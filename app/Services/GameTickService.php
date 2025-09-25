<?php

namespace App\Services;

use App\Models\Game\BuildingQueue;
use App\Models\Game\GameEvent;
use App\Models\Game\Player;
use App\Models\Game\Resource;
use App\Models\Game\TrainingQueue;
use App\Models\Game\Village;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameTickService
{
    public function processGameTick()
    {
        DB::beginTransaction();
        
        try {
            // Process resource production
            $this->processResourceProduction();
            
            // Process building queues
            $this->processBuildingQueues();
            
            // Process training queues
            $this->processTrainingQueues();
            
            // Process game events
            $this->processGameEvents();
            
            // Update player statistics
            $this->updatePlayerStatistics();
            
            DB::commit();
            
            Log::info('Game tick processed successfully');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Game tick failed: ' . $e->getMessage());
            throw $e;
        }
    }

    private function processResourceProduction()
    {
        $villages = Village::with('resources')->get();
        
        foreach ($villages as $village) {
            $resources = $village->resources;
            
            foreach ($resources as $resource) {
                $timeSinceLastUpdate = now()->diffInSeconds($resource->last_updated);
                
                // Calculate production rate based on buildings
                $productionRate = $this->calculateResourceProduction($village, $resource->type);
                $production = $productionRate * $timeSinceLastUpdate;
                
                $newAmount = min(
                    $resource->amount + $production,
                    $resource->storage_capacity
                );
                
                if ($newAmount !== $resource->amount) {
                    $resource->update([
                        'amount' => $newAmount,
                        'production_rate' => $productionRate,
                        'last_updated' => now()
                    ]);
                }
            }
        }
    }

    private function processBuildingQueues()
    {
        $completedBuildings = BuildingQueue::where('completed_at', '<=', now())
            ->where('is_completed', false)
            ->get();

        foreach ($completedBuildings as $building) {
            $this->completeBuilding($building);
        }
    }

    private function processTrainingQueues()
    {
        $completedTraining = TrainingQueue::where('completed_at', '<=', now())
            ->where('is_completed', false)
            ->get();

        foreach ($completedTraining as $training) {
            $this->completeTraining($training);
        }
    }

    private function processGameEvents()
    {
        $events = GameEvent::where('triggered_at', '<=', now())
            ->where('is_completed', false)
            ->get();

        foreach ($events as $event) {
            $this->completeEvent($event);
        }
    }

    private function updatePlayerStatistics()
    {
        $players = Player::with(['villages.troops'])->get();
        
        foreach ($players as $player) {
            $totalPopulation = $player->villages->sum('population');
            $totalTroops = $player->villages->sum(function($village) {
                return $village->troops->sum('quantity');
            });
            
            $player->update([
                'population' => $totalPopulation,
                'villages_count' => $player->villages->count(),
                'last_active_at' => now()
            ]);
        }
    }

    private function completeBuilding($building)
    {
        try {
            $building->update(['is_completed' => true]);
            
            // Update village building
            $villageBuilding = $building->village->buildings()
                ->where('building_type_id', $building->building_type_id)
                ->first();

            if ($villageBuilding) {
                $villageBuilding->update(['level' => $building->target_level]);
            }

            // Create game event
            GameEvent::create([
                'player_id' => $building->village->player_id,
                'village_id' => $building->village_id,
                'type' => 'building_completed',
                'title' => 'Building Completed',
                'description' => "{$building->buildingType->name} reached level {$building->target_level}",
                'data' => [
                    'building_name' => $building->buildingType->name,
                    'level' => $building->target_level,
                    'village_id' => $building->village_id
                ],
                'triggered_at' => now(),
                'is_completed' => true
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to complete building {$building->id}: " . $e->getMessage());
        }
    }

    private function completeTraining($training)
    {
        try {
            $training->update(['is_completed' => true]);
            
            // Add troops to village
            $village = $training->village;
            $troop = $village->troops()
                ->where('unit_type_id', $training->unit_type_id)
                ->first();

            if ($troop) {
                $troop->increment('quantity', $training->quantity);
            } else {
                $village->troops()->create([
                    'unit_type_id' => $training->unit_type_id,
                    'quantity' => $training->quantity
                ]);
            }

            // Create game event
            GameEvent::create([
                'player_id' => $training->village->player_id,
                'village_id' => $training->village_id,
                'type' => 'training_completed',
                'title' => 'Training Completed',
                'description' => "{$training->quantity} {$training->unitType->name} trained",
                'data' => [
                    'unit_name' => $training->unitType->name,
                    'quantity' => $training->quantity,
                    'village_id' => $training->village_id
                ],
                'triggered_at' => now(),
                'is_completed' => true
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to complete training {$training->id}: " . $e->getMessage());
        }
    }

    private function completeEvent($event)
    {
        try {
            $event->update(['is_completed' => true]);
            
            // Process event rewards if any
            if ($event->rewards) {
                $this->processEventRewards($event);
            }

        } catch (\Exception $e) {
            Log::error("Failed to complete event {$event->id}: " . $e->getMessage());
        }
    }

    private function processEventRewards($event)
    {
        $rewards = $event->rewards;
        
        if (isset($rewards['resources'])) {
            $village = $event->village;
            
            foreach ($rewards['resources'] as $resource => $amount) {
                $resourceModel = $village->resources()->where('type', $resource)->first();
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

    public function getGameTickStatus()
    {
        return [
            'last_tick' => now(),
            'pending_buildings' => BuildingQueue::where('is_completed', false)->count(),
            'pending_training' => TrainingQueue::where('is_completed', false)->count(),
            'pending_events' => GameEvent::where('is_completed', false)->count(),
        ];
    }

    /**
     * Calculate resource production rate for a village
     */
    public function calculateResourceProduction(Village $village, string $resourceType): int
    {
        $baseProduction = 10;  // Base production per second

        // Get building levels that affect this resource
        $buildingLevels = $this->getResourceBuildingLevels($village, $resourceType);

        // Calculate production based on building levels
        $production = $baseProduction;
        foreach ($buildingLevels as $level) {
            $production += $level * 2;  // Each level adds 2 production per second
        }

        return $production;
    }

    /**
     * Get building levels that affect a specific resource type
     */
    public function getResourceBuildingLevels(Village $village, string $resourceType): array
    {
        $buildingTypeMap = [
            'wood' => ['woodcutter'],
            'clay' => ['clay_pit'],
            'iron' => ['iron_mine'],
            'crop' => ['crop_field'],
        ];

        $buildingTypes = $buildingTypeMap[$resourceType] ?? [];
        
        if (empty($buildingTypes)) {
            return [];
        }

        $levels = [];
        foreach ($buildingTypes as $buildingType) {
            $building = $village->buildings()
                ->whereHas('buildingType', function($query) use ($buildingType) {
                    $query->where('key', $buildingType);
                })
                ->first();

            if ($building) {
                $levels[] = $building->level;
            }
        }

        return $levels;
    }

    /**
     * Create a game event
     */
    public function createGameEvent($player, $village, string $eventType, string $description, array $data = [])
    {
        return GameEvent::create([
            'player_id' => is_object($player) ? $player->id : $player,
            'village_id' => is_object($village) ? $village->id : $village,
            'event_type' => $eventType,
            'description' => $description,
            'data' => $data,
            'occurred_at' => now(),
        ]);
    }

    /**
     * Log resource production
     */
    public function logResourceProduction(Village $village, string $resourceType, int $amountProduced, int $finalAmount)
    {
        return \App\Models\Game\ResourceProductionLog::create([
            'village_id' => $village->id,
            'type' => $resourceType,
            'amount_produced' => $amountProduced,
            'final_amount' => $finalAmount,
            'produced_at' => now(),
        ]);
    }
}