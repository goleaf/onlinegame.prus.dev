<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;

class ArtifactEffect extends Model
{
    use HasFactory, HasReference;

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'AE-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'AE';

    protected $fillable = [
        'artifact_id',
        'effect_type',
        'target_type',
        'target_id',
        'effect_data',
        'magnitude',
        'duration_type',
        'duration_hours',
        'starts_at',
        'expires_at',
        'is_active',
        'reference_number',
    ];

    protected $casts = [
        'effect_data' => 'array',
        'magnitude' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function artifact(): BelongsTo
    {
        return $this->belongsTo(Artifact::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('effect_type', $type);
    }

    public function scopeByTarget($query, $targetType, $targetId = null)
    {
        $query = $query->where('target_type', $targetType);

        if ($targetId !== null) {
            $query->where('target_id', $targetId);
        }

        return $query;
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeValid($query)
    {
        return $query
            ->where('is_active', true)
            ->where(function ($q) {
                $q
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->is_active &&
            ($this->expires_at === null || $this->expires_at > now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at <= now();
    }

    public function activate(): bool
    {
        if ($this->is_active) {
            return false;
        }

        $this->update([
            'is_active' => true,
            'starts_at' => now(),
            'expires_at' => $this->duration_hours ? now()->addHours($this->duration_hours) : null,
        ]);

        return true;
    }

    public function deactivate(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $this->update(['is_active' => false]);
        return true;
    }

    public function getEffectTypeDisplayNameAttribute(): string
    {
        return match ($this->effect_type) {
            'resource_bonus' => 'Resource Bonus',
            'combat_bonus' => 'Combat Bonus',
            'building_bonus' => 'Building Bonus',
            'troop_bonus' => 'Troop Bonus',
            'defense_bonus' => 'Defense Bonus',
            'attack_bonus' => 'Attack Bonus',
            'speed_bonus' => 'Speed Bonus',
            'production_bonus' => 'Production Bonus',
            'trade_bonus' => 'Trade Bonus',
            'diplomacy_bonus' => 'Diplomacy Bonus',
            default => ucfirst(str_replace('_', ' ', $this->effect_type))
        };
    }

    public function getTargetTypeDisplayNameAttribute(): string
    {
        return match ($this->target_type) {
            'player' => 'Player',
            'village' => 'Village',
            'server' => 'Server',
            'tribe' => 'Tribe',
            'alliance' => 'Alliance',
            default => ucfirst($this->target_type)
        };
    }

    public function getDurationTypeDisplayNameAttribute(): string
    {
        return match ($this->duration_type) {
            'permanent' => 'Permanent',
            'temporary' => 'Temporary',
            'conditional' => 'Conditional',
            default => ucfirst($this->duration_type)
        };
    }

    public function getRemainingTimeAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return max(0, $this->expires_at->diffInMinutes(now()));
    }

    public function getTimeRemainingFormattedAttribute(): ?string
    {
        $minutes = $this->remaining_time;

        if ($minutes === null) {
            return 'Permanent';
        }

        if ($minutes < 60) {
            return "{$minutes} minutes";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 24) {
            return $remainingMinutes > 0 ? "{$hours}h {$remainingMinutes}m" : "{$hours} hours";
        }

        $days = floor($hours / 24);
        $remainingHours = $hours % 24;

        return $remainingHours > 0 ? "{$days}d {$remainingHours}h" : "{$days} days";
    }
}
