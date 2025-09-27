<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Services\RealTimeGameService;
use App\Services\GameCacheService;
use App\Services\GameErrorHandler;
use App\Services\GamePerformanceMonitor;
use App\Services\GameIntegrationService;
use App\Services\ValueObjectService;
use App\ValueObjects\PlayerStats;
use App\ValueObjects\VillageResources;
use App\ValueObjects\Coordinates;
use App\Traits\GameValidationTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Intervention\Validation\Rules\Username;
use Intervention\Validation\Rules\Latitude;
use Intervention\Validation\Rules\Longitude;
use JonPurvis\Squeaky\Rules\Clean;
use sbamtr\LaravelQueryEnrich\QE;
use function sbamtr\LaravelQueryEnrich\c;

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
        $startTime = microtime(true);

        ds('API: Get user request', [
            'endpoint' => 'getUser',
            'user_id' => $request->user()->id ?? null,
            'request_time' => now()
        ]);

        $user = $request->user();
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);

        ds('API: Get user response', [
            'user_id' => $user->id,
            'response_time_ms' => $responseTime
        ]);

        return response()->json($user);
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
        $startTime = microtime(true);

        ds('API: Get villages request', [
            'endpoint' => 'getVillages',
            'user_id' => $request->user()->id ?? null,
            'request_time' => now()
        ]);

        $user = $request->user();
        $player = Player::where('user_id', $user->id)->first();

        if (!$player) {
            ds('API: No player found for user', [
                'user_id' => $user->id
            ]);
            return response()->json(['villages' => []]);
        }

        $villages = Village::where('player_id', $player->id)
            ->with(['buildings', 'resources'])
            ->select([
                'villages.*',
                'latitude',
                'longitude',
                'geohash',
                'elevation',
                QE::select(QE::count(c('id')))
                    ->from('buildings')
                    ->whereColumn('village_id', c('villages.id'))
                    ->as('building_count'),
                QE::select(QE::count(c('id')))
                    ->from('troops')
                    ->whereColumn('village_id', c('villages.id'))
                    ->where('quantity', '>', 0)
                    ->as('troop_count')
            ])
            ->get()
            ->map(function ($village) {
                return [
                    'id' => $village->id,
                    'player_id' => $village->player_id,
                    'world_id' => $village->world_id,
                    'name' => $village->name,
                    'x_coordinate' => $village->x_coordinate,
                    'y_coordinate' => $village->y_coordinate,
                    'population' => $village->population,
                    'is_capital' => $village->is_capital,
                    'is_active' => $village->is_active,
                    'latitude' => $village->latitude,
                    'longitude' => $village->longitude,
                    'geohash' => $village->geohash,
                    'elevation' => $village->elevation,
                    'building_count' => $village->building_count ?? 0,
                    'troop_count' => $village->troop_count ?? 0,
                    'buildings' => $village->buildings,
                    'resources' => $village->resources,
                    'created_at' => $village->created_at,
                    'updated_at' => $village->updated_at,
                ];
            });

        $responseTime = round((microtime(true) - $startTime) * 1000, 2);

        ds('API: Get villages response', [
            'player_id' => $player->id,
            'villages_count' => $villages->count(),
            'response_time_ms' => $responseTime
        ]);

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

        $startTime = microtime(true);

        try {
            DB::beginTransaction();

            // Simple building upgrade logic
            $resourceType = $buildingType;
            if (in_array($resourceType, ['wood', 'clay', 'iron', 'crop'])) {
                $village->increment('population', 1);

                // Update player population
                $player->increment('population', 1);
            }

            DB::commit();

            // Send real-time update
            RealTimeGameService::sendBuildingUpdate(
                $user->id,
                $village->id,
                $buildingType,
                1, // Assuming level 1 for now
                [
                    'village_name' => $village->name,
                    'building_type' => $buildingType,
                    'new_population' => $village->fresh()->population,
                ]
            );

            // Invalidate cache
            GameCacheService::invalidateVillageCache($village->id);
            GameCacheService::invalidatePlayerCache($user->id);

            // Log performance
            GamePerformanceMonitor::monitorResponseTime('api_upgrade_building', $startTime);

            // Log action
            GameErrorHandler::logGameAction('api_upgrade_building', [
                'user_id' => $user->id,
                'village_id' => $village->id,
                'building_type' => $buildingType,
            ]);

            return response()->json([
                'success' => true,
                'village' => $village->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            GameErrorHandler::handleGameError($e, [
                'action' => 'api_upgrade_building',
                'user_id' => $user->id,
                'village_id' => $village->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Building upgrade failed'
            ], 500);
        }
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
        $village = Village::with(['buildings', 'resources', 'player'])
            ->select([
                'villages.*',
                'latitude',
                'longitude',
                'geohash',
                'elevation',
                QE::select(QE::count(c('id')))
                    ->from('buildings')
                    ->whereColumn('village_id', c('villages.id'))
                    ->as('building_count'),
                QE::select(QE::count(c('id')))
                    ->from('troops')
                    ->whereColumn('village_id', c('villages.id'))
                    ->where('quantity', '>', 0)
                    ->as('troop_count')
            ])
            ->find($id);

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

        // Create enhanced player stats using value objects
        $playerStats = new PlayerStats(
            points: $player->points,
            rank: $this->calculatePlayerRank($player),
            population: $player->villages->sum('population'),
            villages: $player->villages->count(),
            alliance_id: $player->alliance_id,
            tribe: $player->tribe,
            is_online: $player->is_online,
            is_active: $player->is_active,
            last_active_at: $player->last_active_at
        );

        // Get village data with value objects
        $villageData = $player->villages->map(function ($village) {
            $villageResources = $village->resources ? new VillageResources(
                wood: $village->resources->wood ?? 0,
                clay: $village->resources->clay ?? 0,
                iron: $village->resources->iron ?? 0,
                crop: $village->resources->crop ?? 0
            ) : null;

            $coordinates = new Coordinates(
                x: $village->x_coordinate,
                y: $village->y_coordinate,
                latitude: $village->latitude,
                longitude: $village->longitude,
                elevation: $village->elevation,
                geohash: $village->geohash
            );

            return [
                'id' => $village->id,
                'name' => $village->name,
                'coordinates' => $coordinates,
                'resources' => $villageResources,
                'population' => $village->population,
                'is_capital' => $village->is_capital,
                'is_active' => $village->is_active,
            ];
        });

        return response()->json([
            'success' => true,
            'player' => $player,
            'player_stats' => $playerStats,
            'villages' => $villageData,
            'value_objects_integration' => true
        ]);
    }

    /**
     * Get geographic data for villages
     *
     * @authenticated
     *
     * @queryParam radius integer Distance radius in kilometers. Example: 50
     * @queryParam center_lat float Center latitude. Example: 50.1109
     * @queryParam center_lon float Center longitude. Example: 8.6821
     *
     * @response 200 {
     *   "villages": [
     *     {
     *       "id": 1,
     *       "name": "Main Village",
     *       "latitude": 50.1109,
     *       "longitude": 8.6821,
     *       "geohash": "u0y0y0",
     *       "distance_km": 0,
     *       "bearing": 0
     *     }
     *   ]
     * }
     *
     * @tag Geographic Data
     */
    public function getGeographicData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'radius' => 'nullable|integer|min:1|max:1000',
            'center_lat' => 'nullable|numeric|between:-90,90',
            'center_lon' => 'nullable|numeric|between:-180,180',
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

        $query = Village::where('player_id', $player->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // Apply radius filter if provided
        if ($request->has('radius') && $request->has('center_lat') && $request->has('center_lon')) {
            $radius = $request->input('radius');
            $centerLat = $request->input('center_lat');
            $centerLon = $request->input('center_lon');

            $query->whereRaw('ST_Distance_Sphere(
                POINT(longitude, latitude), 
                POINT(?, ?)
            ) <= ?', [$centerLon, $centerLat, $radius * 1000]);
        }

        $villages = $query->get()->map(function ($village) use ($request) {
            $data = [
                'id' => $village->id,
                'name' => $village->name,
                'latitude' => $village->latitude,
                'longitude' => $village->longitude,
                'geohash' => $village->geohash,
                'elevation' => $village->elevation,
            ];

            // Calculate distance and bearing if center coordinates provided
            if ($request->has('center_lat') && $request->has('center_lon')) {
                $geoService = app(\App\Services\GeographicService::class);
                $data['distance_km'] = $geoService->calculateDistance(
                    $request->input('center_lat'),
                    $request->input('center_lon'),
                    $village->latitude,
                    $village->longitude
                );
                $data['bearing'] = $geoService->calculateBearing(
                    $request->input('center_lat'),
                    $request->input('center_lon'),
                    $village->latitude,
                    $village->longitude
                );
            }

            return $data;
        });

        return response()->json([
            'success' => true,
            'villages' => $villages
        ]);
    }

    /**
     * Calculate distance between two villages
     *
     * @authenticated
     *
     * @queryParam village1_id integer required First village ID. Example: 1
     * @queryParam village2_id integer required Second village ID. Example: 2
     *
     * @response 200 {
     *   "distance_km": 15.5,
     *   "bearing": 245.3,
     *   "travel_time_minutes": 23
     * }
     *
     * @tag Geographic Data
     */
    public function calculateDistance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'village1_id' => 'required|integer|exists:villages,id',
            'village2_id' => 'required|integer|exists:villages,id',
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

        $village1 = Village::where('player_id', $player->id)->find($request->input('village1_id'));
        $village2 = Village::where('player_id', $player->id)->find($request->input('village2_id'));

        if (!$village1 || !$village2) {
            return response()->json([
                'success' => false,
                'message' => 'One or both villages not found or not owned by player'
            ], 404);
        }

        $geoService = app(\App\Services\GeographicService::class);

        $distance = $geoService->calculateDistance(
            $village1->latitude ?? 0,
            $village1->longitude ?? 0,
            $village2->latitude ?? 0,
            $village2->longitude ?? 0
        );

        $bearing = $geoService->calculateBearing(
            $village1->latitude ?? 0,
            $village1->longitude ?? 0,
            $village2->latitude ?? 0,
            $village2->longitude ?? 0
        );

        $travelTime = $geoService->calculateTravelTimeFromDistance($distance);

        return response()->json([
            'success' => true,
            'distance_km' => round($distance, 2),
            'bearing' => round($bearing, 1),
            'travel_time_minutes' => $travelTime
        ]);
    }

    /**
     * Calculate player rank based on points
     */
    private function calculatePlayerRank(Player $player): int
    {
        return Player::where('world_id', $player->world_id)
            ->where('points', '>', $player->points)
            ->count() + 1;
    }
}
