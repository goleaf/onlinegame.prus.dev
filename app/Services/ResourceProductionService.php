<?php

namespace App\Services;

class ResourceProductionService
{
    public function calculateResourceProduction($village)
    {
        $resources = $village->resources;
        $buildings = $village->buildings;

        $productionRates = [
            'wood' => 0,
            'clay' => 0,
            'iron' => 0,
            'crop' => 0,
        ];

        foreach ($buildings as $building) {
            $buildingType = $building->buildingType;
            $level = $building->level;

            if ($buildingType->production) {
                $production = is_string($buildingType->production)
                    ? json_decode($buildingType->production, true)
                    : $buildingType->production;

                if (is_array($production)) {
                    foreach ($production as $resource => $baseRate) {
                        $productionRates[$resource] += $this->calculateProductionRate($baseRate, $level);
                    }
                }
            }
        }

        return $productionRates;
    }

    public function calculateProductionRate($baseRate, $level)
    {
        // Production increases by 10% per level
        return $baseRate * pow(1.1, $level - 1);
    }

    public function updateVillageResources($village)
    {
        $productionRates = $this->calculateResourceProduction($village);
        $resources = $village->resources;

        foreach ($resources as $resource) {
            $timeSinceLastUpdate = now()->diffInSeconds($resource->last_updated);
            $production = $productionRates[$resource->type] * $timeSinceLastUpdate;

            $newAmount = min(
                $resource->amount + $production,
                $resource->storage_capacity
            );

            if ($newAmount !== $resource->amount) {
                $resource->update([
                    'amount' => $newAmount,
                    'last_updated' => now(),
                ]);
            }
        }
    }

    public function calculateStorageCapacity($village)
    {
        $buildings = $village->buildings;
        $capacities = [
            'wood' => 1000,
            'clay' => 1000,
            'iron' => 1000,
            'crop' => 1000,
        ];

        foreach ($buildings as $building) {
            $buildingType = $building->buildingType;
            $level = $building->level;

            if ($buildingType->key === 'warehouse') {
                $capacities['wood'] += $this->calculateStorageCapacityForLevel($level);
                $capacities['clay'] += $this->calculateStorageCapacityForLevel($level);
                $capacities['iron'] += $this->calculateStorageCapacityForLevel($level);
            } elseif ($buildingType->key === 'granary') {
                $capacities['crop'] += $this->calculateStorageCapacityForLevel($level);
            }
        }

        return $capacities;
    }

    private function calculateStorageCapacityForLevel($level)
    {
        // Storage capacity increases by 1000 per level
        return 1000 * $level;
    }

    public function updateStorageCapacities($village)
    {
        $capacities = $this->calculateStorageCapacity($village);
        $resources = $village->resources;

        foreach ($resources as $resource) {
            $resource->update([
                'storage_capacity' => $capacities[$resource->type],
            ]);
        }
    }

    public function canAfford($village, $costs)
    {
        $resources = $village->resources;

        foreach ($costs as $resource => $amount) {
            $resourceModel = $resources->where('type', $resource)->first();
            if (!$resourceModel || $resourceModel->amount < $amount) {
                return false;
            }
        }

        return true;
    }

    public function spendResources($village, $costs)
    {
        if (!$this->canAfford($village, $costs)) {
            return false;
        }

        $resources = $village->resources;

        foreach ($costs as $resource => $amount) {
            $resourceModel = $resources->where('type', $resource)->first();
            if ($resourceModel) {
                $resourceModel->decrement('amount', $amount);
            }
        }

        return true;
    }

    public function addResources($village, $amounts)
    {
        $resources = $village->resources;

        foreach ($amounts as $resource => $amount) {
            $resourceModel = $resources->where('type', $resource)->first();
            if ($resourceModel) {
                $newAmount = min(
                    $resourceModel->amount + $amount,
                    $resourceModel->storage_capacity
                );
                $resourceModel->update(['amount' => $newAmount]);
            }
        }
    }
}
