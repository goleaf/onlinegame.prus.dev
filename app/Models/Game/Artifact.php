<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use SmartCache\Facades\SmartCache;
use sbamtr\LaravelQueryEnrich\QE;
use function sbamtr\LaravelQueryEnrich\c;
use MohamedSaid\Referenceable\Traits\HasReference;

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

    // Relationships
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

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByRarity($query, string $rarity)
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

    public function scopeOwnedBy($query, Player $player)
    {
        return $query->where('owner_id', $player->id);
    }

    public function scopeInVillage($query, Village $village)
    {
        return $query->where('village_id', $village->id);
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
    public function getPowerLevelAttribute($value): int
    {
        return $value ?? 1;
    }

    public function getDurabilityAttribute($value): int
    {
        return $value ?? 100;
    }

    public function getMaxDurabilityAttribute($value): int
    {
        return $value ?? 100;
    }

    public function getDurabilityPercentageAttribute(): float
    {
        if ($this->max_durability <= 0) {
            return 0;
        }

        return ($this->durability / $this->max_durability) * 100;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && $this->durability > 0;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsDamagedAttribute(): bool
    {
        return $this->durability < $this->max_durability;
    }

    public function getIsBrokenAttribute(): bool
    {
        return $this->durability <= 0;
    }

    public function getRarityColorAttribute(): string
    {
        return match($this->rarity) {
            'common' => 'gray',
            'uncommon' => 'green',
            'rare' => 'blue',
            'epic' => 'purple',
            'legendary' => 'orange',
            'mythic' => 'red',
            default => 'gray'
        };
    }

    public function getTypeIconAttribute(): string
    {
        return match($this->type) {
            'weapon' => 'sword',
            'armor' => 'shield',
            'tool' => 'wrench',
            'mystical' => 'sparkles',
            'relic' => 'gem',
            'crystal' => 'diamond',
            default => 'question-mark-circle'
        };
    }

    // Methods
    public function activate(): bool
    {
        if (!$this->canActivate()) {
            return false;
        }

        $this->update([
            'status' => 'active',
            'activated_at' => now(),
        ]);

        $this->applyEffects();
        return true;
    }

    public function deactivate(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $this->update([
            'status' => 'inactive',
            'activated_at' => null,
        ]);

        $this->removeEffects();
        return true;
    }

    public function canActivate(): bool
    {
        if ($this->status === 'active') {
            return false;
        }

        if ($this->isExpired) {
            return false;
        }

        if ($this->isBroken) {
            return false;
        }

        if (!$this->meetsRequirements()) {
            return false;
        }

        return true;
    }

    public function meetsRequirements(): bool
    {
        if (empty($this->requirements)) {
            return true;
        }

        foreach ($this->requirements as $requirement) {
            if (!$this->checkRequirement($requirement)) {
                return false;
            }
        }

        return true;
    }

    public function checkRequirement(array $requirement): bool
    {
        switch ($requirement['type']) {
            case 'level':
                return $this->owner && $this->owner->level >= $requirement['value'];
            case 'building':
                return $this->village && $this->village->hasBuilding($requirement['building'], $requirement['level'] ?? 1);
            case 'resource':
                return $this->village && $this->village->hasResource($requirement['resource'], $requirement['amount']);
            case 'artifact':
                return $this->owner && $this->owner->hasArtifact($requirement['artifact_id']);
            case 'quest':
                return $this->owner && $this->owner->hasCompletedQuest($requirement['quest_id']);
            case 'achievement':
                return $this->owner && $this->owner->hasAchievement($requirement['achievement_id']);
            default:
                return true;
        }
    }

    public function applyEffects(): void
    {
        if (empty($this->effects)) {
            return;
        }

        foreach ($this->effects as $effect) {
            $this->applyEffect($effect);
        }
    }

    public function removeEffects(): void
    {
        if (empty($this->effects)) {
            return;
        }

        foreach ($this->effects as $effect) {
            $this->removeEffect($effect);
        }
    }

    public function applyEffect(array $effect): void
    {
        switch ($effect['type']) {
            case 'resource_production':
                $this->applyResourceProductionEffect($effect);
                break;
            case 'building_speed':
                $this->applyBuildingSpeedEffect($effect);
                break;
            case 'unit_training':
                $this->applyUnitTrainingEffect($effect);
                break;
            case 'combat_bonus':
                $this->applyCombatBonusEffect($effect);
                break;
            case 'defense_bonus':
                $this->applyDefenseBonusEffect($effect);
                break;
            case 'movement_speed':
                $this->applyMovementSpeedEffect($effect);
                break;
            case 'storage_capacity':
                $this->applyStorageCapacityEffect($effect);
                break;
            case 'research_speed':
                $this->applyResearchSpeedEffect($effect);
                break;
        }
    }

    public function removeEffect(array $effect): void
    {
        // Remove the effect (implementation depends on how effects are stored)
        // This would typically involve updating the affected entities
    }

    public function damage(int $amount): void
    {
        $newDurability = max(0, $this->durability - $amount);
        $this->update(['durability' => $newDurability]);

        if ($newDurability <= 0) {
            $this->break();
        }
    }

    public function repair(int $amount): void
    {
        $newDurability = min($this->max_durability, $this->durability + $amount);
        $this->update(['durability' => $newDurability]);
    }

    public function break(): void
    {
        $this->update([
            'status' => 'destroyed',
            'durability' => 0,
        ]);

        $this->removeEffects();
    }

    public function destroyArtifact(): void
    {
        $this->removeEffects();
        $this->delete();
    }

    public function transfer(Player $newOwner, Village $newVillage = null): bool
    {
        if (!$this->canTransfer()) {
            return false;
        }

        $this->update([
            'owner_id' => $newOwner->id,
            'village_id' => $newVillage?->id,
        ]);

        return true;
    }

    public function canTransfer(): bool
    {
        return $this->status === 'active' && !$this->isExpired;
    }

    public function discover(Player $discoverer, Village $village = null): void
    {
        $this->update([
            'owner_id' => $discoverer->id,
            'village_id' => $village?->id,
            'discovered_at' => now(),
            'status' => 'inactive',
        ]);
    }

    public function getEffectValue(string $effectType): float
    {
        if (empty($this->effects)) {
            return 0;
        }

        foreach ($this->effects as $effect) {
            if ($effect['type'] === $effectType) {
                return $effect['value'] ?? 0;
            }
        }

        return 0;
    }

    public function getTotalPowerAttribute(): int
    {
        $basePower = $this->power_level;
        $rarityMultiplier = match($this->rarity) {
            'common' => 1.0,
            'uncommon' => 1.2,
            'rare' => 1.5,
            'epic' => 2.0,
            'legendary' => 3.0,
            'mythic' => 5.0,
            default => 1.0
        };

        $durabilityMultiplier = $this->durability_percentage / 100;

        return (int) ($basePower * $rarityMultiplier * $durabilityMultiplier);
    }

    // Caching methods
    public static function getCachedArtifacts(string $cacheKey, callable $callback)
    {
        return SmartCache::remember(
            "artifacts_{$cacheKey}",
            now()->addMinutes(30),
            $callback
        );
    }

    public function getCachedEffects()
    {
        return SmartCache::remember(
            "artifact_effects_{$this->id}",
            now()->addMinutes(15),
            function () {
                return $this->effects ?? [];
            }
        );
    }

    // Static methods
    public static function generateRandomArtifact(array $options = []): self
    {
        $types = ['weapon', 'armor', 'tool', 'mystical', 'relic', 'crystal'];
        $rarities = ['common', 'uncommon', 'rare', 'epic', 'legendary', 'mythic'];

        $type = $options['type'] ?? $types[array_rand($types)];
        $rarity = $options['rarity'] ?? $rarities[array_rand($rarities)];

        $powerLevel = match($rarity) {
            'common' => rand(1, 10),
            'uncommon' => rand(10, 25),
            'rare' => rand(25, 50),
            'epic' => rand(50, 75),
            'legendary' => rand(75, 90),
            'mythic' => rand(90, 100),
            default => 1
        };

        return self::create([
            'name' => self::generateArtifactName($type, $rarity),
            'description' => self::generateArtifactDescription($type, $rarity),
            'type' => $type,
            'rarity' => $rarity,
            'status' => 'inactive',
            'power_level' => $powerLevel,
            'durability' => 100,
            'max_durability' => 100,
            'effects' => self::generateArtifactEffects($type, $rarity),
            'requirements' => self::generateArtifactRequirements($type, $rarity),
            'is_server_wide' => $rarity === 'mythic',
            'is_unique' => in_array($rarity, ['legendary', 'mythic']),
        ]);
    }

    public static function generateArtifactName(string $type, string $rarity): string
    {
        $names = [
            'weapon' => ['Sword of Power', 'Blade of Destiny', 'Axe of War', 'Bow of Precision'],
            'armor' => ['Shield of Protection', 'Armor of Valor', 'Helm of Wisdom', 'Gauntlets of Strength'],
            'tool' => ['Hammer of Creation', 'Pickaxe of Mining', 'Saw of Crafting', 'Chisel of Artistry'],
            'mystical' => ['Orb of Magic', 'Crystal of Power', 'Tome of Knowledge', 'Staff of Elements'],
            'relic' => ['Ancient Relic', 'Sacred Artifact', 'Divine Object', 'Holy Symbol'],
            'crystal' => ['Power Crystal', 'Energy Gem', 'Mana Stone', 'Spirit Crystal'],
        ];

        $rarityPrefixes = [
            'common' => '',
            'uncommon' => 'Enhanced ',
            'rare' => 'Superior ',
            'epic' => 'Legendary ',
            'legendary' => 'Mythical ',
            'mythic' => 'Divine ',
        ];

        $typeNames = $names[$type] ?? ['Mysterious Object'];
        $name = $typeNames[array_rand($typeNames)];
        $prefix = $rarityPrefixes[$rarity] ?? '';

        return $prefix . $name;
    }

    public static function generateArtifactDescription(string $type, string $rarity): string
    {
        $descriptions = [
            'weapon' => 'A powerful weapon that enhances combat effectiveness.',
            'armor' => 'Protective gear that provides defensive bonuses.',
            'tool' => 'A useful tool that improves construction and crafting.',
            'mystical' => 'A mystical object with magical properties.',
            'relic' => 'An ancient artifact with historical significance.',
            'crystal' => 'A crystal that channels and amplifies energy.',
        ];

        $rarityDescriptions = [
            'common' => 'A basic item with modest benefits.',
            'uncommon' => 'An improved item with noticeable advantages.',
            'rare' => 'A valuable item with significant benefits.',
            'epic' => 'An exceptional item with powerful effects.',
            'legendary' => 'A legendary item with extraordinary powers.',
            'mythic' => 'A divine item with godlike abilities.',
        ];

        $baseDescription = $descriptions[$type] ?? 'A mysterious object with unknown properties.';
        $rarityDescription = $rarityDescriptions[$rarity] ?? '';

        return $baseDescription . ' ' . $rarityDescription;
    }

    public static function generateArtifactEffects(string $type, string $rarity): array
    {
        $effects = [];

        switch ($type) {
            case 'weapon':
                $effects[] = [
                    'type' => 'combat_bonus',
                    'value' => rand(5, 25) * (match($rarity) {
                        'common' => 1, 'uncommon' => 1.5, 'rare' => 2, 'epic' => 3, 'legendary' => 4, 'mythic' => 5
                    }),
                ];
                break;
            case 'armor':
                $effects[] = [
                    'type' => 'defense_bonus',
                    'value' => rand(5, 25) * (match($rarity) {
                        'common' => 1, 'uncommon' => 1.5, 'rare' => 2, 'epic' => 3, 'legendary' => 4, 'mythic' => 5
                    }),
                ];
                break;
            case 'tool':
                $effects[] = [
                    'type' => 'building_speed',
                    'value' => rand(10, 50) * (match($rarity) {
                        'common' => 1, 'uncommon' => 1.5, 'rare' => 2, 'epic' => 3, 'legendary' => 4, 'mythic' => 5
                    }),
                ];
                break;
            case 'mystical':
                $effects[] = [
                    'type' => 'resource_production',
                    'value' => rand(15, 75) * (match($rarity) {
                        'common' => 1, 'uncommon' => 1.5, 'rare' => 2, 'epic' => 3, 'legendary' => 4, 'mythic' => 5
                    }),
                ];
                break;
            case 'relic':
                $effects[] = [
                    'type' => 'research_speed',
                    'value' => rand(20, 100) * (match($rarity) {
                        'common' => 1, 'uncommon' => 1.5, 'rare' => 2, 'epic' => 3, 'legendary' => 4, 'mythic' => 5
                    }),
                ];
                break;
            case 'crystal':
                $effects[] = [
                    'type' => 'storage_capacity',
                    'value' => rand(25, 125) * (match($rarity) {
                        'common' => 1, 'uncommon' => 1.5, 'rare' => 2, 'epic' => 3, 'legendary' => 4, 'mythic' => 5
                    }),
                ];
                break;
        }

        return $effects;
    }

    public static function generateArtifactRequirements(string $type, string $rarity): array
    {
        $requirements = [];

        if ($rarity === 'epic' || $rarity === 'legendary' || $rarity === 'mythic') {
            $requirements[] = [
                'type' => 'level',
                'value' => match($rarity) {
                    'epic' => 20, 'legendary' => 40, 'mythic' => 60
                },
            ];
        }

        if ($rarity === 'legendary' || $rarity === 'mythic') {
            $requirements[] = [
                'type' => 'building',
                'building' => 'academy',
                'level' => match($rarity) {
                    'legendary' => 15, 'mythic' => 20
                },
            ];
        }

        return $requirements;
    }

    /**
     * Get artifacts with SmartCache optimization
     */
    public static function getCachedArtifacts($playerId = null, $filters = [])
    {
        $cacheKey = "artifacts_{$playerId}_" . md5(serialize($filters));
        
        return SmartCache::remember($cacheKey, now()->addMinutes(12), function () use ($playerId, $filters) {
            $query = static::with(['owner', 'village']);
            
            if ($playerId) {
                $query->where('owner_id', $playerId);
            }
            
            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }
            
            if (isset($filters['rarity'])) {
                $query->where('rarity', $filters['rarity']);
            }
            
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            
            if (isset($filters['active'])) {
                $query->where('status', 'active');
            }
            
            return $query->get();
        });
    }
}