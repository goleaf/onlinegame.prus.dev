<?php

namespace App\Services;

use App\Models\Game\Building;
use App\Models\Game\BuildingQueue;
use App\Models\Game\Resource;
use App\Models\Game\Village;
use App\Models\Game\World;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameMechanicsService
{
    protected $gameTickService;

    protected $resourceProductionService;

    protected $buildingService;

    protected $troopService;

    public function __construct(
        GameTickService $gameTickService,
        ResourceProductionService $resourceProductionService,
        BuildingService $buildingService,
        TroopService $troopService
    ) {
        $this->gameTickService = $gameTickService;
        $this->resourceProductionService = $resourceProductionService;
        $this->buildingService = $buildingService;
        $this->troopService = $troopService;
    }

    /**
     * Process all game mechanics for a world
     */
    public function processWorldMechanics(World $world)
    {
        $startTime = microtime(true);

        ds('GameMechanicsService: Processing world mechanics', [
            'service' => 'GameMechanicsService',
            'method' => 'processWorldMechanics',
            'world_id' => $world->id,
            'world_name' => $world->name,
            'processing_time' => now(),
        ]);

        try {
            DB::beginTransaction();

            // Process all villages in the world
            $villageQueryStart = microtime(true);
            $villages = Village::where('world_id', $world->id)
                ->with(['resources', 'buildings', 'player'])
                ->get();
            $villageQueryTime = round((microtime(true) - $villageQueryStart) * 1000, 2);

            ds('GameMechanicsService: Villages loaded for processing', [
                'world_id' => $world->id,
                'villages_count' => $villages->count(),
                'village_query_time_ms' => $villageQueryTime,
            ]);

            $villageProcessingStart = microtime(true);
            foreach ($villages as $village) {
                $this->processVillageMechanics($village);
            }
            $villageProcessingTime = round((microtime(true) - $villageProcessingStart) * 1000, 2);

            // Process world-wide events
            $worldEventsStart = microtime(true);
            $this->processWorldEvents($world);
            $worldEventsTime = round((microtime(true) - $worldEventsStart) * 1000, 2);

            DB::commit();

            $totalTime = round((microtime(true) - $startTime) * 1000, 2);

            ds('GameMechanicsService: World mechanics processed successfully', [
                'world_id' => $world->id,
                'villages_processed' => $villages->count(),
                'village_processing_time_ms' => $villageProcessingTime,
                'world_events_time_ms' => $worldEventsTime,
                'total_time_ms' => $totalTime,
            ]);

            Log::info("World mechanics processed for world {$world->id}");
        } catch (\Exception $e) {
            DB::rollBack();

            ds('GameMechanicsService: World mechanics processing failed', [
                'world_id' => $world->id,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ]);

            Log::error('Failed to process world mechanics: '.$e->getMessage());

            throw $e;
        }
    }

    /**
     * Process mechanics for a specific village
     */
    public function processVillageMechanics(Village $village)
    {
        // Update resource production
        $this->resourceProductionService->updateVillageResources($village);

        // Process building queues
        $this->processBuildingQueues($village);

        // Update village population
        $this->updateVillagePopulation($village);

        // Process village events
        $this->processVillageEvents($village);
    }

    /**
     * Process building construction queues
     */
    public function processBuildingQueues(Village $village)
    {
        $queues = BuildingQueue::where('village_id', $village->id)
            ->where('status', 'building')
            ->orderBy('started_at')
            ->get();

        foreach ($queues as $queue) {
            if ($this->isBuildingComplete($queue)) {
                $this->completeBuilding($queue);
            }
        }
    }

    /**
     * Check if a building construction is complete
     */
    protected function isBuildingComplete(BuildingQueue $queue)
    {
        $buildTime = $this->calculateBuildTime($queue);
        $completionTime = $queue->started_at->addSeconds($buildTime);

        return now()->gte($completionTime);
    }

    /**
     * Complete a building construction
     */
    protected function completeBuilding(BuildingQueue $queue)
    {
        try {
            DB::beginTransaction();

            // Update building level
            $building = Building::where('village_id', $queue->village_id)
                ->where('building_type_id', $queue->building_type_id)
                ->first();

            if ($building) {
                $building->level = $queue->target_level;
                $building->save();
            } else {
                // Create new building if it doesn't exist
                Building::create([
                    'village_id' => $queue->village_id,
                    'building_type_id' => $queue->building_type_id,
                    'level' => $queue->target_level,
                    'position' => $queue->position ?? 0,
                ]);
            }

            // Mark queue as completed
            $queue->status = 'completed';
            $queue->completed_at = now();
            $queue->save();

            // Create game event
            $this->createBuildingCompletedEvent($queue);

            DB::commit();

            Log::info("Building completed: {$queue->buildingType->name} level {$queue->target_level} in village {$queue->village_id}");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete building: '.$e->getMessage());

            throw $e;
        }
    }

    /**
     * Calculate build time for a building
     */
    protected function calculateBuildTime(BuildingQueue $queue)
    {
        $buildingType = $queue->buildingType;
        $level = $queue->target_level;

        // Base build time calculation
        $baseTime = $buildingType->build_time_base;
        $multiplier = $buildingType->build_time_multiplier;

        return $baseTime * pow($multiplier, $level - 1);
    }

    /**
     * Update village population based on buildings
     */
    public function updateVillagePopulation(Village $village)
    {
        $buildings = Building::where('village_id', $village->id)
            ->with('buildingType')
            ->get();

        $totalPopulation = 0;

        foreach ($buildings as $building) {
            $population = $building->buildingType->population ?? 0;
            $totalPopulation += $population * $building->level;
        }

        $village->population = $totalPopulation;
        $village->save();
    }

    /**
     * Process village-specific events
     */
    protected function processVillageEvents(Village $village)
    {
        // Check for resource capacity overflows
        $this->checkResourceOverflow($village);

        // Check for building requirements
        $this->checkBuildingRequirements($village);
    }

    /**
     * Check for resource capacity overflows
     */
    protected function checkResourceOverflow(Village $village)
    {
        $resources = Resource::where('village_id', $village->id)->get();

        foreach ($resources as $resource) {
            $capacity = $this->calculateResourceCapacity($village, $resource->type);

            if ($resource->amount > $capacity) {
                $resource->amount = $capacity;
                $resource->save();

                // Create overflow event
                $this->createResourceOverflowEvent($village, $resource);
            }
        }
    }

    /**
     * Calculate resource capacity for a village
     */
    protected function calculateResourceCapacity(Village $village, string $resourceType)
    {
        $buildings = Building::where('village_id', $village->id)
            ->with('buildingType')
            ->get();

        $capacity = 800;  // Base capacity

        foreach ($buildings as $building) {
            $effects = $building->buildingType->effects ?? [];

            if (isset($effects['capacity_'.$resourceType])) {
                $capacity += $effects['capacity_'.$resourceType] * $building->level;
            }
        }

        return $capacity;
    }

    /**
     * Check building requirements
     */
    protected function checkBuildingRequirements(Village $village)
    {
        $buildings = Building::where('village_id', $village->id)
            ->with('buildingType')
            ->get();

        foreach ($buildings as $building) {
            $requirements = $building->buildingType->requirements ?? [];

            foreach ($requirements as $requirement) {
                if (! $this->checkRequirement($village, $requirement)) {
                    // Handle requirement not met
                    $this->handleRequirementNotMet($village, $building, $requirement);
                }
            }
        }
    }

    /**
     * Check if a requirement is met
     */
    protected function checkRequirement(Village $village, array $requirement)
    {
        switch ($requirement['type']) {
            case 'building':
                $building = Building::where('village_id', $village->id)
                    ->where('building_type_id', $requirement['building_type_id'])
                    ->first();

                return $building && $building->level >= $requirement['level'];

            case 'resource':
                $resource = Resource::where('village_id', $village->id)
                    ->where('type', $requirement['resource_type'])
                    ->first();

                return $resource && $resource->amount >= $requirement['amount'];

            default:
                return true;
        }
    }

    /**
     * Handle requirement not met
     */
    protected function handleRequirementNotMet(Village $village, Building $building, array $requirement)
    {
        // Log requirement not met
        Log::warning("Requirement not met for building {$building->id}: ".json_encode($requirement));

        // Create event for requirement not met
        $this->createRequirementNotMetEvent($village, $building, $requirement);
    }

    /**
     * Process world-wide events
     */
    protected function processWorldEvents(World $world)
    {
        // Process world events like server maintenance, special events, etc.
        $this->processServerEvents($world);
        $this->processSpecialEvents($world);
    }

    /**
     * Process server events
     */
    protected function processServerEvents(World $world)
    {
        // Check for server maintenance windows
        // Check for world resets
        // Check for world events
    }

    /**
     * Process special events
     */
    protected function processSpecialEvents(World $world)
    {
        // Check for special game events
        // Process holiday events
        // Process tournament events
    }

    /**
     * Create building completed event
     */
    protected function createBuildingCompletedEvent(BuildingQueue $queue)
    {
        // Create game event for building completion
        $queue->village->player->gameEvents()->create([
            'village_id' => $queue->village_id,
            'event_type' => 'building_completed',
            'event_data' => [
                'building_name' => $queue->buildingType->name,
                'level' => $queue->target_level,
                'position' => $queue->position,
            ],
            'occurred_at' => now(),
            'description' => "Building {$queue->buildingType->name} completed to level {$queue->target_level}",
        ]);
    }

    /**
     * Create resource overflow event
     */
    protected function createResourceOverflowEvent(Village $village, Resource $resource)
    {
        $village->player->gameEvents()->create([
            'village_id' => $village->id,
            'event_type' => 'resource_overflow',
            'event_data' => [
                'resource_type' => $resource->type,
                'amount' => $resource->amount,
            ],
            'occurred_at' => now(),
            'description' => "Resource {$resource->type} storage is full",
        ]);
    }

    /**
     * Create requirement not met event
     */
    protected function createRequirementNotMetEvent(Village $village, Building $building, array $requirement)
    {
        $village->player->gameEvents()->create([
            'village_id' => $village->id,
            'event_type' => 'requirement_not_met',
            'event_data' => [
                'building_name' => $building->buildingType->name,
                'requirement' => $requirement,
            ],
            'occurred_at' => now(),
            'description' => "Building {$building->buildingType->name} requirements not met",
        ]);
    }

    /**
     * Get village statistics
     */
    public function getVillageStatistics(Village $village)
    {
        $buildings = Building::where('village_id', $village->id)
            ->with('buildingType')
            ->get();

        $resources = Resource::where('village_id', $village->id)->get();

        return [
            'total_buildings' => $buildings->count(),
            'total_levels' => $buildings->sum('level'),
            'population' => $village->population,
            'resources' => $resources->pluck('amount', 'type'),
            'production_rates' => $this->resourceProductionService->getProductionRates($village),
            'storage_capacity' => $this->calculateStorageCapacity($village),
        ];
    }

    /**
     * Calculate total storage capacity
     */
    protected function calculateStorageCapacity(Village $village)
    {
        $buildings = Building::where('village_id', $village->id)
            ->with('buildingType')
            ->get();

        $capacity = [
            'wood' => 800,
            'clay' => 800,
            'iron' => 800,
            'crop' => 800,
        ];

        foreach ($buildings as $building) {
            $effects = $building->buildingType->effects ?? [];

            foreach (['wood', 'clay', 'iron', 'crop'] as $resource) {
                if (isset($effects['capacity_'.$resource])) {
                    $capacity[$resource] += $effects['capacity_'.$resource] * $building->level;
                }
            }
        }

        return $capacity;
    }
}
