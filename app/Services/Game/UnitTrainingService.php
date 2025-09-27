<?php

namespace App\Services\Game;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\UnitType;
use App\Models\Game\TrainingQueue;
use App\Models\Game\Resource;
use App\Services\GameCacheService;
use App\Services\GamePerformanceMonitor;
use App\Services\GameErrorHandler;
use App\Utilities\GameUtility;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Unit Training Service
 * Handles unit training, queue management, and resource calculations
 */
class UnitTrainingService
{
    /**
     * Start training units
     */
    public function startTraining(
        int $villageId,
        int $unitTypeId,
        int $quantity,
        int $playerId
    ): array {
        $startTime = microtime(true);

        try {
            DB::beginTransaction();

            // Validate village ownership
            $village = Village::where('id', $villageId)
                ->where('player_id', $playerId)
                ->firstOrFail();

            // Get unit type
            $unitType = UnitType::findOrFail($unitTypeId);

            // Calculate training cost
            $cost = GameUtility::calculateTroopCost($unitType->name, $quantity);

            // Check if player has enough resources
            $resources = $this->getVillageResources($villageId);
            if (!$this->hasEnoughResources($resources, $cost)) {
                throw new \Exception('Insufficient resources for training');
            }

            // Check if village has enough population
            if ($village->population < $quantity) {
                throw new \Exception('Insufficient population for training');
            }

            // Calculate training time
            $trainingTime = $this->calculateTrainingTime($unitType, $quantity, $village);

            // Deduct resources
            $this->deductResources($villageId, $cost);

            // Create training queue entry
            $trainingQueue = TrainingQueue::create([
                'village_id' => $villageId,
                'unit_type_id' => $unitTypeId,
                'quantity' => $quantity,
                'started_at' => now(),
                'completed_at' => now()->addSeconds($trainingTime),
                'status' => 'training',
                'reference_number' => GameUtility::generateReference('TRN'),
            ]);

            // Update village population
            $village->decrement('population', $quantity);

            DB::commit();

            // Invalidate cache
            GameCacheService::invalidateVillageCache($villageId);

            // Log action
            GameErrorHandler::logGameAction('unit_training_started', [
                'village_id' => $villageId,
                'unit_type_id' => $unitTypeId,
                'quantity' => $quantity,
                'training_time' => $trainingTime,
                'cost' => $cost,
            ]);

            // Monitor performance
            GamePerformanceMonitor::monitorResponseTime('unit_training_start', $startTime);

            return [
                'success' => true,
                'message' => 'Unit training started successfully',
                'training_queue' => $trainingQueue,
                'training_time' => $trainingTime,
                'cost' => $cost,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            GameErrorHandler::handleGameError($e, [
                'action' => 'unit_training_start',
                'village_id' => $villageId,
                'unit_type_id' => $unitTypeId,
                'quantity' => $quantity,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Complete training
     */
    public function completeTraining(int $trainingQueueId): array
    {
        $startTime = microtime(true);

        try {
            DB::beginTransaction();

            $trainingQueue = TrainingQueue::findOrFail($trainingQueueId);

            if ($trainingQueue->status !== 'training') {
                throw new \Exception('Training is not in progress');
            }

            if ($trainingQueue->completed_at > now()) {
                throw new \Exception('Training is not yet complete');
            }

            // Get village and unit type
            $village = $trainingQueue->village;
            $unitType = $trainingQueue->unitType;

            // Add units to village
            $this->addUnitsToVillage($village->id, $unitType->id, $trainingQueue->quantity);

            // Update training queue status
            $trainingQueue->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            DB::commit();

            // Invalidate cache
            GameCacheService::invalidateVillageCache($village->id);

            // Log action
            GameErrorHandler::logGameAction('unit_training_completed', [
                'training_queue_id' => $trainingQueueId,
                'village_id' => $village->id,
                'unit_type_id' => $unitType->id,
                'quantity' => $trainingQueue->quantity,
            ]);

            // Monitor performance
            GamePerformanceMonitor::monitorResponseTime('unit_training_complete', $startTime);

            return [
                'success' => true,
                'message' => 'Unit training completed successfully',
                'training_queue' => $trainingQueue,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            GameErrorHandler::handleGameError($e, [
                'action' => 'unit_training_complete',
                'training_queue_id' => $trainingQueueId,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel training
     */
    public function cancelTraining(int $trainingQueueId): array
    {
        $startTime = microtime(true);

        try {
            DB::beginTransaction();

            $trainingQueue = TrainingQueue::findOrFail($trainingQueueId);

            if ($trainingQueue->status !== 'training') {
                throw new \Exception('Training is not in progress');
            }

            // Get village
            $village = $trainingQueue->village;

            // Calculate refund (50% of cost)
            $cost = GameUtility::calculateTroopCost($trainingQueue->unitType->name, $trainingQueue->quantity);
            $refund = $this->calculateRefund($cost);

            // Refund resources
            $this->addResources($village->id, $refund);

            // Return population
            $village->increment('population', $trainingQueue->quantity);

            // Update training queue status
            $trainingQueue->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            DB::commit();

            // Invalidate cache
            GameCacheService::invalidateVillageCache($village->id);

            // Log action
            GameErrorHandler::logGameAction('unit_training_cancelled', [
                'training_queue_id' => $trainingQueueId,
                'village_id' => $village->id,
                'refund' => $refund,
            ]);

            // Monitor performance
            GamePerformanceMonitor::monitorResponseTime('unit_training_cancel', $startTime);

            return [
                'success' => true,
                'message' => 'Unit training cancelled successfully',
                'refund' => $refund,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            GameErrorHandler::handleGameError($e, [
                'action' => 'unit_training_cancel',
                'training_queue_id' => $trainingQueueId,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get training queue for village
     */
    public function getTrainingQueue(int $villageId): array
    {
        $cacheKey = "training_queue:{$villageId}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($villageId) {
            return TrainingQueue::with(['unitType'])
                ->where('village_id', $villageId)
                ->where('status', 'training')
                ->orderBy('completed_at', 'asc')
                ->get()
                ->toArray();
        });
    }

    /**
     * Process all completed trainings
     */
    public function processCompletedTrainings(): array
    {
        $startTime = microtime(true);
        $processed = 0;
        $errors = 0;

        try {
            $completedTrainings = TrainingQueue::where('status', 'training')
                ->where('completed_at', '<=', now())
                ->get();

            foreach ($completedTrainings as $training) {
                $result = $this->completeTraining($training->id);
                if ($result['success']) {
                    $processed++;
                } else {
                    $errors++;
                    Log::error('Failed to complete training', [
                        'training_id' => $training->id,
                        'error' => $result['message'],
                    ]);
                }
            }

            // Monitor performance
            GamePerformanceMonitor::monitorResponseTime('process_completed_trainings', $startTime);

            return [
                'success' => true,
                'processed' => $processed,
                'errors' => $errors,
                'total' => $completedTrainings->count(),
            ];

        } catch (\Exception $e) {
            GameErrorHandler::handleGameError($e, [
                'action' => 'process_completed_trainings',
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'processed' => $processed,
                'errors' => $errors,
            ];
        }
    }

    /**
     * Get village resources
     */
    private function getVillageResources(int $villageId): array
    {
        return GameCacheService::getResourceData($villageId) ?? [
            'wood' => 0,
            'clay' => 0,
            'iron' => 0,
            'crop' => 0,
        ];
    }

    /**
     * Check if player has enough resources
     */
    private function hasEnoughResources(array $resources, array $cost): bool
    {
        foreach ($cost as $resource => $amount) {
            if (($resources[$resource] ?? 0) < $amount) {
                return false;
            }
        }
        return true;
    }

    /**
     * Calculate training time
     */
    private function calculateTrainingTime(UnitType $unitType, int $quantity, Village $village): int
    {
        $baseTime = $unitType->training_time ?? 60; // Default 60 seconds
        $quantityMultiplier = 1 + ($quantity - 1) * 0.1; // 10% increase per additional unit
        
        // Apply village bonuses (barracks level, etc.)
        $villageBonus = $this->getVillageTrainingBonus($village);
        
        return (int) ($baseTime * $quantityMultiplier * $villageBonus);
    }

    /**
     * Get village training bonus
     */
    private function getVillageTrainingBonus(Village $village): float
    {
        // Check for barracks level
        $barracks = $village->buildings()->where('type', 'barracks')->first();
        if ($barracks) {
            return 1 - ($barracks->level * 0.05); // 5% reduction per level
        }
        
        return 1.0; // No bonus
    }

    /**
     * Deduct resources from village
     */
    private function deductResources(int $villageId, array $cost): void
    {
        foreach ($cost as $resource => $amount) {
            DB::table('resources')
                ->where('village_id', $villageId)
                ->where('type', $resource)
                ->decrement('amount', $amount);
        }
    }

    /**
     * Add resources to village
     */
    private function addResources(int $villageId, array $resources): void
    {
        foreach ($resources as $resource => $amount) {
            DB::table('resources')
                ->where('village_id', $villageId)
                ->where('type', $resource)
                ->increment('amount', $amount);
        }
    }

    /**
     * Add units to village
     */
    private function addUnitsToVillage(int $villageId, int $unitTypeId, int $quantity): void
    {
        // Check if unit already exists in village
        $existingUnit = DB::table('troops')
            ->where('village_id', $villageId)
            ->where('unit_type_id', $unitTypeId)
            ->first();

        if ($existingUnit) {
            // Update existing unit count
            DB::table('troops')
                ->where('village_id', $villageId)
                ->where('unit_type_id', $unitTypeId)
                ->increment('quantity', $quantity);
        } else {
            // Create new unit entry
            DB::table('troops')->insert([
                'village_id' => $villageId,
                'unit_type_id' => $unitTypeId,
                'quantity' => $quantity,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Calculate refund amount
     */
    private function calculateRefund(array $cost): array
    {
        $refund = [];
        foreach ($cost as $resource => $amount) {
            $refund[$resource] = (int) ($amount * 0.5); // 50% refund
        }
        return $refund;
    }

    /**
     * Get training statistics
     */
    public function getTrainingStatistics(int $villageId): array
    {
        $cacheKey = "training_stats:{$villageId}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($villageId) {
            $totalTrainings = TrainingQueue::where('village_id', $villageId)->count();
            $completedTrainings = TrainingQueue::where('village_id', $villageId)
                ->where('status', 'completed')
                ->count();
            $activeTrainings = TrainingQueue::where('village_id', $villageId)
                ->where('status', 'training')
                ->count();

            return [
                'total_trainings' => $totalTrainings,
                'completed_trainings' => $completedTrainings,
                'active_trainings' => $activeTrainings,
                'completion_rate' => $totalTrainings > 0 ? ($completedTrainings / $totalTrainings) * 100 : 0,
            ];
        });
    }
}