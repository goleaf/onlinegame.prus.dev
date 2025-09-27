<?php

namespace App\Models\Game;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use MohamedSaid\Referenceable\Traits\HasReference;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;
use SmartCache\Facades\SmartCache;

class Wonder extends Model implements Auditable
{
    use HasFactory, HasReference, AuditableTrait;

    protected $fillable = [
        'world_id',
        'name',
        'description',
        'level',
        'max_level',
        'current_owner_alliance_id',
        'construction_progress',
        'construction_started_at',
        'construction_completed_at',
        'last_attack_at',
        'defense_bonus',
        'attack_bonus',
        'resource_bonus',
        'population_bonus',
        'is_active',
        'reference_number',
    ];

    protected $casts = [
        'level' => 'integer',
        'max_level' => 'integer',
        'construction_progress' => 'integer',
        'construction_started_at' => 'datetime',
        'construction_completed_at' => 'datetime',
        'last_attack_at' => 'datetime',
        'defense_bonus' => 'decimal:2',
        'attack_bonus' => 'decimal:2',
        'resource_bonus' => 'decimal:2',
        'population_bonus' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Referenceable configuration
    protected $referenceColumn = 'reference_number';
    protected $referenceStrategy = 'template';

    protected $referenceTemplate = [
        'format' => 'WND-{YEAR}{MONTH}{SEQ}',
        'sequence_length' => 4,
    ];

    protected $referencePrefix = 'WND';

    public function world(): BelongsTo
    {
        return $this->belongsTo(World::class);
    }

    public function currentOwner(): BelongsTo
    {
        return $this->belongsTo(Alliance::class, 'current_owner_alliance_id');
    }

    public function constructionHistory(): HasMany
    {
        return $this->hasMany(WonderConstruction::class);
    }

    public function attacks(): HasMany
    {
        return $this->hasMany(WonderAttack::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByWorld($query, $worldId)
    {
        return $query->where('world_id', $worldId);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    public function scopeUnderConstruction($query)
    {
        return $query->whereNotNull('construction_started_at')
            ->whereNull('construction_completed_at');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('construction_completed_at');
    }

    public function scopeByOwner($query, $allianceId)
    {
        return $query->where('current_owner_alliance_id', $allianceId);
    }

    // Methods
    public function isUnderConstruction(): bool
    {
        return $this->construction_started_at && !$this->construction_completed_at;
    }

    public function isCompleted(): bool
    {
        return $this->construction_completed_at !== null;
    }

    public function getConstructionProgressPercentage(): float
    {
        if (!$this->isUnderConstruction()) {
            return 0;
        }

        return ($this->construction_progress / $this->max_level) * 100;
    }

    public function getRemainingConstructionTime(): ?int
    {
        if (!$this->isUnderConstruction()) {
            return null;
        }

        $totalTime = $this->getTotalConstructionTime();
        $elapsedTime = now()->diffInSeconds($this->construction_started_at);
        
        return max(0, $totalTime - $elapsedTime);
    }

    public function getTotalConstructionTime(): int
    {
        // Base time increases with level
        $baseTime = 86400; // 24 hours in seconds
        return $baseTime * $this->level;
    }

    public function canBeAttacked(): bool
    {
        return $this->is_active && 
               $this->isCompleted() && 
               (!$this->last_attack_at || $this->last_attack_at->diffInHours(now()) >= 24);
    }

    public function getDefenseStrength(): int
    {
        $baseDefense = 1000;
        $levelBonus = $this->level * 500;
        $defenseBonus = $baseDefense * ($this->defense_bonus / 100);
        
        return $baseDefense + $levelBonus + $defenseBonus;
    }

    public function getAttackStrength(): int
    {
        $baseAttack = 800;
        $levelBonus = $this->level * 400;
        $attackBonus = $baseAttack * ($this->attack_bonus / 100);
        
        return $baseAttack + $levelBonus + $attackBonus;
    }

    public function startConstruction($allianceId): bool
    {
        if ($this->isUnderConstruction()) {
            return false;
        }

        $this->update([
            'current_owner_alliance_id' => $allianceId,
            'construction_started_at' => now(),
            'construction_progress' => 0,
            'construction_completed_at' => null,
        ]);

        // Clear cache
        SmartCache::forget("wonder_stats:{$this->world_id}");

        return true;
    }

    public function completeConstruction(): bool
    {
        if (!$this->isUnderConstruction()) {
            return false;
        }

        $this->update([
            'construction_completed_at' => now(),
            'construction_progress' => $this->max_level,
        ]);

        // Clear cache
        SmartCache::forget("wonder_stats:{$this->world_id}");

        return true;
    }

    public function attack($attackerAllianceId, $attackStrength): array
    {
        if (!$this->canBeAttacked()) {
            return [
                'success' => false,
                'message' => 'Wonder cannot be attacked at this time',
            ];
        }

        $defenseStrength = $this->getDefenseStrength();
        $success = $attackStrength > $defenseStrength;

        // Record the attack
        WonderAttack::create([
            'wonder_id' => $this->id,
            'attacker_alliance_id' => $attackerAllianceId,
            'defender_alliance_id' => $this->current_owner_alliance_id,
            'attack_strength' => $attackStrength,
            'defense_strength' => $defenseStrength,
            'success' => $success,
            'occurred_at' => now(),
        ]);

        if ($success) {
            // Wonder changes ownership
            $this->update([
                'current_owner_alliance_id' => $attackerAllianceId,
                'last_attack_at' => now(),
            ]);

            // Clear cache
            SmartCache::forget("wonder_stats:{$this->world_id}");
        }

        return [
            'success' => $success,
            'message' => $success ? 'Wonder captured!' : 'Attack failed',
            'attack_strength' => $attackStrength,
            'defense_strength' => $defenseStrength,
        ];
    }

    public static function getWorldWonderStats($worldId): array
    {
        return SmartCache::remember("wonder_stats:{$worldId}", 300, function () use ($worldId) {
            $wonders = self::byWorld($worldId)->active()->get();
            
            return [
                'total_wonders' => $wonders->count(),
                'completed_wonders' => $wonders->where('construction_completed_at', '!=', null)->count(),
                'under_construction' => $wonders->where('construction_started_at', '!=', null)
                    ->where('construction_completed_at', null)->count(),
                'average_level' => $wonders->avg('level'),
                'highest_level' => $wonders->max('level'),
                'total_construction_progress' => $wonders->sum('construction_progress'),
            ];
        });
    }

    public static function getTopWonderOwners($worldId, $limit = 10): array
    {
        return SmartCache::remember("top_wonder_owners:{$worldId}", 600, function () use ($worldId, $limit) {
            return self::byWorld($worldId)
                ->active()
                ->completed()
                ->with('currentOwner')
                ->get()
                ->groupBy('current_owner_alliance_id')
                ->map(function ($wonders, $allianceId) {
                    return [
                        'alliance_id' => $allianceId,
                        'alliance_name' => $wonders->first()->currentOwner->name ?? 'Unknown',
                        'wonder_count' => $wonders->count(),
                        'total_levels' => $wonders->sum('level'),
                        'average_level' => $wonders->avg('level'),
                    ];
                })
                ->sortByDesc('wonder_count')
                ->take($limit)
                ->values()
                ->toArray();
        });
    }
}
