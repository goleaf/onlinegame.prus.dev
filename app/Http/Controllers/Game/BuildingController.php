<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Models\Game\Building;
use App\Models\Game\BuildingType;
use App\Models\Game\BuildingQueue;
use App\Models\Game\Village;
use App\Traits\GameValidationTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;

/**
 * @group Building Management
 *
 * API endpoints for managing buildings, building queues, and construction.
 * Buildings are the foundation of village development and resource production.
 *
 * @authenticated
 *
 * @tag Building System
 * @tag Construction
 * @tag Village Development
 */
class BuildingController extends CrudController
{
    use ApiResponseTrait, GameValidationTrait;

    protected Model $model;

    protected array $validationRules = [
        'village_id' => 'required|exists:villages,id',
        'building_type_id' => 'required|exists:building_types,id',
        'level' => 'required|integer|min:1|max:20',
        'is_upgrading' => 'boolean',
    ];

    protected array $searchableFields = ['level'];
    protected array $relationships = ['buildingType', 'village', 'buildingQueues'];
    protected int $perPage = 15;

    public function __construct()
    {
        $this->model = new Building();
        parent::__construct($this->model);
    }
    /**
     * Get village buildings
     *
     * @authenticated
     *
     * @description Retrieve all buildings in a specific village.
     *
     * @urlParam villageId int required The ID of the village. Example: 1
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "village_id": 1,
     *       "building_type_id": 1,
     *       "type": {
     *         "id": 1,
     *         "name": "barracks",
     *         "display_name": "Barracks"
     *       },
     *       "level": 5,
     *       "is_upgrading": false,
     *       "created_at": "2023-01-01T00:00:00.000000Z"
     *     }
   *   ]
   * }
   *
   * @response 404 {
   *   "message": "Village not found"
   * }
   *
   * @tag Building System
   */
    public function villageBuildings(int $villageId): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            
            $village = Village::where('player_id', $playerId)
                ->findOrFail($villageId);

            $buildings = Building::with(['buildingType'])
                ->where('village_id', $villageId)
                ->get();

            return $this->successResponse($buildings, 'Village buildings retrieved successfully.');

        } catch (\Exception $e) {
            return $this->errorResponse('Village not found', 404);
        }
    }

    /**
     * Get specific building
     *
     * @authenticated
     *
     * @description Retrieve detailed information about a specific building.
     *
     * @urlParam id int required The ID of the building. Example: 1
   *
   * @response 200 {
   *   "id": 1,
   *   "village_id": 1,
   *   "building_type_id": 1,
   *   "type": {
   *     "id": 1,
   *     "name": "barracks",
   *     "display_name": "Barracks",
   *     "description": "Training facility for military units"
   *   },
   *   "level": 5,
   *   "is_upgrading": false,
   *   "upgrade_cost": {
   *     "wood": 1000,
   *     "clay": 800,
   *     "iron": 600,
   *     "crop": 400
   *   },
   *   "upgrade_time": 3600,
   *   "created_at": "2023-01-01T00:00:00.000000Z"
   * }
   *
   * @response 404 {
   *   "message": "Building not found"
   * }
   *
   * @tag Building System
   */
    public function show(int $id): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            
            $building = Building::with(['buildingType', 'village'])
                ->whereHas('village', function ($query) use ($playerId) {
                    $query->where('player_id', $playerId);
                })
                ->findOrFail($id);

            // Calculate upgrade cost and time (would need actual implementation)
            $upgradeCost = [
                'wood' => 1000 * $building->level,
                'clay' => 800 * $building->level,
                'iron' => 600 * $building->level,
                'crop' => 400 * $building->level,
            ];

            $upgradeTime = 3600 * $building->level; // Base time in seconds

            $buildingData = $building->toArray();
            $buildingData['upgrade_cost'] = $upgradeCost;
            $buildingData['upgrade_time'] = $upgradeTime;

            return $this->successResponse($buildingData, 'Building details retrieved successfully.');

        } catch (\Exception $e) {
            return $this->errorResponse('Building not found', 404);
        }
    }

    /**
     * Start building upgrade
     *
     * @authenticated
   *
   * @description Start upgrading a building to the next level.
   *
   * @urlParam id int required The ID of the building to upgrade. Example: 1
   *
   * @response 200 {
   *   "success": true,
   *   "message": "Building upgrade started",
   *   "building_queue": {
   *     "id": 1,
   *     "building_id": 1,
   *     "target_level": 6,
   *     "completion_time": "2023-01-01T13:00:00.000000Z"
   *   }
   * }
   *
   * @response 400 {
   *   "success": false,
   *   "message": "Building is already upgrading or insufficient resources"
   * }
   *
   * @tag Building System
   */
    public function upgrade(int $id): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            
            $building = Building::with(['village', 'buildingType'])
                ->whereHas('village', function ($query) use ($playerId) {
                    $query->where('player_id', $playerId);
                })
                ->findOrFail($id);

            // Check if building is already upgrading
            if ($building->is_upgrading) {
                return response()->json([
                    'success' => false,
                    'message' => 'Building is already upgrading'
                ], 400);
            }

            // Check if there's already a queue for this building
            $existingQueue = BuildingQueue::where('building_id', $id)
                ->where('status', 'active')
                ->first();

            if ($existingQueue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Building upgrade already queued'
                ], 400);
            }

            // Calculate upgrade cost and time
            $upgradeCost = [
                'wood' => 1000 * $building->level,
                'clay' => 800 * $building->level,
                'iron' => 600 * $building->level,
                'crop' => 400 * $building->level,
            ];

            $upgradeTime = 3600 * $building->level; // Base time in seconds

            // Check if player has enough resources
            $village = $building->village;
            if ($village->wood < $upgradeCost['wood'] ||
                $village->clay < $upgradeCost['clay'] ||
                $village->iron < $upgradeCost['iron'] ||
                $village->crop < $upgradeCost['crop']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient resources'
                ], 400);
            }

            DB::beginTransaction();

            // Deduct resources
            $village->decrement('wood', $upgradeCost['wood']);
            $village->decrement('clay', $upgradeCost['clay']);
            $village->decrement('iron', $upgradeCost['iron']);
            $village->decrement('crop', $upgradeCost['crop']);

            // Create building queue
            $buildingQueue = BuildingQueue::create([
                'building_id' => $id,
                'building_type_id' => $building->building_type_id,
                'target_level' => $building->level + 1,
                'status' => 'active',
                'start_time' => now(),
                'completion_time' => now()->addSeconds($upgradeTime),
            ]);

            // Mark building as upgrading
            $building->update(['is_upgrading' => true]);

            DB::commit();

            return $this->successResponse([
                'building_queue' => $buildingQueue
            ], 'Building upgrade started successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to start building upgrade: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cancel building upgrade
     *
     * @authenticated
   *
   * @description Cancel an active building upgrade and refund resources.
   *
   * @urlParam id int required The ID of the building. Example: 1
   *
   * @response 200 {
   *   "success": true,
   *   "message": "Building upgrade cancelled and resources refunded"
   * }
   *
   * @response 400 {
   *   "success": false,
   *   "message": "No active upgrade found for this building"
   * }
   *
   * @tag Building System
   */
    public function cancelUpgrade(int $id): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            
            $building = Building::with(['village'])
                ->whereHas('village', function ($query) use ($playerId) {
                    $query->where('player_id', $playerId);
                })
                ->findOrFail($id);

            $buildingQueue = BuildingQueue::where('building_id', $id)
                ->where('status', 'active')
                ->first();

            if (!$buildingQueue) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active upgrade found for this building'
                ], 400);
            }

            DB::beginTransaction();

            // Calculate refund (partial refund based on remaining time)
            $totalTime = $buildingQueue->start_time->diffInSeconds($buildingQueue->completion_time);
            $remainingTime = now()->diffInSeconds($buildingQueue->completion_time);
            $refundPercentage = max(0.5, $remainingTime / $totalTime); // Minimum 50% refund

            $upgradeCost = [
                'wood' => 1000 * $building->level,
                'clay' => 800 * $building->level,
                'iron' => 600 * $building->level,
                'crop' => 400 * $building->level,
            ];

            $refundAmount = [
                'wood' => floor($upgradeCost['wood'] * $refundPercentage),
                'clay' => floor($upgradeCost['clay'] * $refundPercentage),
                'iron' => floor($upgradeCost['iron'] * $refundPercentage),
                'crop' => floor($upgradeCost['crop'] * $refundPercentage),
            ];

            // Refund resources
            $village = $building->village;
            $village->increment('wood', $refundAmount['wood']);
            $village->increment('clay', $refundAmount['clay']);
            $village->increment('iron', $refundAmount['iron']);
            $village->increment('crop', $refundAmount['crop']);

            // Cancel queue
            $buildingQueue->update(['status' => 'cancelled']);

            // Mark building as not upgrading
            $building->update(['is_upgrading' => false]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Building upgrade cancelled and resources refunded',
                'refunded_resources' => $refundAmount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel building upgrade: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get building queue
     *
     * @authenticated
   *
   * @description Retrieve the building queue for a specific village.
   *
   * @urlParam villageId int required The ID of the village. Example: 1
   *
   * @response 200 {
   *   "data": [
   *     {
   *       "id": 1,
   *       "building_id": 1,
   *       "building_type_id": 1,
   *       "building_type": {
   *         "name": "barracks",
   *         "display_name": "Barracks"
   *       },
   *       "target_level": 6,
   *       "status": "active",
   *       "start_time": "2023-01-01T12:00:00.000000Z",
   *       "completion_time": "2023-01-01T13:00:00.000000Z"
   *     }
   *   ]
   * }
   *
   * @tag Building System
   */
    public function buildingQueue(int $villageId): JsonResponse
    {
        try {
            $playerId = Auth::user()->player->id;
            
            $village = Village::where('player_id', $playerId)
                ->findOrFail($villageId);

            $buildingQueue = BuildingQueue::whereHas('building', function ($query) use ($villageId) {
                $query->where('village_id', $villageId);
            })
                ->with(['buildingType'])
                ->orderBy('completion_time', 'asc')
                ->get();

            return response()->json(['data' => $buildingQueue]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Village not found'
            ], 404);
        }
    }

    /**
     * Get building types
     *
     * @authenticated
   *
   * @description Retrieve all available building types.
   *
   * @response 200 {
   *   "data": [
   *     {
   *       "id": 1,
   *       "name": "barracks",
   *       "display_name": "Barracks",
   *       "description": "Training facility for military units",
   *       "category": "military",
   *       "max_level": 20,
   *       "base_cost": {
   *         "wood": 1000,
   *         "clay": 800,
   *         "iron": 600,
   *         "crop": 400
   *       }
   *     }
   *   ]
   * }
   *
   * @tag Building System
   */
    public function buildingTypes(): JsonResponse
    {
        try {
            $buildingTypes = BuildingType::all();

            return response()->json(['data' => $buildingTypes]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve building types: ' . $e->getMessage()
            ], 500);
        }
    }
}
