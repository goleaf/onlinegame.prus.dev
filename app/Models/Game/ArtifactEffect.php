<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArtifactEffect extends Model
{
    use HasFactory;

    protected $fillable = [
        'artifact_id',
        'effect_type',
        'effect_value',
        'target_type',
        'target_id',
        'is_active',
        'activated_at',
        'expires_at',
        'effect_data',
    ];

    protected $casts = [
        'effect_value' => 'float',
        'is_active' => 'boolean',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'effect_data' => 'array',
    ];

    // Relationships
    public function artifact(): BelongsTo
    {
        return $this->belongsTo(Artifact::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('effect_type', $type);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getDurationAttribute(): ?int
    {
        if (!$this->activated_at || !$this->expires_at) {
            return null;
        }

        return $this->activated_at->diffInSeconds($this->expires_at);
    }

    public function getRemainingTimeAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return max(0, now()->diffInSeconds($this->expires_at));
    }

    // Methods
    public function activate(): bool
    {
        if ($this->is_active) {
            return false;
        }

        $this->update([
            'is_active' => true,
            'activated_at' => now(),
        ]);

        $this->applyEffect();
        return true;
    }

    public function deactivate(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $this->update([
            'is_active' => false,
        ]);

        $this->removeEffect();
        return true;
    }

    public function applyEffect(): void
    {
        switch ($this->effect_type) {
            case 'resource_production':
                $this->applyResourceProductionEffect();
                break;
            case 'building_speed':
                $this->applyBuildingSpeedEffect();
                break;
            case 'unit_training':
                $this->applyUnitTrainingEffect();
                break;
            case 'combat_bonus':
                $this->applyCombatBonusEffect();
                break;
            case 'defense_bonus':
                $this->applyDefenseBonusEffect();
                break;
            case 'movement_speed':
                $this->applyMovementSpeedEffect();
                break;
            case 'storage_capacity':
                $this->applyStorageCapacityEffect();
                break;
            case 'research_speed':
                $this->applyResearchSpeedEffect();
                break;
        }
    }

    public function removeEffect(): void
    {
        // Remove the effect (implementation depends on how effects are stored)
        // This would typically involve updating the affected entities
    }

    protected function applyResourceProductionEffect(): void
    {
        // Apply resource production bonus
        // Implementation depends on how resource production is handled
    }

    protected function applyBuildingSpeedEffect(): void
    {
        // Apply building speed bonus
        // Implementation depends on how building construction is handled
    }

    protected function applyUnitTrainingEffect(): void
    {
        // Apply unit training bonus
        // Implementation depends on how unit training is handled
    }

    protected function applyCombatBonusEffect(): void
    {
        // Apply combat bonus
        // Implementation depends on how combat is handled
    }

    protected function applyDefenseBonusEffect(): void
    {
        // Apply defense bonus
        // Implementation depends on how defense is handled
    }

    protected function applyMovementSpeedEffect(): void
    {
        // Apply movement speed bonus
        // Implementation depends on how movement is handled
    }

    protected function applyStorageCapacityEffect(): void
    {
        // Apply storage capacity bonus
        // Implementation depends on how storage is handled
    }

    protected function applyResearchSpeedEffect(): void
    {
        // Apply research speed bonus
        // Implementation depends on how research is handled
    }
}