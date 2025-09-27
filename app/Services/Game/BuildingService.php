<?php

namespace App\Services\Game;

use App\Models\Game\Village;
use App\Models\Game\Building;
use App\Models\Game\BuildingType;
use App\Models\Game\Resource;
use Illuminate\Support\Facades\DB;
use SmartCache\Facades\SmartCache;

class BuildingService
{
    public function __construct(
        private ResourceService $resourceService
    ) {}

    /**
     * Check if building requirements are met
     */
    public function meetsRequirements(Village $village, array $requirements): bool
    {
        if (empty($requirements)) {
            return true;
        }

        foreach ($requirements as $requirement => $value) {
            switch ($requirement) {
                case 'building_level':
                    foreach ($value as $buildingType => $requiredLevel) {
                        $building = $village->buildings()->where('building_type', $buildingType)->first();
                        if (!$building || $building->level < $requiredLevel) {
                            return false;
                        }
                    }
                    break;
                case 'village_level':
                    if ($village->level < $value) {
                        return false;
                    }
                    break;
                case 'population':
                    if ($village->population < $value) {
                        return false;
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Get building costs
     */
    public function getBuildingCosts(BuildingType $buildingType, int $currentLevel): array
    {
        $costs = $buildingType->costs ?? [];
        $levelCosts = [];

        foreach ($costs as $resource => $baseCost) {
            // Cost increases with level
            $levelCosts[$resource] = $baseCost * pow(1.5, $currentLevel);
        }

        return $levelCosts;
    }

    /**
     * Get building construction time
     */
    public function getConstructionTime(BuildingType $buildingType, int $currentLevel): int
    {
        $baseTime = $buildingType->construction_time ?? 60; // 1 minute base
        return (int) ($baseTime * pow(1.2, $currentLevel));
    }

    /**
     * Start building construction
     */
    public function startConstruction(Village $village, BuildingType $buildingType): array
    {
        // Check if building already exists
        $existingBuilding = $village->buildings()->where('building_type', $buildingType->key)->first();
        $currentLevel = $existingBuilding ? $existingBuilding->level : 0;

        // Check requirements
        if (!$this->meetsRequirements($village, $buildingType->requirements)) {
            return [
                'success' => false,
                'message' => 'Building requirements not met',
                'requirements' => $buildingType->requirements,
            ];
        }

        // Calculate costs
        $costs = $this->getBuildingCosts($buildingType, $currentLevel);

        // Check if village has enough resources
        if (!$this->resourceService->hasEnoughResources($village, $costs)) {
            return [
                'success' => false,
                'message' => 'Insufficient resources for construction',
                'required' => $costs,
                'available' => $this->resourceService->getVillageResources($village),
            ];
        }

        // Calculate construction time
        $constructionTime = $this->getConstructionTime($buildingType, $currentLevel);

        DB::transaction(function () use ($village, $buildingType, $currentLevel, $costs, $constructionTime) {
            // Deduct resources
            $this->resourceService->deductResources($village, $costs);

            // Create or update building
            if ($existingBuilding) {
                $existingBuilding->update([
                    'level' => $currentLevel + 1,
                    'construction_started_at' => now(),
                    'construction_completed_at' => now()->addSeconds($constructionTime),
                ]);
            } else {
                $village->buildings()->create([
                    'building_type' => $buildingType->key,
                    'level' => 1,
                    'construction_started_at' => now(),
                    'construction_completed_at' => now()->addSeconds($constructionTime),
                ]);
            }
        });

        // Clear cache
        $this->clearBuildingCache($village);

        return [
            'success' => true,
            'message' => 'Construction started successfully',
            'construction_time' => $constructionTime,
            'completion_time' => now()->addSeconds($constructionTime),
        ];
    }

    /**
     * Complete building construction
     */
    public function completeConstruction(Village $village): array
    {
        $completedBuildings = Building::where('village_id', $village->id)
            ->whereNotNull('construction_started_at')
            ->whereNull('construction_completed_at')
            ->where('construction_completed_at', '<=', now())
            ->get();

        if ($completedBuildings->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No completed construction found',
            ];
        }

        $results = [];

        foreach ($completedBuildings as $building) {
            $building->update([
                'construction_started_at' => null,
                'construction_completed_at' => null,
            ]);

            $results[] = [
                'building_type' => $building->building_type,
                'level' => $building->level,
                'completed_at' => now(),
            ];
        }

        // Clear cache
        $this->clearBuildingCache($village);

        return [
            'success' => true,
            'message' => 'Construction completed successfully',
            'results' => $results,
        ];
    }

    /**
     * Demolish building
     */
    public function demolishBuilding(Village $village, Building $building): array
    {
        if ($building->village_id !== $village->id) {
            return [
                'success' => false,
                'message' => 'Building does not belong to this village',
            ];
        }

        if ($building->level <= 0) {
            return [
                'success' => false,
                'message' => 'Building is already at minimum level',
            ];
        }

        // Calculate refund (50% of construction costs)
        $buildingType = BuildingType::where('key', $building->building_type)->first();
        if ($buildingType) {
            $costs = $this->getBuildingCosts($buildingType, $building->level - 1);
            $refund = [];
            foreach ($costs as $resource => $cost) {
                $refund[$resource] = (int) ($cost * 0.5);
            }

            // Refund resources
            $this->resourceService->addResources($village, $refund);
        }

        // Demolish building
        $building->decrement('level');

        // Clear cache
        $this->clearBuildingCache($village);

        return [
            'success' => true,
            'message' => 'Building demolished successfully',
            'refund' => $refund ?? [],
        ];
    }

    /**
     * Get available buildings for construction
     */
    public function getAvailableBuildings(Village $village): array
    {
        $cacheKey = "available_buildings:{$village->id}";

        return SmartCache::remember($cacheKey, 600, function () use ($village) {
            $buildingTypes = BuildingType::active()->get();
            $available = [];

            foreach ($buildingTypes as $buildingType) {
                if ($this->canBuild($village, $buildingType)) {
                    $existingBuilding = $village->buildings()->where('building_type', $buildingType->key)->first();
                    $currentLevel = $existingBuilding ? $existingBuilding->level : 0;

                    $available[] = [
                        'id' => $buildingType->id,
                        'key' => $buildingType->key,
                        'name' => $buildingType->name,
                        'description' => $buildingType->description,
                        'current_level' => $currentLevel,
                        'max_level' => $buildingType->max_level,
                        'costs' => $this->getBuildingCosts($buildingType, $currentLevel),
                        'construction_time' => $this->getConstructionTime($buildingType, $currentLevel),
                        'requirements' => $buildingType->requirements,
                        'effects' => $buildingType->effects,
                    ];
                }
            }

            return $available;
        });
    }

    /**
     * Get village buildings
     */
    public function getVillageBuildings(Village $village): array
    {
        $cacheKey = "village_buildings:{$village->id}";

        return SmartCache::remember($cacheKey, 300, function () use ($village) {
            return $village->buildings()->with('buildingType')->get()->map(function ($building) {
                return [
                    'id' => $building->id,
                    'building_type' => $building->building_type,
                    'level' => $building->level,
                    'construction_started_at' => $building->construction_started_at,
                    'construction_completed_at' => $building->construction_completed_at,
                    'is_under_construction' => $building->construction_started_at && !$building->construction_completed_at,
                    'remaining_time' => $building->construction_completed_at ? 
                        max(0, $building->construction_completed_at->diffInSeconds(now())) : null,
                ];
            })->toArray();
        });
    }

    /**
     * Get building statistics
     */
    public function getBuildingStatistics(Village $village): array
    {
        $cacheKey = "building_stats:{$village->id}";

        return SmartCache::remember($cacheKey, 600, function () use ($village) {
            $buildings = $village->buildings()->with('buildingType')->get();

            return [
                'total_buildings' => $buildings->count(),
                'total_levels' => $buildings->sum('level'),
                'average_level' => $buildings->count() > 0 ? $buildings->avg('level') : 0,
                'under_construction' => $buildings->where('construction_started_at', '!=', null)
                    ->where('construction_completed_at', null)->count(),
                'building_types' => $buildings->groupBy('building_type')->map(function ($group) {
                    return [
                        'type' => $group->first()->building_type,
                        'level' => $group->sum('level'),
                        'count' => $group->count(),
                    ];
                })->values()->toArray(),
            ];
        });
    }

    /**
     * Check if building can be constructed
     */
    private function canBuild(Village $village, BuildingType $buildingType): bool
    {
        // Check if building is active
        if (!$buildingType->is_active) {
            return false;
        }

        // Check requirements
        if (!$this->meetsRequirements($village, $buildingType->requirements)) {
            return false;
        }

        // Check max level
        $existingBuilding = $village->buildings()->where('building_type', $buildingType->key)->first();
        if ($existingBuilding && $existingBuilding->level >= $buildingType->max_level) {
            return false;
        }

        return true;
    }

    /**
     * Clear building cache
     */
    private function clearBuildingCache(Village $village): void
    {
        SmartCache::forget("available_buildings:{$village->id}");
        SmartCache::forget("village_buildings:{$village->id}");
        SmartCache::forget("building_stats:{$village->id}");
    }
}
