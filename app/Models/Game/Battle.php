<?php

namespace App\Models\Game;

use Aliziodev\LaravelTaxonomy\Traits\HasTaxonomy;
use App\Services\GeographicService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use IndexZer0\EloquentFiltering\Contracts\IsFilterable;
use IndexZer0\EloquentFiltering\Filter\Contracts\AllowedFilterList;
use IndexZer0\EloquentFiltering\Filter\Filterable\Filter;
use IndexZer0\EloquentFiltering\Filter\Traits\Filterable;
use IndexZer0\EloquentFiltering\Filter\Types\Types;
use MohamedSaid\Referenceable\Traits\HasReference;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use WendellAdriel\Lift\Lift;

class Battle extends Model implements Auditable, IsFilterable
{
    use HasReference;
    use HasTaxonomy;
    use AuditableTrait;
    use Filterable;
    use Lift;

    // Laravel Lift typed properties
    public int $id;
    public int $attacker_id;
    public int $defender_id;
    public int $village_id;
    public ?array $attacker_troops;
    public ?array $defender_troops;
    public ?array $attacker_losses;
    public ?array $defender_losses;
    public ?array $loot;
    public ?int $war_id;
    public string $result;
    public \Carbon\Carbon $occurred_at;
    public ?string $reference_number;
    public \Carbon\Carbon $created_at;
    public \Carbon\Carbon $updated_at;

    protected $fillable = [
        'attacker_id',
        'defender_id',
        'village_id',
        'attacker_troops',
        'defender_troops',
        'attacker_losses',
        'defender_losses',
        'loot',
        'war_id',
        'result',
        'occurred_at',
        'reference_number',
    ];

    protected $casts = [
        'attacker_troops' => 'array',
        'defender_troops' => 'array',
        'attacker_losses' => 'array',
        'defender_losses' => 'array',
        'loot' => 'array',
        'occurred_at' => 'datetime',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'BTL-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'BTL';

    public function attacker(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'attacker_id');
    }

    public function defender(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'defender_id');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    /**
     * Get the alliance war this battle belongs to
     */
    public function war(): BelongsTo
    {
        return $this->belongsTo(AllianceWar::class, 'war_id');
    }

    // Optimized query scopes using when() and selectRaw
    public function scopeWithStats($query)
    {
        return $query->selectRaw('
            battles.*,
            (SELECT COUNT(*) FROM battles b2 WHERE b2.attacker_id = battles.attacker_id OR b2.defender_id = battles.attacker_id) as attacker_total_battles,
            (SELECT COUNT(*) FROM battles b3 WHERE b3.attacker_id = battles.defender_id OR b3.defender_id = battles.defender_id) as defender_total_battles,
            (SELECT SUM(CASE WHEN b4.attacker_id = battles.attacker_id AND b4.result = "victory" THEN 1 ELSE 0 END) FROM battles b4 WHERE b4.attacker_id = battles.attacker_id) as attacker_victories,
            (SELECT SUM(CASE WHEN b5.defender_id = battles.defender_id AND b5.result = "victory" THEN 1 ELSE 0 END) FROM battles b5 WHERE b5.defender_id = battles.defender_id) as defender_victories,
            (SELECT AVG(EXTRACT(EPOCH FROM (b6.occurred_at - b6.created_at))/3600) FROM battles b6 WHERE b6.attacker_id = battles.attacker_id) as avg_battle_duration_hours
        ');
    }

    public function scopeByPlayer($query, $playerId)
    {
        return $query->where(function ($q) use ($playerId) {
            $q
                ->where('attacker_id', $playerId)
                ->orWhere('defender_id', $playerId);
        });
    }

    public function scopeByVillage($query, $villageId)
    {
        return $query->where('village_id', $villageId);
    }

    public function scopeByType($query, $type = null)
    {
        return $query->when($type, function ($q) use ($type) {
            return $q->where('battle_type', $type);
        });
    }

    public function scopeByResult($query, $result = null)
    {
        return $query->when($result, function ($q) use ($result) {
            return $q->where('result', $result);
        });
    }

    public function scopeVictories($query)
    {
        return $query->where('result', 'victory');
    }

    public function scopeDefeats($query)
    {
        return $query->where('result', 'defeat');
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('occurred_at', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('occurred_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('occurred_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('occurred_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->when($searchTerm, function ($q) use ($searchTerm) {
            return $q->where(function ($subQ) use ($searchTerm) {
                $subQ
                    ->whereIn('attacker_id', function ($playerQ) use ($searchTerm) {
                        $playerQ
                            ->select('id')
                            ->from('players')
                            ->where('name', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereIn('defender_id', function ($playerQ) use ($searchTerm) {
                        $playerQ
                            ->select('id')
                            ->from('players')
                            ->where('name', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereIn('village_id', function ($villageQ) use ($searchTerm) {
                        $villageQ
                            ->select('id')
                            ->from('villages')
                            ->where('name', 'like', '%' . $searchTerm . '%');
                    });
            });
        });
    }

    public function scopeWithPlayerInfo($query)
    {
        return $query->with([
            'attacker:id,name',
            'defender:id,name',
            'village:id,name'
        ]);
    }

    /**
     * Get battle statistics with Laradumps debugging
     */
    public static function getBattleStatistics($playerId = null, $days = 30)
    {
        $startTime = microtime(true);
        
        ds('Battle: Getting battle statistics', [
            'model' => 'Battle',
            'method' => 'getBattleStatistics',
            'player_id' => $playerId,
            'days' => $days,
            'query_time' => now()
        ]);
        
        $query = static::withStats();
        
        if ($playerId) {
            $query->byPlayer($playerId);
        }
        
        $query->recent($days);
        
        $battles = $query->get();
        
        $statistics = [
            'total_battles' => $battles->count(),
            'victories' => $battles->where('result', 'victory')->count(),
            'defeats' => $battles->where('result', 'defeat')->count(),
            'win_rate' => $battles->count() > 0 ? round(($battles->where('result', 'victory')->count() / $battles->count()) * 100, 2) : 0,
            'avg_loot' => $battles->avg(function($battle) {
                return is_array($battle->loot) ? array_sum($battle->loot) : 0;
            }),
            'battles_by_day' => $battles->groupBy(function($battle) {
                return $battle->occurred_at->format('Y-m-d');
            })->map->count()
        ];
        
        $queryTime = round((microtime(true) - $startTime) * 1000, 2);
        
        ds('Battle: Battle statistics calculated', [
            'total_battles' => $statistics['total_battles'],
            'victories' => $statistics['victories'],
            'defeats' => $statistics['defeats'],
            'win_rate' => $statistics['win_rate'],
            'query_time_ms' => $queryTime,
            'player_id' => $playerId,
            'days_analyzed' => $days
        ]);
        
        return $statistics;
    }

    /**
     * Define allowed filters for the Battle model
     */
    public function allowedFilters(): AllowedFilterList
    {
        return Filter::only(
            Filter::field('result', ['$eq']),
            Filter::field('attacker_id', ['$eq']),
            Filter::field('defender_id', ['$eq']),
            Filter::field('village_id', ['$eq']),
            Filter::field('occurred_at', ['$eq', '$gt', '$lt']),
            Filter::field('reference_number', ['$eq', '$like']),
            Filter::relation('attacker', ['$has']),
            Filter::relation('defender', ['$has']),
            Filter::relation('village', ['$has'])
        );
    }
}

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'BTL-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'BTL';

    public function attacker(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'attacker_id');
    }

    public function defender(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'defender_id');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    /**
     * Get the alliance war this battle belongs to
     */
    public function war(): BelongsTo
    {
        return $this->belongsTo(AllianceWar::class, 'war_id');
    }

    // Optimized query scopes using when() and selectRaw
    public function scopeWithStats($query)
    {
        return $query->selectRaw('
            battles.*,
            (SELECT COUNT(*) FROM battles b2 WHERE b2.attacker_id = battles.attacker_id OR b2.defender_id = battles.attacker_id) as attacker_total_battles,
            (SELECT COUNT(*) FROM battles b3 WHERE b3.attacker_id = battles.defender_id OR b3.defender_id = battles.defender_id) as defender_total_battles,
            (SELECT SUM(CASE WHEN b4.attacker_id = battles.attacker_id AND b4.result = "victory" THEN 1 ELSE 0 END) FROM battles b4 WHERE b4.attacker_id = battles.attacker_id) as attacker_victories,
            (SELECT SUM(CASE WHEN b5.defender_id = battles.defender_id AND b5.result = "victory" THEN 1 ELSE 0 END) FROM battles b5 WHERE b5.defender_id = battles.defender_id) as defender_victories,
            (SELECT AVG(EXTRACT(EPOCH FROM (b6.occurred_at - b6.created_at))/3600) FROM battles b6 WHERE b6.attacker_id = battles.attacker_id) as avg_battle_duration_hours
        ');
    }

    public function scopeByPlayer($query, $playerId)
    {
        return $query->where(function ($q) use ($playerId) {
            $q
                ->where('attacker_id', $playerId)
                ->orWhere('defender_id', $playerId);
        });
    }

    public function scopeByVillage($query, $villageId)
    {
        return $query->where('village_id', $villageId);
    }

    public function scopeByType($query, $type = null)
    {
        return $query->when($type, function ($q) use ($type) {
            return $q->where('battle_type', $type);
        });
    }

    public function scopeByResult($query, $result = null)
    {
        return $query->when($result, function ($q) use ($result) {
            return $q->where('result', $result);
        });
    }

    public function scopeVictories($query)
    {
        return $query->where('result', 'victory');
    }

    public function scopeDefeats($query)
    {
        return $query->where('result', 'defeat');
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('occurred_at', '>=', now()->subDays($days));
    }

    public function scopeToday($query)
    {
        return $query->whereDate('occurred_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('occurred_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('occurred_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->when($searchTerm, function ($q) use ($searchTerm) {
            return $q->where(function ($subQ) use ($searchTerm) {
                $subQ
                    ->whereIn('attacker_id', function ($playerQ) use ($searchTerm) {
                        $playerQ
                            ->select('id')
                            ->from('players')
                            ->where('name', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereIn('defender_id', function ($playerQ) use ($searchTerm) {
                        $playerQ
                            ->select('id')
                            ->from('players')
                            ->where('name', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereIn('village_id', function ($villageQ) use ($searchTerm) {
                        $villageQ
                            ->select('id')
                            ->from('villages')
                            ->where('name', 'like', '%' . $searchTerm . '%');
                    });
            });
        });
    }

    public function scopeWithPlayerInfo($query)
    {
        return $query->with([
            'attacker:id,name',
            'defender:id,name',
            'village:id,name'
        ]);
    }

    /**
     * Define allowed filters for the Battle model
     */
    public function allowedFilters(): AllowedFilterList
    {
        return Filter::only(
            Filter::field('result', ['$eq']),
            Filter::field('attacker_id', ['$eq']),
            Filter::field('defender_id', ['$eq']),
            Filter::field('village_id', ['$eq']),
            Filter::field('occurred_at', ['$eq', '$gt', '$lt']),
            Filter::field('reference_number', ['$eq', '$like']),
            Filter::relation('attacker', ['$has']),
            Filter::relation('defender', ['$has']),
            Filter::relation('village', ['$has'])
        );
    }
}


    /**
     * Get battle distance between attacker and defender villages
     */
    public function getBattleDistanceAttribute(): ?float
    {
        if (!$this->attackerVillage || !$this->defenderVillage) {
            return null;
        }

        $geoService = app(GeographicService::class);
        return $geoService->calculateDistance(
            $this->attackerVillage->latitude ?? 0,
            $this->attackerVillage->longitude ?? 0,
            $this->defenderVillage->latitude ?? 0,
            $this->defenderVillage->longitude ?? 0
        );
    }

    /**
     * Get battle bearing from attacker to defender
     */
    public function getBattleBearingAttribute(): ?float
    {
        if (!$this->attackerVillage || !$this->defenderVillage) {
            return null;
        }

        $geoService = app(GeographicService::class);
        return $geoService->calculateBearing(
            $this->attackerVillage->latitude ?? 0,
            $this->attackerVillage->longitude ?? 0,
            $this->defenderVillage->latitude ?? 0,
            $this->defenderVillage->longitude ?? 0
        );
    }

    /**
     * Get battle travel time based on distance
     */
    public function getBattleTravelTimeAttribute(): ?int
    {
        $distance = $this->battle_distance;
        if (!$distance) {
            return null;
        }

        $geoService = app(GeographicService::class);
        return $geoService->calculateTravelTimeFromDistance($distance);
    }

    /**
     * Scope for battles within a certain distance
     */
    public function scopeWithinDistance($query, $latitude, $longitude, $maxDistance)
    {
        return $query->whereHas('village', function ($q) use ($latitude, $longitude, $maxDistance) {
            $q->whereRaw("ST_Distance_Sphere(
                POINT(longitude, latitude), 
                POINT(?, ?)
            ) <= ?", [$longitude, $latitude, $maxDistance * 1000]);
        });
    }

    /**
     * Scope for battles by geographic region
     */
    public function scopeInGeographicRegion($query, $minLat, $maxLat, $minLon, $maxLon)
    {
        return $query->whereHas('village', function ($q) use ($minLat, $maxLat, $minLon, $maxLon) {
            $q->whereBetween('latitude', [$minLat, $maxLat])
              ->whereBetween('longitude', [$minLon, $maxLon]);
        });
    }
}
