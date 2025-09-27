<?php

namespace App\Services;

use App\Models\Game\Artifact;
use App\Models\Game\ArtifactEffect;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Services\QueryOptimizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SmartCache\Facades\SmartCache;

/**
 * Artifact Effect Service
 * Manages artifact effects and their application to game entities
 */
class ArtifactEffectService
{
    /**
     * Apply artifact effects to a target
     */
    public function applyArtifactEffects(Artifact $artifact, $target = null): void
    {
        try {
            DB::beginTransaction();

            foreach ($artifact->effects as $effect) {
                $this->applyEffect($artifact, $effect, $target);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to apply artifact effects', [
                'artifact_id' => $artifact->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Remove artifact effects from a target
     */
    public function removeArtifactEffects(Artifact $artifact, $target = null): void
    {
        try {
            DB::beginTransaction();

            $artifact->artifactEffects()
                ->where('is_active', true)
                ->update(['is_active' => false]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to remove artifact effects', [
                'artifact_id' => $artifact->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Apply a single effect
     */
    protected function applyEffect(Artifact $artifact, array $effect, $target = null): void
    {
        $artifactEffect = ArtifactEffect::create([
            'artifact_id' => $artifact->id,
            'effect_type' => $effect['type'],
            'target_type' => $effect['target_type'] ?? $this->getTargetType($target),
            'target_id' => $effect['target_id'] ?? $this->getTargetId($target),
            'effect_data' => $effect['data'] ?? [],
            'magnitude' => $effect['value'] ?? 0,
            'duration_type' => $effect['duration_type'] ?? 'permanent',
            'duration_hours' => $effect['duration_hours'] ?? null,
            'is_active' => true,
        ]);

        $this->executeEffect($artifactEffect, $target);
    }

    /**
     * Execute the actual effect logic
     */
    protected function executeEffect(ArtifactEffect $effect, $target = null): void
    {
        switch ($effect->effect_type) {
            case 'resource_bonus':
                $this->applyResourceBonus($effect, $target);
                break;
            case 'combat_bonus':
                $this->applyCombatBonus($effect, $target);
                break;
            case 'building_bonus':
                $this->applyBuildingBonus($effect, $target);
                break;
            case 'troop_bonus':
                $this->applyTroopBonus($effect, $target);
                break;
            case 'defense_bonus':
                $this->applyDefenseBonus($effect, $target);
                break;
            case 'attack_bonus':
                $this->applyAttackBonus($effect, $target);
                break;
            case 'speed_bonus':
                $this->applySpeedBonus($effect, $target);
                break;
            case 'production_bonus':
                $this->applyProductionBonus($effect, $target);
                break;
            case 'trade_bonus':
                $this->applyTradeBonus($effect, $target);
                break;
            case 'diplomacy_bonus':
                $this->applyDiplomacyBonus($effect, $target);
                break;
        }
    }

    /**
     * Apply resource bonus effect
     */
    protected function applyResourceBonus(ArtifactEffect $effect, $target): void
    {
        if ($target instanceof Village) {
            // Apply resource production bonus to village
            $bonus = $effect->magnitude / 100; // Convert percentage
            $target->update([
                'resource_production_bonus' => $target->resource_production_bonus + $bonus
            ]);
        }
    }

    /**
     * Apply combat bonus effect
     */
    protected function applyCombatBonus(ArtifactEffect $effect, $target): void
    {
        if ($target instanceof Village) {
            // Apply combat bonus to village
            $bonus = $effect->magnitude;
            $target->update([
                'combat_bonus' => $target->combat_bonus + $bonus
            ]);
        }
    }

    /**
     * Apply building bonus effect
     */
    protected function applyBuildingBonus(ArtifactEffect $effect, $target): void
    {
        if ($target instanceof Village) {
            // Apply building speed bonus to village
            $bonus = $effect->magnitude / 100; // Convert percentage
            $target->update([
                'building_speed_bonus' => $target->building_speed_bonus + $bonus
            ]);
        }
    }

    /**
     * Apply troop bonus effect
     */
    protected function applyTroopBonus(ArtifactEffect $effect, $target): void
    {
        if ($target instanceof Village) {
            // Apply troop training bonus to village
            $bonus = $effect->magnitude / 100; // Convert percentage
            $target->update([
                'troop_training_bonus' => $target->troop_training_bonus + $bonus
            ]);
        }
    }

    /**
     * Apply defense bonus effect
     */
    protected function applyDefenseBonus(ArtifactEffect $effect, $target): void
    {
        if ($target instanceof Village) {
            // Apply defense bonus to village
            $bonus = $effect->magnitude;
            $target->update([
                'defense_bonus' => $target->defense_bonus + $bonus
            ]);
        }
    }

    /**
     * Apply attack bonus effect
     */
    protected function applyAttackBonus(ArtifactEffect $effect, $target): void
    {
        if ($target instanceof Village) {
            // Apply attack bonus to village
            $bonus = $effect->magnitude;
            $target->update([
                'attack_bonus' => $target->attack_bonus + $bonus
            ]);
        }
    }

    /**
     * Apply speed bonus effect
     */
    protected function applySpeedBonus(ArtifactEffect $effect, $target): void
    {
        if ($target instanceof Village) {
            // Apply movement speed bonus to village
            $bonus = $effect->magnitude / 100; // Convert percentage
            $target->update([
                'movement_speed_bonus' => $target->movement_speed_bonus + $bonus
            ]);
        }
    }

    /**
     * Apply production bonus effect
     */
    protected function applyProductionBonus(ArtifactEffect $effect, $target): void
    {
        if ($target instanceof Village) {
            // Apply production bonus to village
            $bonus = $effect->magnitude / 100; // Convert percentage
            $target->update([
                'production_bonus' => $target->production_bonus + $bonus
            ]);
        }
    }

    /**
     * Apply trade bonus effect
     */
    protected function applyTradeBonus(ArtifactEffect $effect, $target): void
    {
        if ($target instanceof Village) {
            // Apply trade bonus to village
            $bonus = $effect->magnitude / 100; // Convert percentage
            $target->update([
                'trade_bonus' => $target->trade_bonus + $bonus
            ]);
        }
    }

    /**
     * Apply diplomacy bonus effect
     */
    protected function applyDiplomacyBonus(ArtifactEffect $effect, $target): void
    {
        if ($target instanceof Player) {
            // Apply diplomacy bonus to player
            $bonus = $effect->magnitude;
            $target->update([
                'diplomacy_bonus' => $target->diplomacy_bonus + $bonus
            ]);
        }
    }

    /**
     * Get target type from target object
     */
    protected function getTargetType($target): string
    {
        return match(true) {
            $target instanceof Player => 'player',
            $target instanceof Village => 'village',
            default => 'server'
        };
    }

    /**
     * Get target ID from target object
     */
    protected function getTargetId($target): ?int
    {
        return match(true) {
            $target instanceof Player => $target->id,
            $target instanceof Village => $target->id,
            default => null
        };
    }

    /**
     * Get active effects for a target with query optimization
     */
    public function getActiveEffects($target): \Illuminate\Database\Eloquent\Collection
    {
        $targetType = $this->getTargetType($target);
        $targetId = $this->getTargetId($target);

        $baseQuery = ArtifactEffect::query();
        
        $filters = [
            true => function ($q) {
                return $q->valid();
            },
            $targetType => function ($q) use ($targetType) {
                return $q->byTarget($targetType, $this->getTargetId($target));
            }
        ];

        return QueryOptimizationService::applyConditionalFilters($baseQuery, $filters)
            ->selectRaw('
                artifact_effects.*,
                (SELECT COUNT(*) FROM artifact_effects ae2 WHERE ae2.artifact_id = artifact_effects.artifact_id AND ae2.is_active = 1) as artifact_active_effects,
                (SELECT SUM(magnitude) FROM artifact_effects ae3 WHERE ae3.artifact_id = artifact_effects.artifact_id AND ae3.is_active = 1) as total_magnitude,
                (SELECT AVG(magnitude) FROM artifact_effects ae4 WHERE ae4.artifact_id = artifact_effects.artifact_id AND ae4.is_active = 1) as avg_magnitude
            ')
            ->with(['artifact:id,name,description'])
            ->get();
    }

    /**
     * Get effect magnitude for a specific effect type with query optimization
     */
    public function getEffectMagnitude($target, string $effectType): float
    {
        $targetType = $this->getTargetType($target);
        $targetId = $this->getTargetId($target);

        $result = ArtifactEffect::selectRaw('SUM(magnitude) as total_magnitude')
            ->valid()
            ->byTarget($targetType, $targetId)
            ->where('effect_type', $effectType)
            ->where('is_active', true)
            ->first();

        return $result->total_magnitude ?? 0.0;
    }

    /**
     * Clean up expired effects with query optimization
     */
    public function cleanupExpiredEffects(): int
    {
        return ArtifactEffect::selectRaw('COUNT(*) as expired_count')
            ->expired()
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    /**
     * Get artifact effect statistics with query optimization
     */
    public function getEffectStatistics(): array
    {
        return SmartCache::remember('artifact_effect_stats', now()->addMinutes(10), function () {
            $stats = ArtifactEffect::selectRaw('
                COUNT(*) as total_effects,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_effects,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_effects,
                COUNT(DISTINCT artifact_id) as unique_artifacts,
                COUNT(DISTINCT effect_type) as unique_effect_types,
                AVG(magnitude) as avg_magnitude,
                MAX(magnitude) as max_magnitude,
                MIN(magnitude) as min_magnitude,
                SUM(CASE WHEN duration_type = "permanent" THEN 1 ELSE 0 END) as permanent_effects,
                SUM(CASE WHEN duration_type != "permanent" THEN 1 ELSE 0 END) as temporary_effects
            ')
            ->first();

            return [
                'total_effects' => $stats->total_effects ?? 0,
                'active_effects' => $stats->active_effects ?? 0,
                'inactive_effects' => $stats->inactive_effects ?? 0,
                'unique_artifacts' => $stats->unique_artifacts ?? 0,
                'unique_effect_types' => $stats->unique_effect_types ?? 0,
                'avg_magnitude' => round($stats->avg_magnitude ?? 0, 2),
                'max_magnitude' => $stats->max_magnitude ?? 0,
                'min_magnitude' => $stats->min_magnitude ?? 0,
                'permanent_effects' => $stats->permanent_effects ?? 0,
                'temporary_effects' => $stats->temporary_effects ?? 0,
            ];
        });
    }

    /**
     * Get cached effects for performance
     */
    public function getCachedEffects($target): array
    {
        $targetType = $this->getTargetType($target);
        $targetId = $this->getTargetId($target);
        $cacheKey = "artifact_effects_{$targetType}_{$targetId}";

        return SmartCache::remember(
            $cacheKey,
            now()->addMinutes(15),
            function () use ($target) {
                return $this->getActiveEffects($target)->toArray();
            }
        );
    }

    /**
     * Invalidate effect cache for a target
     */
    public function invalidateEffectCache($target): void
    {
        $targetType = $this->getTargetType($target);
        $targetId = $this->getTargetId($target);
        $cacheKey = "artifact_effects_{$targetType}_{$targetId}";

        SmartCache::forget($cacheKey);
    }

}
