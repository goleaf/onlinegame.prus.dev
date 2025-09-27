<?php

namespace App\Services;

use App\Models\Game\Building;
use App\Models\Game\Village;
use App\Services\GeographicService;
use Illuminate\Support\Facades\Log;

class DefenseCalculationService
{
    /**
     * Calculate total defensive bonus for a village
     */
    public function calculateDefensiveBonus(Village $village): float
    {
        $totalBonus = 0;

        // Get all buildings for the village
        $buildings = $village->buildings()->with('buildingType')->get();

        foreach ($buildings as $building) {
            $buildingType = $building->buildingType;
            $level = $building->level;

            $bonus = $this->getBuildingDefenseBonus($buildingType->key, $level);
            $totalBonus += $bonus;
        }

        // Cap defensive bonus at 50%
        return min($totalBonus, 0.5);
    }

    /**
     * Get defense bonus for a specific building type and level
     */
    public function getBuildingDefenseBonus(string $buildingKey, int $level): float
    {
        return match ($buildingKey) {
            'wall' => $level * 0.02,  // 2% per level
            'watchtower' => $level * 0.015,  // 1.5% per level
            'trap' => $level * 0.01,  // 1% per level
            'rally_point' => $level * 0.005,  // 0.5% per level
            default => 0
        };
    }

    /**
     * Calculate spy defense percentage for a village
     */
    public function calculateSpyDefense(Village $village): int
    {
        $spyDefense = 0;

        // Get trap level for spy defense
        $trap = $village
            ->buildings()
            ->whereHas('buildingType', function ($query) {
                $query->where('key', 'trap');
            })
            ->first();

        if ($trap) {
            // Each trap level provides 5% chance to catch spies
            $spyDefense = $trap->level * 5;
        }

        return min($spyDefense, 100);  // Cap at 100%
    }

    /**
     * Calculate resource protection bonus
     */
    public function calculateResourceProtection(Village $village): float
    {
        $protection = 0;

        // Get warehouse and granary levels
        $warehouse = $village
            ->buildings()
            ->whereHas('buildingType', function ($query) {
                $query->where('key', 'warehouse');
            })
            ->first();

        $granary = $village
            ->buildings()
            ->whereHas('buildingType', function ($query) {
                $query->where('key', 'granary');
            })
            ->first();

        // Each level provides 1% resource protection
        if ($warehouse) {
            $protection += $warehouse->level * 0.01;
        }

        if ($granary) {
            $protection += $granary->level * 0.01;
        }

        return min($protection, 0.3);  // Cap at 30%
    }

    /**
     * Calculate troop training speed bonus
     */
    public function calculateTrainingSpeedBonus(Village $village): float
    {
        $bonus = 0;

        // Get barracks level
        $barracks = $village
            ->buildings()
            ->whereHas('buildingType', function ($query) {
                $query->where('key', 'barracks');
            })
            ->first();

        if ($barracks) {
            // Each level provides 2% training speed bonus
            $bonus = $barracks->level * 0.02;
        }

        return min($bonus, 0.4);  // Cap at 40%
    }

    /**
     * Calculate resource production bonus
     */
    public function calculateProductionBonus(Village $village, string $resourceType): float
    {
        $bonus = 0;

        $buildingKey = match ($resourceType) {
            'wood' => 'woodcutter',
            'clay' => 'clay_pit',
            'iron' => 'iron_mine',
            'crop' => 'crop_field',
            default => null
        };

        if ($buildingKey) {
            $building = $village
                ->buildings()
                ->whereHas('buildingType', function ($query) use ($buildingKey) {
                    $query->where('key', $buildingKey);
                })
                ->first();

            if ($building) {
                // Each level provides 3% production bonus
                $bonus = $building->level * 0.03;
            }
        }

        return min($bonus, 0.6);  // Cap at 60%
    }

    /**
     * Get comprehensive defense report for a village
     */
    public function getDefenseReport(Village $village): array
    {
        return [
            'defensive_bonus' => $this->calculateDefensiveBonus($village),
            'spy_defense' => $this->calculateSpyDefense($village),
            'resource_protection' => $this->calculateResourceProtection($village),
            'training_speed_bonus' => $this->calculateTrainingSpeedBonus($village),
            'building_details' => $this->getBuildingDefenseDetails($village),
        ];
    }

    /**
     * Get detailed defense information for each building
     */
    private function getBuildingDefenseDetails(Village $village): array
    {
        $details = [];
        $buildings = $village->buildings()->with('buildingType')->get();

        foreach ($buildings as $building) {
            $buildingType = $building->buildingType;
            $level = $building->level;

            $defenseBonus = $this->getBuildingDefenseBonus($buildingType->key, $level);

            if ($defenseBonus > 0) {
                $details[] = [
                    'building_name' => $buildingType->name,
                    'building_key' => $buildingType->key,
                    'level' => $level,
                    'defense_bonus' => $defenseBonus,
                    'defense_percentage' => $defenseBonus * 100,
                ];
            }
        }

        return $details;
    }

    /**
     * Calculate if a spy mission should succeed
     */
    public function shouldSpySucceed(Village $targetVillage): bool
    {
        $spyDefense = $this->calculateSpyDefense($targetVillage);
        $randomChance = rand(1, 100);

        return $randomChance > $spyDefense;
    }

    /**
     * Get recommended defense improvements for a village
     */
    public function getDefenseRecommendations(Village $village): array
    {
        $recommendations = [];
        $currentBonus = $this->calculateDefensiveBonus($village);

        // Check wall level
        $wall = $village
            ->buildings()
            ->whereHas('buildingType', function ($query) {
                $query->where('key', 'wall');
            })
            ->first();

        if (!$wall || $wall->level < 10) {
            $recommendations[] = [
                'type' => 'wall',
                'priority' => 'high',
                'message' => 'Upgrade your wall to level 10+ for better defense',
                'current_level' => $wall?->level ?? 0,
                'recommended_level' => 10,
            ];
        }

        // Check trap level
        $trap = $village
            ->buildings()
            ->whereHas('buildingType', function ($query) {
                $query->where('key', 'trap');
            })
            ->first();

        if (!$trap || $trap->level < 5) {
            $recommendations[] = [
                'type' => 'trap',
                'priority' => 'medium',
                'message' => 'Build traps to catch enemy spies',
                'current_level' => $trap?->level ?? 0,
                'recommended_level' => 5,
            ];
        }

        // Check watchtower
        $watchtower = $village
            ->buildings()
            ->whereHas('buildingType', function ($query) {
                $query->where('key', 'watchtower');
            })
            ->first();

        if (!$watchtower || $watchtower->level < 3) {
            $recommendations[] = [
                'type' => 'watchtower',
                'priority' => 'medium',
                'message' => 'Build a watchtower for additional defense bonus',
                'current_level' => $watchtower?->level ?? 0,
                'recommended_level' => 3,
            ];
        }

        return $recommendations;
    }
}
