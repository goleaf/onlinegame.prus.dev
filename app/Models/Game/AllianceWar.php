<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;

class AllianceWar extends Model
{
    use HasFactory, HasReference;

    protected $fillable = [
        'attacker_alliance_id',
        'defender_alliance_id',
        'reason',
        'status',
        'declared_at',
        'ended_at',
        'war_score',
        'war_data',
        'reference_number',
    ];

    protected $casts = [
        'declared_at' => 'datetime',
        'ended_at' => 'datetime',
        'war_data' => 'array',
    ];

    /**
     * Get the attacker alliance
     */
    public function attackerAlliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'attacker_alliance_id');
    }

    /**
     * Get the defender alliance
     */
    public function defenderAlliance(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'defender_alliance_id');
    }

    /**
     * Get all battles in this war
     */
    public function battles()
    {
        return $this->hasMany(Battle::class, 'war_id');
    }

    /**
     * Check if war is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if war is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get war duration
     */
    public function getDurationAttribute(): ?int
    {
        if ($this->ended_at) {
            return $this->declared_at->diffInDays($this->ended_at);
        }

        return $this->declared_at->diffInDays(now());
    }

    /**
     * Get war winner
     */
    public function getWinnerAttribute(): ?Alliance
    {
        if (!$this->isCompleted()) {
            return null;
        }

        return $this->war_score > 0 ? $this->attackerAlliance : $this->defenderAlliance;
    }

    /**
     * Get war loser
     */
    public function getLoserAttribute(): ?Alliance
    {
        if (!$this->isCompleted()) {
            return null;
        }

        return $this->war_score > 0 ? $this->defenderAlliance : $this->attackerAlliance;
    }

    /**
     * Get war score for specific alliance
     */
    public function getWarScoreForAlliance(Alliance $alliance): int
    {
        if ($alliance->id === $this->attacker_alliance_id) {
            return $this->war_score;
        } elseif ($alliance->id === $this->defender_alliance_id) {
            return -$this->war_score;
        }

        return 0;
    }

    /**
     * Check if alliance is winning
     */
    public function isAllianceWinning(Alliance $alliance): bool
    {
        return $this->getWarScoreForAlliance($alliance) > 0;
    }

    /**
     * Get war progress percentage
     */
    public function getProgressPercentageAttribute(): float
    {
        return min(100, abs($this->war_score) / 100 * 100);
    }

    /**
     * Scope for active wars
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for completed wars
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for wars involving alliance
     */
    public function scopeInvolvingAlliance($query, Alliance $alliance)
    {
        return $query
            ->where('attacker_alliance_id', $alliance->id)
            ->orWhere('defender_alliance_id', $alliance->id);
    }

    /**
     * Scope for wars where alliance is attacker
     */
    public function scopeWhereAttacker($query, Alliance $alliance)
    {
        return $query->where('attacker_alliance_id', $alliance->id);
    }

    /**
     * Scope for wars where alliance is defender
     */
    public function scopeWhereDefender($query, Alliance $alliance)
    {
        return $query->where('defender_alliance_id', $alliance->id);
    }
}
