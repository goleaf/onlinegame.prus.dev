<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GameCacheService;
use App\Services\GameErrorHandler;
use App\Services\GamePerformanceMonitor;
use App\Utilities\GameUtility;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class GameApiController extends Controller
{
    /**
     * Get player data
     */
    public function getPlayer(Request $request, int $playerId): JsonResponse
    {
        $startTime = microtime(true);
        
        try {
            $player = GameCacheService::getPlayerData($playerId);
            
            if (!$player) {
                return response()->json(['error' => 'Player not found'], 404);
            }
            
            GamePerformanceMonitor::monitorResponseTime('api_get_player', $startTime);
            
            return response()->json([
                'success' => true,
                'data' => $player,
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            GameErrorHandler::handleGameError($e, [
                'action' => 'api_get_player',
                'player_id' => $playerId,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => GameErrorHandler::getUserFriendlyMessage($e),
            ], 500);
        }
    }

    /**
     * Get village data
     */
    public function getVillage(Request $request, int $villageId): JsonResponse
    {
        $startTime = microtime(true);
        
        try {
            $village = GameCacheService::getVillageData($villageId);
            
            if (!$village) {
                return response()->json(['error' => 'Village not found'], 404);
            }
            
            GamePerformanceMonitor::monitorResponseTime('api_get_village', $startTime);
            
            return response()->json([
                'success' => true,
                'data' => $village,
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            GameErrorHandler::handleGameError($e, [
                'action' => 'api_get_village',
                'village_id' => $villageId,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => GameErrorHandler::getUserFriendlyMessage($e),
            ], 500);
        }
    }

    /**
     * Get map data
     */
    public function getMapData(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lon' => 'required|numeric|between:-180,180',
            'radius' => 'integer|min:1|max:100',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }
        
        try {
            $lat = $request->input('lat');
            $lon = $request->input('lon');
            $radius = $request->input('radius', 10);
            
            $mapData = GameCacheService::getMapData($lat, $lon, $radius);
            
            GamePerformanceMonitor::monitorResponseTime('api_get_map', $startTime);
            
            return response()->json([
                'success' => true,
                'data' => $mapData,
                'center' => ['lat' => $lat, 'lon' => $lon],
                'radius' => $radius,
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            GameErrorHandler::handleGameError($e, [
                'action' => 'api_get_map',
                'lat' => $lat,
                'lon' => $lon,
                'radius' => $radius,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => GameErrorHandler::getUserFriendlyMessage($e),
            ], 500);
        }
    }

    /**
     * Get resource data
     */
    public function getResources(Request $request, int $villageId): JsonResponse
    {
        $startTime = microtime(true);
        
        try {
            $resources = GameCacheService::getResourceData($villageId);
            
            if (!$resources) {
                return response()->json(['error' => 'Village not found'], 404);
            }
            
            GamePerformanceMonitor::monitorResponseTime('api_get_resources', $startTime);
            
            return response()->json([
                'success' => true,
                'data' => $resources,
                'formatted' => [
                    'wood' => GameUtility::formatNumber($resources['wood']),
                    'clay' => GameUtility::formatNumber($resources['clay']),
                    'iron' => GameUtility::formatNumber($resources['iron']),
                    'crop' => GameUtility::formatNumber($resources['crop']),
                ],
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            GameErrorHandler::handleGameError($e, [
                'action' => 'api_get_resources',
                'village_id' => $villageId,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => GameErrorHandler::getUserFriendlyMessage($e),
            ], 500);
        }
    }

    /**
     * Get game statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        try {
            $type = $request->input('type', 'general');
            $stats = GameCacheService::getGameStatistics($type);
            
            GamePerformanceMonitor::monitorResponseTime('api_get_statistics', $startTime);
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'type' => $type,
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            GameErrorHandler::handleGameError($e, [
                'action' => 'api_get_statistics',
                'type' => $type,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => GameErrorHandler::getUserFriendlyMessage($e),
            ], 500);
        }
    }

    /**
     * Get leaderboard
     */
    public function getLeaderboard(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        $validator = Validator::make($request->all(), [
            'type' => 'string|in:points,villages,alliances',
            'limit' => 'integer|min:1|max:1000',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }
        
        try {
            $type = $request->input('type', 'points');
            $limit = $request->input('limit', 100);
            
            $leaderboard = GameCacheService::getLeaderboard($type, $limit);
            
            GamePerformanceMonitor::monitorResponseTime('api_get_leaderboard', $startTime);
            
            return response()->json([
                'success' => true,
                'data' => $leaderboard,
                'type' => $type,
                'limit' => $limit,
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            GameErrorHandler::handleGameError($e, [
                'action' => 'api_get_leaderboard',
                'type' => $type,
                'limit' => $limit,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => GameErrorHandler::getUserFriendlyMessage($e),
            ], 500);
        }
    }

    /**
     * Calculate travel time
     */
    public function calculateTravelTime(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        $validator = Validator::make($request->all(), [
            'from_lat' => 'required|numeric|between:-90,90',
            'from_lon' => 'required|numeric|between:-180,180',
            'to_lat' => 'required|numeric|between:-90,90',
            'to_lon' => 'required|numeric|between:-180,180',
            'speed' => 'numeric|min:0.1|max:100',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }
        
        try {
            $fromLat = $request->input('from_lat');
            $fromLon = $request->input('from_lon');
            $toLat = $request->input('to_lat');
            $toLon = $request->input('to_lon');
            $speed = $request->input('speed', 10.0);
            
            $distance = GameUtility::calculateDistance($fromLat, $fromLon, $toLat, $toLon);
            $travelTime = GameUtility::calculateTravelTime($fromLat, $fromLon, $toLat, $toLon, $speed);
            
            GamePerformanceMonitor::monitorResponseTime('api_calculate_travel', $startTime);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'distance_km' => round($distance, 2),
                    'travel_time_seconds' => $travelTime,
                    'travel_time_formatted' => GameUtility::formatDuration($travelTime),
                    'speed_kmh' => $speed,
                    'from' => ['lat' => $fromLat, 'lon' => $fromLon],
                    'to' => ['lat' => $toLat, 'lon' => $toLon],
                ],
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            GameErrorHandler::handleGameError($e, [
                'action' => 'api_calculate_travel',
                'from_lat' => $fromLat,
                'from_lon' => $fromLon,
                'to_lat' => $toLat,
                'to_lon' => $toLon,
                'speed' => $speed,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => GameErrorHandler::getUserFriendlyMessage($e),
            ], 500);
        }
    }

    /**
     * Calculate battle points
     */
    public function calculateBattlePoints(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        
        $validator = Validator::make($request->all(), [
            'units' => 'required|array',
            'units.infantry' => 'integer|min:0',
            'units.archer' => 'integer|min:0',
            'units.cavalry' => 'integer|min:0',
            'units.siege' => 'integer|min:0',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }
        
        try {
            $units = $request->input('units');
            $battlePoints = GameUtility::calculateBattlePoints($units);
            
            GamePerformanceMonitor::monitorResponseTime('api_calculate_battle_points', $startTime);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'units' => $units,
                    'battle_points' => $battlePoints,
                    'battle_points_formatted' => GameUtility::formatNumber($battlePoints),
                ],
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            GameErrorHandler::handleGameError($e, [
                'action' => 'api_calculate_battle_points',
                'units' => $units,
            ]);
            
            return response()->json([
                'success' => false,
                'error' => GameErrorHandler::getUserFriendlyMessage($e),
            ], 500);
        }
    }

    /**
     * Get performance metrics
     */
    public function getPerformanceMetrics(Request $request): JsonResponse
    {
        try {
            $metrics = GamePerformanceMonitor::getPerformanceStats();
            
            return response()->json([
                'success' => true,
                'data' => $metrics,
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            GameErrorHandler::handleGameError($e, [
                'action' => 'api_get_performance_metrics',
            ]);
            
            return response()->json([
                'success' => false,
                'error' => GameErrorHandler::getUserFriendlyMessage($e),
            ], 500);
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStatistics(Request $request): JsonResponse
    {
        try {
            $stats = GameCacheService::getCacheStatistics();
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            GameErrorHandler::handleGameError($e, [
                'action' => 'api_get_cache_statistics',
            ]);
            
            return response()->json([
                'success' => false,
                'error' => GameErrorHandler::getUserFriendlyMessage($e),
            ], 500);
        }
    }

    /**
     * Generate random game event
     */
    public function generateRandomEvent(Request $request): JsonResponse
    {
        try {
            $event = GameUtility::generateRandomEvent();
            
            GameErrorHandler::logGameAction('api_generate_random_event', [
                'event_type' => $event['type'],
                'user_id' => auth()->id(),
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $event,
                'timestamp' => now()->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            GameErrorHandler::handleGameError($e, [
                'action' => 'api_generate_random_event',
            ]);
            
            return response()->json([
                'success' => false,
                'error' => GameErrorHandler::getUserFriendlyMessage($e),
            ], 500);
        }
    }
}
