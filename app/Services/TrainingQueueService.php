<?php

namespace App\Services;

use App\Models\Game\TrainingQueue;
use App\Models\Game\Village;
use App\Models\Game\UnitType;
use App\Models\Game\Troop;
use App\Models\Game\Resource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TrainingQueueService
{
    /**
     * Start a new training queue
     */
    public function startTraining(Village $village, UnitType $unitType, int $quantity): TrainingQueue
    {
        return DB::transaction(function () use ($village, $unitType, $quantity) {
            // Calculate training costs
            $costs = $this->calculateTrainingCosts($unitType, $quantity);
            
            // Check if village has enough resources
            if (!$this->hasEnoughResources($village, $costs)) {
                throw new \Exception('Insufficient resources for training');
            }

            // Calculate training time
            $trainingTime = $this->calculateTrainingTime($village, $unitType, $quantity);

            // Create training queue
            $trainingQueue = TrainingQueue::create([
                'village_id' => $village->id,
                'unit_type_id' => $unitType->id,
                'count' => $quantity,
                'started_at' => now(),
                'completed_at' => now()->addSeconds($trainingTime),
                'costs' => $costs,
                'status' => 'in_progress',
            ]);

            // Generate reference number
            $trainingQueue->generateReference();

            // Deduct resources
            $this->deductResources($village, $costs);

            Log::info('Training queue started', [
                'village_id' => $village->id,
                'unit_type' => $unitType->name,
                'quantity' => $quantity,
                'reference' => $trainingQueue->reference_number,
            ]);

            return $trainingQueue;
        });
    }

    /**
     * Complete a training queue
     */
    public function completeTraining(TrainingQueue $trainingQueue): void
    {
        DB::transaction(function () use ($trainingQueue) {
            $village = $trainingQueue->village;
            $unitType = $trainingQueue->unitType;

            // Add troops to village
            $troop = $village->troops()->where('unit_type_id', $unitType->id)->first();
            
            if ($troop) {
                $troop->increment('quantity', $trainingQueue->count);
            } else {
                $village->troops()->create([
                    'unit_type_id' => $unitType->id,
                    'quantity' => $trainingQueue->count,
                ]);
            }

            // Update training queue status
            $trainingQueue->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            Log::info('Training queue completed', [
                'village_id' => $village->id,
                'unit_type' => $unitType->name,
                'quantity' => $trainingQueue->count,
                'reference' => $trainingQueue->reference_number,
            ]);
        });
    }

    /**
     * Cancel a training queue
     */
    public function cancelTraining(TrainingQueue $trainingQueue): void
    {
        DB::transaction(function () use ($trainingQueue) {
            $village = $trainingQueue->village;
            $costs = $trainingQueue->costs;

            // Refund resources (50% refund for cancelled training)
            $refundCosts = [];
            foreach ($costs as $resource => $cost) {
                $refundCosts[$resource] = floor($cost * 0.5);
            }

            $this->addResources($village, $refundCosts);

            // Update training queue status
            $trainingQueue->update([
                'status' => 'cancelled',
                'completed_at' => now(),
            ]);

            Log::info('Training queue cancelled', [
                'village_id' => $village->id,
                'unit_type' => $trainingQueue->unitType->name,
                'quantity' => $trainingQueue->count,
                'reference' => $trainingQueue->reference_number,
                'refund' => $refundCosts,
            ]);
        });
    }

    /**
     * Process all completed training queues
     */
    public function processCompletedTraining(): int
    {
        $completedQueues = TrainingQueue::where('status', 'in_progress')
            ->where('completed_at', '<=', now())
            ->with(['village', 'unitType'])
            ->get();

        $processed = 0;
        foreach ($completedQueues as $queue) {
            try {
                $this->completeTraining($queue);
                $processed++;
            } catch (\Exception $e) {
                Log::error('Failed to complete training queue', [
                    'queue_id' => $queue->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    /**
     * Calculate training costs
     */
    private function calculateTrainingCosts(UnitType $unitType, int $quantity): array
    {
        $costs = $unitType->costs ?? [];
        $totalCosts = [];

        foreach ($costs as $resource => $cost) {
            $totalCosts[$resource] = $cost * $quantity;
        }

        return $totalCosts;
    }

    /**
     * Calculate training time
     */
    private function calculateTrainingTime(Village $village, UnitType $unitType, int $quantity): int
    {
        // Base training time (in seconds)
        $baseTime = 60; // 1 minute per unit
        
        // Building bonuses
        $barracks = $village->buildings()
            ->whereHas('buildingType', function ($query) {
                $query->where('key', 'barracks');
            })
            ->first();

        $stable = $village->buildings()
            ->whereHas('buildingType', function ($query) {
                $query->where('key', 'stable');
            })
            ->first();

        $workshop = $village->buildings()
            ->whereHas('buildingType', function ($query) {
                $query->where('key', 'workshop');
            })
            ->first();

        // Determine which building affects this unit type
        $buildingLevel = 0;
        if (in_array($unitType->key, ['legionnaire', 'praetorian', 'imperian', 'clubswinger', 'spearman', 'axeman', 'phalanx', 'swordsman'])) {
            $buildingLevel = $barracks ? $barracks->level : 0;
        } elseif (in_array($unitType->key, ['equites_legati', 'equites_imperatoris', 'equites_caesaris', 'paladin', 'teutonic_knight', 'theutates_thunder', 'druidrider', 'haeduan'])) {
            $buildingLevel = $stable ? $stable->level : 0;
        } elseif (in_array($unitType->key, ['ram', 'catapult'])) {
            $buildingLevel = $workshop ? $workshop->level : 0;
        }

        // Calculate time reduction (5% per building level)
        $timeReduction = 1 - ($buildingLevel * 0.05);
        $timeReduction = max($timeReduction, 0.1); // Minimum 10% of original time

        $totalTime = $baseTime * $quantity * $timeReduction;

        return (int) $totalTime;
    }

    /**
     * Check if village has enough resources
     */
    private function hasEnoughResources(Village $village, array $costs): bool
    {
        foreach ($costs as $resource => $cost) {
            $resourceModel = $village->resources()->where('type', $resource)->first();
            if (!$resourceModel || $resourceModel->amount < $cost) {
                return false;
            }
        }

        return true;
    }

    /**
     * Deduct resources from village
     */
    private function deductResources(Village $village, array $costs): void
    {
        foreach ($costs as $resource => $cost) {
            $resourceModel = $village->resources()->where('type', $resource)->first();
            if ($resourceModel) {
                $resourceModel->decrement('amount', $cost);
            }
        }
    }

    /**
     * Add resources to village
     */
    private function addResources(Village $village, array $costs): void
    {
        foreach ($costs as $resource => $cost) {
            $resourceModel = $village->resources()->where('type', $resource)->first();
            if ($resourceModel) {
                $resourceModel->increment('amount', $cost);
            }
        }
    }

    /**
     * Get training queue statistics
     */
    public function getTrainingStats(Village $village): array
    {
        $activeQueues = $village->trainingQueues()
            ->where('status', 'in_progress')
            ->with('unitType')
            ->get();

        $totalUnitsTraining = $activeQueues->sum('count');
        $nextCompletion = $activeQueues->min('completed_at');

        return [
            'active_queues' => $activeQueues->count(),
            'total_units_training' => $totalUnitsTraining,
            'next_completion' => $nextCompletion,
            'queues' => $activeQueues,
        ];
    }

    /**
     * Get training queue history
     */
    public function getTrainingHistory(Village $village, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return $village->trainingQueues()
            ->with('unitType')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
