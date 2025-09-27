<?php

namespace App\Models\Game;

use App\Models\User;
use App\ValueObjects\PlayerStats;
use IndexZer0\EloquentFiltering\Filter\Traits\Filterable;
use IndexZer0\EloquentFiltering\Contracts\IsFilterable;
use IndexZer0\EloquentFiltering\Filter\Contracts\AllowedFilterList;
use IndexZer0\EloquentFiltering\Filter\Filterable\Filter;
use IndexZer0\EloquentFiltering\Filter\Types\Types;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Notable\Traits\HasNotables;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use SmartCache\Facades\SmartCache;
use WendellAdriel\Lift\Lift;

class Player extends Model implements Auditable
{
    use HasFactory;
    use HasNotables;
    use AuditableTrait;

    protected $fillable = [
        'user_id',
        'world_id',
        'name',
        'tribe',
        'alliance_id',
        'population',
        'villages_count',
        'is_active',
        'is_online',
        'last_login',
        'last_active_at',
        'points',
        'total_attack_points',
        'total_defense_points',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'last_login' => 'datetime',
        'last_active_at' => 'datetime',
        'is_active' => 'boolean',
        'is_online' => 'boolean',
    ];

    /**
     * Get the player stats as a value object
     */
    protected function stats(): Attribute
    {
        return Attribute::make(
            get: fn () => new PlayerStats(
                points: $this->points,
                population: $this->population,
                villagesCount: $this->villages_count,
                totalAttackPoints: $this->total_attack_points,
                totalDefensePoints: $this->total_defense_points,
                isActive: $this->is_active,
                isOnline: $this->is_online
            ),
            set: fn (PlayerStats $stats) => [
                'points' => $stats->points,
                'population' => $stats->population,
                'villages_count' => $stats->villagesCount,
                'total_attack_points' => $stats->totalAttackPoints,
                'total_defense_points' => $stats->totalDefensePoints,
                'is_active' => $stats->isActive,
                'is_online' => $stats->isOnline,
            ]
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function villages(): HasMany
    {
        return $this->hasMany(Village::class);
    }

    public function hero(): HasOne
    {
        return $this->hasOne(Hero::class);
    }

    public function world()
    {
        return $this->belongsTo(World::class);
    }

    public function alliance()
    {
        return $this->belongsTo(Alliance::class);
    }

    public function allianceMembership()
    {
        return $this->hasOne(AllianceMember::class);
    }

    public function statistics()
    {
        return $this->hasOne(PlayerStatistic::class);
    }

    public function quests()
    {
        return $this
            ->belongsToMany(Quest::class, 'player_quests')
            ->withPivot(['status', 'progress', 'progress_data', 'started_at', 'completed_at', 'expires_at'])
            ->withTimestamps();
    }

    public function achievements()
    {
        return $this
            ->belongsToMany(Achievement::class, 'player_achievements')
            ->withPivot(['unlocked_at', 'progress_data'])
            ->withTimestamps();
    }

    public function technologies()
    {
        return $this
            ->belongsToMany(Technology::class, 'player_technologies')
            ->withPivot(['level', 'research_progress', 'unlocked_at'])
            ->withTimestamps();
    }

    // Enhanced query scopes using Query Enrich
    public function scopeWithStats($query)
    {
        return $query->select([
            'players.*',
            QE::select(QE::count(c('id')))
                ->from('villages')
                ->whereColumn('player_id', c('players.id'))
                ->as('village_count'),
            QE::select(QE::sum(c('population')))
                ->from('villages')
                ->whereColumn('player_id', c('players.id'))
                ->as('total_population'),
            QE::select(QE::count(c('id')))
                ->from('reports')
                ->where(function($q) {
                    $q->whereColumn('attacker_id', c('players.id'))
                      ->orWhereColumn('defender_id', c('players.id'));
                })
                ->as('total_battles'),
            QE::select(
                QE::sum(QE::case()
                    ->when(QE::and(
                        QE::eq(c('attacker_id'), c('players.id')),
                        QE::eq(c('status'), 'victory')
                    ), 1)
                    ->else(0))
                )->add(
                    QE::sum(QE::case()
                        ->when(QE::and(
                            QE::eq(c('defender_id'), c('players.id')),
                            QE::eq(c('status'), 'victory')
                        ), 1)
                        ->else(0))
                )
            )->from('reports')->as('total_victories')
        ]);
    }

    public function scopeByWorld($query, $worldId)
    {
        return $query->where('world_id', $worldId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    public function scopeInAlliance($query, $allianceId = null)
    {
        return $query->when($allianceId, function ($q) use ($allianceId) {
            return $q->where('alliance_id', $allianceId);
        }, function ($q) {
            return $q->whereNotNull('alliance_id');
        });
    }

    public function scopeByTribe($query, $tribe)
    {
        return $query->where('tribe', $tribe);
    }

    public function scopeTopPlayers($query, $limit = 10)
    {
        return $query->orderBy('points', 'desc')->limit($limit);
    }

    public function scopeSearch($query, $searchTerm)
    {
        return $query->when($searchTerm, function ($q) use ($searchTerm) {
            return $q->where('name', 'like', '%' . $searchTerm . '%');
        });
    }

    public function tasks()
    {
        return $this->hasMany(GameTask::class);
    }

    public function events()
    {
        return $this->hasMany(GameEvent::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function playerQuests(): HasMany
    {
        return $this->hasMany(PlayerQuest::class);
    }

    /**
     * Get players with SmartCache optimization
     */
    public static function getCachedPlayers($worldId = null, $filters = [])
    {
        $cacheKey = "players_{$worldId}_" . md5(serialize($filters));
        
        return SmartCache::remember($cacheKey, now()->addMinutes(10), function () use ($worldId, $filters) {
            $query = static::active()->withStats();
            
            if ($worldId) {
                $query->byWorld($worldId);
            }
            
            if (isset($filters['tribe'])) {
                $query->byTribe($filters['tribe']);
            }
            
            if (isset($filters['alliance'])) {
                $query->byAlliance($filters['alliance']);
            }
            
            if (isset($filters['online'])) {
                $query->online();
            }
            
            if (isset($filters['search'])) {
                $query->search($filters['search']);
            }
            
            return $query->get();
        });
    }

    public function notes()
    {
        return $this->hasMany(PlayerNote::class);
    }

    public function targetNotes()
    {
        return $this->hasMany(PlayerNote::class, 'target_player_id');
    }

    public function movements()
    {
        return $this->hasMany(Movement::class);
    }

    public function battlesAsAttacker()
    {
        return $this->hasMany(Battle::class, 'attacker_id');
    }

    public function battlesAsDefender()
    {
        return $this->hasMany(Battle::class, 'defender_id');
    }

    /**
     * Define allowed filters for the Player model
     */
    public function allowedFilters(): AllowedFilterList
    {
        return Filter::only(
            Filter::field('name', ['$eq', '$contains']),
            Filter::field('tribe', ['$eq']),
            Filter::field('points', ['$eq', '$gt', '$lt']),
            Filter::field('population', ['$eq', '$gt', '$lt']),
            Filter::field('villages_count', ['$eq', '$gt', '$lt']),
            Filter::field('is_active', ['$eq']),
            Filter::field('is_online', ['$eq']),
            Filter::field('world_id', ['$eq']),
            Filter::field('alliance_id', ['$eq']),
            Filter::field('last_login', ['$eq', '$gt', '$lt']),
            Filter::field('last_active_at', ['$eq', '$gt', '$lt']),
            Filter::relation('alliance', ['$has']),
            Filter::relation('villages', ['$has']),
            Filter::relation('user', ['$has'])
        );
    }
}
