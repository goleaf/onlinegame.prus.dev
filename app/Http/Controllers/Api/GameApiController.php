<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game\Player;
use App\Models\Game\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @group Game API
 * 
 * API endpoints for managing game players, villages, and game mechanics.
 * All endpoints require authentication via Sanctum token.
 * 
 * @authenticated
 * 
 * This API provides comprehensive game management functionality including:
 * - Player authentication and profile management
 * - Village creation and management
 * - Building upgrades and resource management
 * - Game statistics and analytics
 * 
 * @tag Game Management
 * @tag Player Management
 * @tag Village Management
 */
class GameApiController extends Controller
{
    /**
     * Get authenticated user
     * 
     * @authenticated
     * 
     * @description Retrieve the currently authenticated user's information.
     * 
     * @response 200 {
     *   "id": 1,
     *   "name": "John Doe",
     *   "email": "john@example.com",
     *   "email_verified_at": "2023-01-01T00:00:00.000000Z",
     *   "created_at": "2023-01-01T00:00:00.000000Z",
     *   "updated_at": "2023-01-01T00:00:00.000000Z"
     * }
     * 
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     * 
     * @tag Authentication
     */
    public function getUser(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    /**
     * Get player's villages
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "villages": [
     *     {
     *       "id": 1,
     *       "player_id": 1,
     *       "world_id": 1,
     *       "name": "Main Village",
     *       "x_coordinate": 50,
     *       "y_coordinate": 50,
     *       "population": 100,
     *       "is_capital": true,
     *       "is_active": true,
     *       "created_at": "2023-01-01T00:00:00.000000Z",
     *       "updated_at": "2023-01-01T00:00:00.000000Z"
     *     }
     *   ]
     * }
     * 
     * @response 401 {
     *   "message": "Unauthenticated."
     * }
     * 
     * @tag Village Management
     */
    public function getVillages(Request $request): JsonResponse
    {
        $user = $request->user();
        $player = Player::where('user_id', $user->id)->first();

        if (!$player) {
            return response()->json(['villages' => []]);
        }

        $villages = Village::where('player_id', $player->id)
            ->with(['buildings', 'resources'])
            ->get();

        return response()->json(['villages' => $villages]);
    }

    /**
     * Create a new village
     * 
     * @authenticated
     * 
     * @bodyParam name string required The name of the village. Example: "New Village"
     * @bodyParam x integer required X coordinate. Example: 50
     * @bodyParam y integer required Y coordinate. Example: 50
     * 
     * @response 200 {
     *   "success": true,
     *   "village": {
     *     "id": 2,
     *     "player_id": 1,
     *     "world_id": 1,
     *     "name": "New Village",
     *     "x_coordinate": 50,
     *     "y_coordinate": 50,
     *     "population": 2,
     *     "is_capital": false,
     *     "is_active": true,
     *     "created_at": "2023-01-01T00:00:00.000000Z",
     *     "updated_at": "2023-01-01T00:00:00.000000Z"
     *   }
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "name": ["The name field is required."]
     *   }
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Player not found"
     * }
     * 
     * @tag Village Management
     */
    public function createVillage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'x' => 'required|integer|min:0|max:999',
            'y' => 'required|integer|min:0|max:999',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $player = Player::where('user_id', $user->id)->first();

        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Player not found'
            ], 404);
        }

        $village = Village::create([
            'player_id' => $player->id,
            'world_id' => $player->world_id,
            'name' => $request->input('name'),
            'x_coordinate' => $request->input('x'),
            'y_coordinate' => $request->input('y'),
            'population' => 2,
            'is_capital' => false,
        ]);

        // Update player statistics
        $player->increment('villages_count');
        $player->increment('population', $village->population);

        return response()->json([
            'success' => true,
            'village' => $village
        ]);
    }

    /**
     * Upgrade building in a village
     * 
     * @authenticated
     * 
     * @urlParam id integer required Village ID. Example: 1
     * @bodyParam building_type string required Type of building to upgrade. Example: "wood"
     * 
     * @response 200 {
     *   "success": true,
     *   "village": {
     *     "id": 1,
     *     "player_id": 1,
     *     "world_id": 1,
     *     "name": "Main Village",
     *     "x_coordinate": 50,
     *     "y_coordinate": 50,
     *     "population": 101,
     *     "is_capital": true,
     *     "is_active": true,
     *     "created_at": "2023-01-01T00:00:00.000000Z",
     *     "updated_at": "2023-01-01T00:00:00.000000Z"
     *   }
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "message": "Unauthorized"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Village not found"
     * }
     * 
     * @tag Village Management
     */
    public function upgradeBuilding(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'building_type' => 'required|string|in:wood,clay,iron,crop',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $village = Village::find($id);

        if (!$village) {
            return response()->json([
                'success' => false,
                'message' => 'Village not found'
            ], 404);
        }

        // Check if player owns this village
        $player = Player::where('user_id', $user->id)->first();
        if (!$player || $village->player_id !== $player->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $buildingType = $request->input('building_type');

        // Simple building upgrade logic
        $resourceType = $buildingType;
        if (in_array($resourceType, ['wood', 'clay', 'iron', 'crop'])) {
            $village->increment('population', 1);
            
            // Update player population
            $player->increment('population', 1);
        }

        return response()->json([
            'success' => true,
            'village' => $village->fresh()
        ]);
    }

    /**
     * Get village details
     * 
     * @authenticated
     * 
     * @urlParam id integer required Village ID. Example: 1
     * 
     * @response 200 {
     *   "village": {
     *     "id": 1,
     *     "player_id": 1,
     *     "world_id": 1,
     *     "name": "Main Village",
     *     "x_coordinate": 50,
     *     "y_coordinate": 50,
     *     "population": 100,
     *     "is_capital": true,
     *     "is_active": true,
     *     "buildings": [],
     *     "resources": [],
     *     "created_at": "2023-01-01T00:00:00.000000Z",
     *     "updated_at": "2023-01-01T00:00:00.000000Z"
     *   }
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "message": "Unauthorized"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Village not found"
     * }
     * 
     * @tag Village Management
     */
    public function getVillage(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $village = Village::with(['buildings', 'resources', 'player'])->find($id);

        if (!$village) {
            return response()->json([
                'success' => false,
                'message' => 'Village not found'
            ], 404);
        }

        // Check if player owns this village
        $player = Player::where('user_id', $user->id)->first();
        if (!$player || $village->player_id !== $player->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        return response()->json([
            'village' => $village
        ]);
    }

    /**
     * Get player statistics
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "player": {
     *     "id": 1,
     *     "user_id": 1,
     *     "world_id": 1,
     *     "name": "PlayerName",
     *     "tribe": "Romans",
     *     "population": 1000,
     *     "villages_count": 5,
     *     "points": 15000,
     *     "is_active": true,
     *     "is_online": true,
     *     "last_login": "2023-01-01T00:00:00.000000Z",
     *     "created_at": "2023-01-01T00:00:00.000000Z",
     *     "updated_at": "2023-01-01T00:00:00.000000Z"
     *   }
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Player not found"
     * }
     * 
     * @tag Player Management
     */
    public function getPlayerStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $player = Player::where('user_id', $user->id)
            ->with(['alliance', 'villages'])
            ->first();

        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Player not found'
            ], 404);
        }

        return response()->json([
            'player' => $player
        ]);
    }
}