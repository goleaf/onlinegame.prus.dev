<?php

namespace App\Services\Game;

use App\Models\Game\TrainingQueue;
use App\Models\Game\UnitType;
use App\Models\Game\Village;
use App\Models\Game\Resource;
use App\Models\Game\Building;
use Illuminate\Support\Facades\DB;
use SmartCache\Facades\SmartCache;

class UnitTrainingService
{
    public function __construct(
        private ResourceService $resourceService,
        private BuildingService $buildingService
    ) {}

    /**
     * Start training units for a village
     */
    public function startTraining(Village $village, UnitType $unitType, int $count): array
    {
        // Validate training requirements
        $validation = $this->validateTraining($village, $unitType, $count);
        if (!$validation['valid']) {
            return $validation;
        }

        // Calculate costs
        $costs = $this->calculateTrainingCosts($unitType, $count);
        
        // Check if village has enough resources
        if (!$this->resourceService->hasEnoughResources($village, $costs)) {
            return [
                'success' => false,
                'message' => 'Insufficient resources for training',
                'required' => $costs,
                'available' => $this->resourceService->getVillageResources($village),
            ];
        }

        // Calculate training time
        $trainingTime = $this->calculateTrainingTime($village, $unitType, $count);

        // Create training queue
        $trainingQueue = TrainingQueue::create([
            'village_id' => $village->id,
            'unit_type_id' => $unitType->id,
            'count' => $count,
            'started_at' => now(),
            'completed_at' => now()->addSeconds($trainingTime),
            'costs' => $costs,
            'status' => 'training',
        ]);

        // Deduct resources
        $this->resourceService->deductResources($village, $costs);

        // Clear cache
        $this->clearTrainingCache($village);

        return [
            'success' => true,
            'message' => "Training started for {$count} {$unitType->name}",
            'training_queue' => $trainingQueue,
            'completion_time' => $trainingQueue->completed_at,
            'training_time' => $trainingTime,
        ];
    }

    /**
     * Complete training for a village
     */
    public function completeTraining(Village $village): array
    {
        $completedTrainings = TrainingQueue::where('village_id', $village->id)
            ->where('status', 'training')
            ->where('completed_at', '<=', now())
            ->get();

        if ($completedTrainings->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No completed training found',
            ];
        }

        $results = [];
        $totalUnitsTrained = 0;

        foreach ($completedTrainings as $training) {
            // Add units to village
            $this->addUnitsToVillage($village, $training->unitType, $training->count);
            
            // Update training status
            $training->update(['status' => 'completed']);

            $results[] = [
                'unit_type' => $training->unitType->name,
                'count' => $training->count,
                'completed_at' => $training->completed_at,
            ];

            $totalUnitsTrained += $training->count;
        }

        // Clear cache
        $this->clearTrainingCache($village);

        return [
            'success' => true,
            'message' => "Training completed for {$totalUnitsTrained} units",
            'results' => $results,
        ];
    }

    /**
     * Cancel training queue
     */
    public function cancelTraining(TrainingQueue $trainingQueue): array
    {
        if ($trainingQueue->status !== 'training') {
            return [
                'success' => false,
                'message' => 'Training cannot be cancelled',
            ];
        }

        // Calculate refund (50% of costs)
        $refund = $this->calculateRefund($trainingQueue->costs);
        
        // Refund resources
        $this->resourceService->addResources($trainingQueue->village, $refund);

        // Update training status
        $trainingQueue->update(['status' => 'cancelled']);

        // Clear cache
        $this->clearTrainingCache($trainingQueue->village);

        return [
            'success' => true,
            'message' => 'Training cancelled and resources refunded',
            'refund' => $refund,
        ];
    }

    /**
     * Get training queue for a village
     */
    public function getTrainingQueue(Village $village): array
    {
        $cacheKey = "training_queue:{$village->id}";

        return SmartCache::remember($cacheKey, 300, function () use ($village) {
            $trainingQueues = TrainingQueue::where('village_id', $village->id)
                ->where('status', 'training')
                ->with('unitType')
                ->orderBy('completed_at', 'asc')
                ->get();

            return $trainingQueues->map(function ($queue) {
                return [
                    'id' => $queue->id,
                    'unit_type' => $queue->unitType->name,
                    'count' => $queue->count,
                    'started_at' => $queue->started_at,
                    'completed_at' => $queue->completed_at,
                    'remaining_time' => max(0, $queue->completed_at->diffInSeconds(now())),
                    'progress_percentage' => $this->calculateProgressPercentage($queue),
                ];
            })->toArray();
        });
    }

    /**
     * Get available unit types for training
     */
    public function getAvailableUnitTypes(Village $village): array
    {
        $cacheKey = "available_unit_types:{$village->id}";

        return SmartCache::remember($cacheKey, 600, function () use ($village) {
            $unitTypes = UnitType::active()->get();
            $available = [];

            foreach ($unitTypes as $unitType) {
                if ($this->canTrainUnitType($village, $unitType)) {
                    $available[] = [
                        'id' => $unitType->id,
                        'name' => $unitType->name,
                        'description' => $unitType->description,
                        'tribe' => $unitType->tribe,
                        'attack' => $unitType->attack,
                        'defense_infantry' => $unitType->defense_infantry,
                        'defense_cavalry' => $unitType->defense_cavalry,
                        'speed' => $unitType->speed,
                        'carry_capacity' => $unitType->carry_capacity,
                        'costs' => $unitType->costs,
                        'requirements' => $unitType->requirements,
                    ];
                }
            }

            return $available;
        });
    }

    /**
     * Validate training requirements
     */
    private function validateTraining(Village $village, UnitType $unitType, int $count): array
    {
        if ($count <= 0) {
            return [
                'valid' => false,
                'message' => 'Invalid unit count',
            ];
        }

        if (!$this->canTrainUnitType($village, $unitType)) {
            return [
                'valid' => false,
                'message' => 'Unit type cannot be trained in this village',
            ];
        }

        // Check building requirements
        if (!$this->buildingService->meetsRequirements($village, $unitType->requirements)) {
            return [
                'valid' => false,
                'message' => 'Building requirements not met',
                'requirements' => $unitType->requirements,
            ];
        }

        return ['valid' => true];
    }

    /**
     * Check if unit type can be trained in village
     */
    private function canTrainUnitType(Village $village, UnitType $unitType): bool
    {
        // Check if unit type is active
        if (!$unitType->is_active) {
            return false;
        }

        // Check tribe compatibility (if applicable)
        if ($unitType->tribe && $village->player->tribe !== $unitType->tribe) {
            return false;
        }

        return true;
    }

    /**
     * Calculate training costs
     */
    private function calculateTrainingCosts(UnitType $unitType, int $count): array
    {
        $costs = $unitType->costs ?? [];
        $totalCosts = [];

        foreach ($costs as $resource => $cost) {
            $totalCosts[$resource] = $cost * $count;
        }

        return $totalCosts;
    }

    /**
     * Calculate training time
     */
    private function calculateTrainingTime(Village $village, UnitType $unitType, int $count): int
    {
        $baseTime = 60; // 1 minute per unit
        $buildingBonus = $this->getBuildingSpeedBonus($village);
        $totalTime = $baseTime * $count;
        
        // Apply building bonus
        $totalTime = $totalTime * (1 - $buildingBonus / 100);
        
        return max(1, (int) $totalTime);
    }

    /**
     * Get building speed bonus
     */
    private function getBuildingSpeedBonus(Village $village): float
    {
        // Get relevant buildings for training speed
        $barracks = $village->buildings()->where('building_type', 'barracks')->first();
        $stable = $village->buildings()->where('building_type', 'stable')->first();
        $workshop = $village->buildings()->where('building_type', 'workshop')->first();

        $bonus = 0;
        if ($barracks) $bonus += $barracks->level * 2;
        if ($stable) $bonus += $stable->level * 2;
        if ($workshop) $bonus += $workshop->level * 2;

        return min(50, $bonus); // Max 50% bonus
    }

    /**
     * Add units to village
     */
    private function addUnitsToVillage(Village $village, UnitType $unitType, int $count): void
    {
        DB::transaction(function () use ($village, $unitType, $count) {
            $troop = $village->troops()->where('unit_type_id', $unitType->id)->first();
            
            if ($troop) {
                $troop->increment('quantity', $count);
            } else {
                $village->troops()->create([
                    'unit_type_id' => $unitType->id,
                    'quantity' => $count,
                ]);
            }
        });
    }

    /**
     * Calculate refund amount
     */
    private function calculateRefund(array $costs): array
    {
        $refund = [];
        foreach ($costs as $resource => $cost) {
            $refund[$resource] = (int) ($cost * 0.5); // 50% refund
        }
        return $refund;
    }

    /**
     * Calculate progress percentage
     */
    private function calculateProgressPercentage(TrainingQueue $queue): float
    {
        $totalTime = $queue->started_at->diffInSeconds($queue->completed_at);
        $elapsedTime = $queue->started_at->diffInSeconds(now());
        
        return min(100, max(0, ($elapsedTime / $totalTime) * 100));
    }

    /**
     * Clear training cache
     */
    private function clearTrainingCache(Village $village): void
    {
        SmartCache::forget("training_queue:{$village->id}");
        SmartCache::forget("available_unit_types:{$village->id}");
    }
}
