<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hero extends Model
{
    use HasFactory;

    protected $fillable = [
        'player_id',
        'name',
        'level',
        'experience',
        'attack_power',
        'defense_power',
        'health',
        'max_health',
        'special_abilities',
        'equipment',
        'is_active',
    ];

    protected $casts = [
        'special_abilities' => 'array',
        'equipment' => 'array',
        'is_active' => 'boolean',
    ];

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    // Helper methods
    public function gainExperience(int $amount): void
    {
        $this->experience += $amount;
        
        // Check for level up
        $requiredExp = $this->level * 1000;
        if ($this->experience >= $requiredExp) {
            $this->levelUp();
        }
        
        $this->save();
    }

    public function levelUp(): void
    {
        $this->level++;
        $this->attack_power += 10;
        $this->defense_power += 10;
        $this->max_health += 100;
        $this->health = $this->max_health; // Full heal on level up
    }

    public function heal(int $amount): void
    {
        $this->health = min($this->health + $amount, $this->max_health);
        $this->save();
    }

    public function takeDamage(int $amount): void
    {
        $this->health = max($this->health - $amount, 0);
        $this->save();
    }

    public function isAlive(): bool
    {
        return $this->health > 0;
    }

    public function getHealthPercentage(): float
    {
        return ($this->health / $this->max_health) * 100;
    }
}
