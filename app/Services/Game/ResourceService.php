<?php

namespace App\Services\Game;

use App\Models\Game\Village;
use App\Models\Game\Resource;
use App\Models\Game\Building;
use Illuminate\Support\Facades\DB;
use SmartCache\Facades\SmartCache;

class ResourceService
{
    /**
     * Get village resources
     */
    public function getVillageResources(Village $village): array
    {
        $cacheKey = "village_resources:{$village->id}";

        return SmartCache::remember($cacheKey, 300, function () use ($village) {
            $resources = $village->resources()->get();
            $resourceArray = [];

            foreach ($resources as $resource) {
                $resourceArray[$resource->resource_type] = $resource->amount;
            }

            return $resourceArray;
        });
    }

    /**
     * Check if village has enough resources
     */
    public function hasEnoughResources(Village $village, array $requiredResources): bool
    {
        $currentResources = $this->getVillageResources($village);

        foreach ($requiredResources as $resourceType => $requiredAmount) {
            $currentAmount = $currentResources[$resourceType] ?? 0;
            if ($currentAmount < $requiredAmount) {
                return false;
            }
        }

        return true;
    }

    /**
     * Deduct resources from village
     */
    public function deductResources(Village $village, array $resources): bool
    {
        if (!$this->hasEnoughResources($village, $resources)) {
            return false;
        }

        DB::transaction(function () use ($village, $resources) {
            foreach ($resources as $resourceType => $amount) {
                $resource = $village->resources()->where('resource_type', $resourceType)->first();
                if ($resource) {
                    $resource->decrement('amount', $amount);
                }
            }
        });

        // Clear cache
        $this->clearResourceCache($village);

        return true;
    }

    /**
     * Add resources to village
     */
    public function addResources(Village $village, array $resources): void
    {
        DB::transaction(function () use ($village, $resources) {
            foreach ($resources as $resourceType => $amount) {
                $resource = $village->resources()->where('resource_type', $resourceType)->first();
                if ($resource) {
                    $resource->increment('amount', $amount);
                } else {
                    $village->resources()->create([
                        'resource_type' => $resourceType,
                        'amount' => $amount,
                    ]);
                }
            }
        });

        // Clear cache
        $this->clearResourceCache($village);
    }

    /**
     * Set resources for village
     */
    public function setResources(Village $village, array $resources): void
    {
        DB::transaction(function () use ($village, $resources) {
            foreach ($resources as $resourceType => $amount) {
                $resource = $village->resources()->where('resource_type', $resourceType)->first();
                if ($resource) {
                    $resource->update(['amount' => $amount]);
                } else {
                    $village->resources()->create([
                        'resource_type' => $resourceType,
                        'amount' => $amount,
                    ]);
                }
            }
        });

        // Clear cache
        $this->clearResourceCache($village);
    }

    /**
     * Calculate resource production
     */
    public function calculateResourceProduction(Village $village): array
    {
        $cacheKey = "resource_production:{$village->id}";

        return SmartCache::remember($cacheKey, 600, function () use ($village) {
            $production = [
                'wood' => 0,
                'clay' => 0,
                'iron' => 0,
                'crop' => 0,
            ];

            $buildings = $village->buildings()->with('buildingType')->get();

            foreach ($buildings as $building) {
                $buildingType = $building->buildingType;
                if ($buildingType && $buildingType->resource_production) {
                    foreach ($buildingType->resource_production as $resourceType => $baseProduction) {
                        $production[$resourceType] += $baseProduction * $building->level;
                    }
                }
            }

            // Apply bonuses
            $production = $this->applyProductionBonuses($village, $production);

            return $production;
        });
    }

    /**
     * Apply production bonuses
     */
    private function applyProductionBonuses(Village $village, array $production): array
    {
        // Get building bonuses
        $bonuses = $this->getBuildingBonuses($village);

        foreach ($production as $resourceType => $amount) {
            $bonus = $bonuses[$resourceType] ?? 0;
            $production[$resourceType] = $amount * (1 + $bonus / 100);
        }

        return $production;
    }

    /**
     * Get building bonuses
     */
    private function getBuildingBonuses(Village $village): array
    {
        $bonuses = [
            'wood' => 0,
            'clay' => 0,
            'iron' => 0,
            'crop' => 0,
        ];

        $buildings = $village->buildings()->with('buildingType')->get();

        foreach ($buildings as $building) {
            $buildingType = $building->buildingType;
            if ($buildingType && $buildingType->resource_bonus) {
                foreach ($buildingType->resource_bonus as $resourceType => $bonus) {
                    $bonuses[$resourceType] += $bonus * $building->level;
                }
            }
        }

        return $bonuses;
    }

    /**
     * Update resource production
     */
    public function updateResourceProduction(Village $village): void
    {
        $production = $this->calculateResourceProduction($village);
        $currentResources = $this->getVillageResources($village);

        // Add production to current resources
        foreach ($production as $resourceType => $amount) {
            $currentAmount = $currentResources[$resourceType] ?? 0;
            $newAmount = $currentAmount + $amount;
            
            $this->setResources($village, [$resourceType => $newAmount]);
        }
    }

    /**
     * Get resource capacity
     */
    public function getResourceCapacity(Village $village): array
    {
        $cacheKey = "resource_capacity:{$village->id}";

        return SmartCache::remember($cacheKey, 600, function () use ($village) {
            $capacity = [
                'wood' => 1000,
                'clay' => 1000,
                'iron' => 1000,
                'crop' => 1000,
            ];

            $buildings = $village->buildings()->with('buildingType')->get();

            foreach ($buildings as $building) {
                $buildingType = $building->buildingType;
                if ($buildingType && $buildingType->resource_capacity) {
                    foreach ($buildingType->resource_capacity as $resourceType => $baseCapacity) {
                        $capacity[$resourceType] += $baseCapacity * $building->level;
                    }
                }
            }

            return $capacity;
        });
    }

    /**
     * Check if resources are at capacity
     */
    public function isAtCapacity(Village $village, string $resourceType): bool
    {
        $currentResources = $this->getVillageResources($village);
        $capacity = $this->getResourceCapacity($village);

        $currentAmount = $currentResources[$resourceType] ?? 0;
        $maxCapacity = $capacity[$resourceType] ?? 1000;

        return $currentAmount >= $maxCapacity;
    }

    /**
     * Get resource statistics
     */
    public function getResourceStatistics(Village $village): array
    {
        $cacheKey = "resource_stats:{$village->id}";

        return SmartCache::remember($cacheKey, 600, function () use ($village) {
            $currentResources = $this->getVillageResources($village);
            $production = $this->calculateResourceProduction($village);
            $capacity = $this->getResourceCapacity($village);

            $stats = [];
            foreach (['wood', 'clay', 'iron', 'crop'] as $resourceType) {
                $stats[$resourceType] = [
                    'current' => $currentResources[$resourceType] ?? 0,
                    'production' => $production[$resourceType] ?? 0,
                    'capacity' => $capacity[$resourceType] ?? 1000,
                    'percentage' => $capacity[$resourceType] > 0 ? 
                        (($currentResources[$resourceType] ?? 0) / $capacity[$resourceType]) * 100 : 0,
                ];
            }

            return $stats;
        });
    }

    /**
     * Clear resource cache
     */
    private function clearResourceCache(Village $village): void
    {
        SmartCache::forget("village_resources:{$village->id}");
        SmartCache::forget("resource_production:{$village->id}");
        SmartCache::forget("resource_capacity:{$village->id}");
        SmartCache::forget("resource_stats:{$village->id}");
    }
}
