<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;

class WonderAttack extends Model
{
    use HasFactory, HasReference;

    protected $fillable = [
        'wonder_id',
        'attacker_alliance_id',
        'defender_alliance_id',
        'attack_strength',
        'defense_strength',
        'success',
        'casualties',
        'loot',
        'occurred_at',
        'reference_number',
    ];

    protected $casts = [
        'attack_strength' => 'integer',
        'defense_strength' => 'integer',
        'success' => 'boolean',
        'casualties' => 'array',
        'loot' => 'array',
        'occurred_at' => 'datetime',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'WNA-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'WNA';

    public function wonder(): BelongsTo
    {
        return $this->belongsTo(Wonder::class);
    }

    public function attackerAlliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'attacker_alliance_id');
    }

    public function defenderAlliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'defender_alliance_id');
    }

    // Scopes
    public function scopeByWonder($query, $wonderId)
    {
        return $query->where('wonder_id', $wonderId);
    }

    public function scopeByAttacker($query, $allianceId)
    {
        return $query->where('attacker_alliance_id', $allianceId);
    }

    public function scopeByDefender($query, $allianceId)
    {
        return $query->where('defender_alliance_id', $allianceId);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    public function scopeFailed($query)
    {
        return $query->where('success', false);
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

    // Methods
    public function getAttackResult(): string
    {
        return $this->success ? 'Victory' : 'Defeat';
    }

    public function getCasualtyCount(): int
    {
        if (!$this->casualties) {
            return 0;
        }

        return array_sum($this->casualties);
    }

    public function getLootValue(): int
    {
        if (!$this->loot) {
            return 0;
        }

        return array_sum($this->loot);
    }

    public function getAttackEfficiency(): float
    {
        if ($this->defense_strength == 0) {
            return 100;
        }

        return ($this->attack_strength / $this->defense_strength) * 100;
    }

    public function getDefenseEfficiency(): float
    {
        if ($this->attack_strength == 0) {
            return 100;
        }

        return ($this->defense_strength / $this->attack_strength) * 100;
    }

    public static function getAttackStatsForAlliance($allianceId, $days = 30): array
    {
        $attacks = self::where('attacker_alliance_id', $allianceId)
            ->where('occurred_at', '>=', now()->subDays($days))
            ->get();

        $defenses = self::where('defender_alliance_id', $allianceId)
            ->where('occurred_at', '>=', now()->subDays($days))
            ->get();

        return [
            'total_attacks' => $attacks->count(),
            'successful_attacks' => $attacks->where('success', true)->count(),
            'failed_attacks' => $attacks->where('success', false)->count(),
            'attack_success_rate' => $attacks->count() > 0 ? ($attacks->where('success', true)->count() / $attacks->count()) * 100 : 0,
            'total_defenses' => $defenses->count(),
            'successful_defenses' => $defenses->where('success', false)->count(),
            'failed_defenses' => $defenses->where('success', true)->count(),
            'defense_success_rate' => $defenses->count() > 0 ? ($defenses->where('success', false)->count() / $defenses->count()) * 100 : 0,
            'total_casualties' => $attacks->sum('casualties') + $defenses->sum('casualties'),
            'total_loot' => $attacks->sum('loot'),
        ];
    }

    public static function getWonderAttackHistory($wonderId, $limit = 50)
    {
        return self::byWonder($wonderId)
            ->with(['attackerAlliance', 'defenderAlliance'])
            ->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
