<?php

namespace App\Services;

use App\Models\Game\Building;
use App\Models\Game\BuildingQueue;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\Log;

class BuildingService
{
    protected $rabbitMQ;

    public function __construct()
    {
        $this->rabbitMQ = new RabbitMQService();
    }

    public function canBuild($village, $buildingType, $level = 1)
    {
        // Check if building exists and can be upgraded
        $existingBuilding = $village
            ->buildings()
            ->where('building_type_id', $buildingType->id)
            ->first();

        if ($existingBuilding) {
            // Check if already at max level
            if ($existingBuilding->level >= $buildingType->max_level) {
                return false;
            }

            // Check if already upgrading
            if ($existingBuilding->upgrade_started_at) {
                return false;
            }
        }

        // Check requirements
        if (!$this->checkRequirements($village, $buildingType, $level)) {
            return false;
        }

        // Check resources
        $costs = $this->calculateUpgradeCost($buildingType, $level);
        $resourceService = new ResourceProductionService();

        return $resourceService->canAfford($village, $costs);
    }

    public function checkRequirements($village, $buildingType, $level)
    {
        $requirements = $buildingType->requirements ?? [];

        foreach ($requirements as $buildingKey => $requiredLevel) {
            $building = $village
                ->buildings()
                ->whereHas('buildingType', function ($query) use ($buildingKey) {
                    $query->where('key', $buildingKey);
                })
                ->first();

            if (!$building || $building->level < $requiredLevel) {
                return false;
            }
        }

        return true;
    }

    public function calculateUpgradeCost($buildingType, $level)
    {
        $baseCosts = $buildingType->costs ?? [];
        $costs = [];

        foreach ($baseCosts as $resource => $baseCost) {
            // Cost increases by 50% per level
            $costs[$resource] = $baseCost * pow(1.5, $level - 1);
        }

        return $costs;
    }

    public function calculateUpgradeTime($buildingType, $level)
    {
        $baseTime = 60;  // Base time in seconds
        $levelMultiplier = pow(1.5, $level - 1);

        return $baseTime * $levelMultiplier;
    }

    public function startUpgrade($village, $buildingType, $level = 1)
    {
        if (!$this->canBuild($village, $buildingType, $level)) {
            return false;
        }

        try {
            $costs = $this->calculateUpgradeCost($buildingType, $level);
            $upgradeTime = $this->calculateUpgradeTime($buildingType, $level);

            // Deduct resources
            $resourceService = new ResourceProductionService();
            $resourceService->spendResources($village, $costs);

            // Create or update building
            $building = $village
                ->buildings()
                ->where('building_type_id', $buildingType->id)
                ->first();

            if ($building) {
                $building->update([
                    'level' => $level,
                    'upgrade_started_at' => now(),
                ]);
            } else {
                $building = $village->buildings()->create([
                    'building_type_id' => $buildingType->id,
                    'name' => $buildingType->name,
                    'level' => $level,
                    'x' => 0,
                    'y' => 0,
                    'upgrade_started_at' => now(),
                ]);
            }

            // Create building queue
            BuildingQueue::create([
                'village_id' => $village->id,
                'building_id' => $building->id,
                'building_type_id' => $buildingType->id,
                'target_level' => $level,
                'started_at' => now(),
                'completed_at' => now()->addSeconds($upgradeTime),
                'costs' => $costs,
                'status' => 'in_progress',
            ]);

            // Publish building start event to RabbitMQ
            $this->rabbitMQ->publishPlayerAction(
                $village->player_id,
                'building_started',
                [
                    'building_name' => $buildingType->name,
                    'level' => $level,
                    'village_id' => $village->id,
                    'completion_time' => now()->addSeconds($upgradeTime)->toISOString(),
                ]
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to start building upgrade: ' . $e->getMessage());

            return false;
        }
    }

    public function completeUpgrade($building)
    {
        try {
            $building->update([
                'level' => $building->target_level,
                'upgrade_started_at' => null,
            ]);

            // Update storage capacities if needed
            if ($building->buildingType->key === 'warehouse' || $building->buildingType->key === 'granary') {
                $resourceService = new ResourceProductionService();
                $resourceService->updateStorageCapacities($building->village);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to complete building upgrade: ' . $e->getMessage());

            return false;
        }
    }

    public function cancelUpgrade($building)
    {
        try {
            $building->update(['upgrade_started_at' => null]);

            // Refund resources (50% refund)
            $costs = $building->costs ?? [];
            $refundCosts = [];

            foreach ($costs as $resource => $cost) {
                $refundCosts[$resource] = $cost * 0.5;
            }

            $resourceService = new ResourceProductionService();
            $resourceService->addResources($building->village, $refundCosts);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cancel building upgrade: ' . $e->getMessage());

            return false;
        }
    }

    public function getBuildingInfo($village, $buildingType)
    {
        $building = $village
            ->buildings()
            ->where('building_type_id', $buildingType->id)
            ->first();

        $info = [
            'exists' => $building !== null,
            'level' => $building ? $building->level : 0,
            'max_level' => $buildingType->max_level,
            'can_upgrade' => $this->canBuild($village, $buildingType, ($building ? $building->level : 0) + 1),
            'upgrade_cost' => $this->calculateUpgradeCost($buildingType, ($building ? $building->level : 0) + 1),
            'upgrade_time' => $this->calculateUpgradeTime($buildingType, ($building ? $building->level : 0) + 1),
            'is_upgrading' => $building && $building->upgrade_started_at,
            'upgrade_progress' => $building && $building->upgrade_started_at
                ? $this->calculateUpgradeProgress($building)
                : 0,
        ];

        return $info;
    }

    private function calculateUpgradeProgress($building)
    {
        if (!$building->upgrade_started_at) {
            return 0;
        }

        $startTime = $building->upgrade_started_at;
        $totalTime = $this->calculateUpgradeTime($building->buildingType, $building->level);
        $elapsedTime = now()->diffInSeconds($startTime);

        return min(100, ($elapsedTime / $totalTime) * 100);
    }
}
