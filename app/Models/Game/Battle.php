<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Battle extends Model
{
    protected $fillable = [
        'attacker_id',
        'defender_id',
        'village_id',
        'battle_type',
        'result',
        'attacker_losses',
        'defender_losses',
        'resources_looted',
        'battle_data',
        'occurred_at',
    ];

    protected $casts = [
        'attacker_losses' => 'array',
        'defender_losses' => 'array',
        'resources_looted' => 'array',
        'battle_data' => 'array',
        'occurred_at' => 'datetime',
    ];

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
