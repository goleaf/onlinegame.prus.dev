<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Artifact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'rarity',
        'status',
        'owner_id',
        'village_id',
        'effects',
        'requirements',
        'power_level',
        'durability',
        'max_durability',
        'discovered_at',
        'activated_at',
        'expires_at',
        'is_server_wide',
        'is_unique',
    ];

    protected $casts = [
        'effects' => 'array',
        'requirements' => 'array',
        'discovered_at' => 'datetime',
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_server_wide' => 'boolean',
        'is_unique' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'owner_id');
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function artifactEffects(): HasMany
    {
        return $this->hasMany(ArtifactEffect::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByRarity($query, $rarity)
    {
        return $query->where('rarity', $rarity);
    }

    public function scopeServerWide($query)
    {
        return $query->where('is_server_wide', true);
    }

    public function scopeUnique($query)
    {
        return $query->where('is_unique', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now());
    }

    public function scopeValid($query)
    {
        return $query->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               ($this->expires_at === null || $this->expires_at > now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at <= now();
    }

    public function canBeActivated(): bool
    {
        return $this->status === 'inactive' && $this->durability > 0;
    }

    public function activate(): bool
    {
        if (!$this->canBeActivated()) {
            return false;
        }

        $this->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);

        return true;
    }

    public function deactivate(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $this->update(['status' => 'inactive']);
        return true;
    }

    public function takeDamage(int $amount): void
    {
        $this->durability = max($this->durability - $amount, 0);
        
        if ($this->durability <= 0) {
            $this->status = 'destroyed';
        }
        
        $this->save();
    }

    public function repair(int $amount): void
    {
        $this->durability = min($this->durability + $amount, $this->max_durability);
        $this->save();
    }

    public function getDurabilityPercentageAttribute(): float
    {
        return ($this->durability / $this->max_durability) * 100;
    }

    public function getRarityColorAttribute(): string
    {
        return match($this->rarity) {
            'common' => '#9CA3AF',      // Gray
            'uncommon' => '#10B981',    // Green
            'rare' => '#3B82F6',        // Blue
            'epic' => '#8B5CF6',        // Purple
            'legendary' => '#F59E0B',   // Orange
            'mythic' => '#EF4444',      // Red
            default => '#6B7280'
        };
    }

    public function getTypeDisplayNameAttribute(): string
    {
        return match($this->type) {
            'weapon' => 'Weapon',
            'armor' => 'Armor',
            'tool' => 'Tool',
            'mystical' => 'Mystical',
            'relic' => 'Relic',
            'crystal' => 'Crystal',
            default => ucfirst($this->type)
        };
    }

    public function getRarityDisplayNameAttribute(): string
    {
        return ucfirst($this->rarity);
    }

    public function getStatusDisplayNameAttribute(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'hidden' => 'Hidden',
            'destroyed' => 'Destroyed',
            default => ucfirst($this->status)
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
