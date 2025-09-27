<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MohamedSaid\Referenceable\Traits\HasReference;

class SiegeWeapon extends Model
{
    use HasFactory;

    protected $fillable = [
        'village_id',
        'type',
        'name',
        'attack_power',
        'defense_power',
        'health',
        'max_health',
        'cost',
        'description',
        'is_active',
    ];

    protected $casts = [
        'cost' => 'array',
        'is_active' => 'boolean',
    ];

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Helper methods
    public function takeDamage(int $amount): void
    {
        $this->health = max($this->health - $amount, 0);
        $this->save();
    }

    public function repair(int $amount): void
    {
        $this->health = min($this->health + $amount, $this->max_health);
        $this->save();
    }

    public function isDestroyed(): bool
    {
        return $this->health <= 0;
    }

    public function getHealthPercentage(): float
    {
        return ($this->health / $this->max_health) * 100;
    }

    public function getEffectiveness(): float
    {
        // Siege weapons become less effective as they take damage
        $healthPercentage = $this->getHealthPercentage();
        return $healthPercentage / 100;
    }
}
