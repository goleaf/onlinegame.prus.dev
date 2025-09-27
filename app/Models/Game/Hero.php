<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MohamedSaid\Referenceable\Traits\HasReference;

class Hero extends Model
{
    use HasFactory, HasReference;

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
        'reference_number',
    ];

    protected $casts = [
        'special_abilities' => 'array',
        'equipment' => 'array',
        'is_active' => 'boolean',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';
    protected $referenceTemplate = [
        'format' => 'HERO-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];
    protected $referencePrefix = 'HERO';

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

    /**
     * Get total power (attack + defense)
     */
    public function getTotalPowerAttribute(): int
    {
        return $this->attack_power + $this->defense_power;
    }

    /**
     * Check if hero can use special ability
     */
    public function canUseAbility(string $ability): bool
    {
        return in_array($ability, $this->special_abilities ?? []);
    }

    /**
     * Add special ability
     */
    public function addAbility(string $ability): void
    {
        $abilities = $this->special_abilities ?? [];
        if (!in_array($ability, $abilities)) {
            $abilities[] = $ability;
            $this->special_abilities = $abilities;
            $this->save();
        }
    }

    /**
     * Remove special ability
     */
    public function removeAbility(string $ability): void
    {
        $abilities = $this->special_abilities ?? [];
        $this->special_abilities = array_values(array_filter($abilities, fn($a) => $a !== $ability));
        $this->save();
    }

    /**
     * Equip item
     */
    public function equipItem(string $item, array $stats = []): void
    {
        $equipment = $this->equipment ?? [];
        $equipment[$item] = $stats;
        $this->equipment = $equipment;
        $this->save();
    }

    /**
     * Unequip item
     */
    public function unequipItem(string $item): void
    {
        $equipment = $this->equipment ?? [];
        unset($equipment[$item]);
        $this->equipment = $equipment;
        $this->save();
    }

    /**
     * Get equipment bonus for stat
     */
    public function getEquipmentBonus(string $stat): int
    {
        $equipment = $this->equipment ?? [];
        $bonus = 0;
        
        foreach ($equipment as $item => $stats) {
            $bonus += $stats[$stat] ?? 0;
        }
        
        return $bonus;
    }

    /**
     * Get effective attack power (base + equipment)
     */
    public function getEffectiveAttackPowerAttribute(): int
    {
        return $this->attack_power + $this->getEquipmentBonus('attack');
    }

    /**
     * Get effective defense power (base + equipment)
     */
    public function getEffectiveDefensePowerAttribute(): int
    {
        return $this->defense_power + $this->getEquipmentBonus('defense');
    }

    /**
     * Get effective health (base + equipment)
     */
    public function getEffectiveMaxHealthAttribute(): int
    {
        return $this->max_health + $this->getEquipmentBonus('health');
    }

    /**
     * Check if hero is ready for battle
     */
    public function isReadyForBattle(): bool
    {
        return $this->is_active && $this->isAlive() && $this->getHealthPercentage() > 50;
    }

    /**
     * Get hero status
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }
        
        if (!$this->isAlive()) {
            return 'dead';
        }
        
        if ($this->getHealthPercentage() < 25) {
            return 'critical';
        }
        
        if ($this->getHealthPercentage() < 50) {
            return 'wounded';
        }
        
        return 'healthy';
    }

    /**
     * Scope for heroes ready for battle
     */
    public function scopeReadyForBattle($query)
    {
        return $query->where('is_active', true)
                    ->where('health', '>', 0)
                    ->whereRaw('(health / max_health) > 0.5');
    }

    /**
     * Scope for heroes by status
     */
    public function scopeByStatus($query, string $status)
    {
        return match($status) {
            'healthy' => $query->where('is_active', true)
                              ->where('health', '>', 0)
                              ->whereRaw('(health / max_health) >= 0.5'),
            'wounded' => $query->where('is_active', true)
                              ->where('health', '>', 0)
                              ->whereRaw('(health / max_health) BETWEEN 0.25 AND 0.5'),
            'critical' => $query->where('is_active', true)
                               ->where('health', '>', 0)
                               ->whereRaw('(health / max_health) < 0.25'),
            'dead' => $query->where('health', '<=', 0),
            'inactive' => $query->where('is_active', false),
            default => $query
        };
    }
}
