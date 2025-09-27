<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Services\GameQueryEnrichService;
use Illuminate\Support\Facades\Auth;

class GameController extends Controller
{
    public function __construct()
    {
        // Middleware is applied in routes/game.php
    }

    public function dashboard()
    {
        $startTime = microtime(true);
        
        ds('GameController: Dashboard request started', [
            'controller' => 'GameController',
            'method' => 'dashboard',
            'request_time' => now(),
            'user_id' => Auth::id()
        ]);
        
        try {
            $user = Auth::user();
            if (!$user) {
                ds('GameController: User not authenticated', [
                    'redirect_to' => 'login'
                ]);
                return redirect()->route('login');
            }

            $player = \App\Models\Game\Player::where('user_id', $user->id)->first();
            if (!$player) {
                ds('GameController: No player found for user', [
                    'user_id' => $user->id
                ]);
                return view('game.no-player', compact('user'));
            }

            // Use Query Enrich service for enhanced dashboard data
            $queryStart = microtime(true);
            $dashboardData = GameQueryEnrichService::getPlayerDashboardData($player->id, $player->world_id);
            $queryTime = round((microtime(true) - $queryStart) * 1000, 2);
            
            $totalTime = round((microtime(true) - $startTime) * 1000, 2);
            
            ds('GameController: Dashboard data loaded successfully', [
                'player_id' => $player->id,
                'world_id' => $player->world_id,
                'query_time_ms' => $queryTime,
                'total_time_ms' => $totalTime,
                'dashboard_data_keys' => array_keys($dashboardData ?? [])
            ]);
            
            return view('game.dashboard', compact('dashboardData'));
        } catch (\Exception $e) {
            ds('GameController: Dashboard error occurred', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);
            return view('game.error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get enhanced player statistics using Query Enrich
     */
    public function getPlayerStats($playerId)
    {
        try {
            $dashboardData = GameQueryEnrichService::getPlayerDashboardData($playerId);
            
            return response()->json([
                'success' => true,
                'data' => $dashboardData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get world leaderboard using Query Enrich
     */
    public function getWorldLeaderboard($worldId)
    {
        try {
            $leaderboard = GameQueryEnrichService::getWorldLeaderboard($worldId, 100)->get();
            
            return response()->json([
                'success' => true,
                'data' => $leaderboard
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get building statistics using Query Enrich
     */
    public function getBuildingStats($playerId)
    {
        try {
            $buildingStats = GameQueryEnrichService::getBuildingStatistics($playerId)->get();
            
            return response()->json([
                'success' => true,
                'data' => $buildingStats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get resource capacity warnings using Query Enrich
     */
    public function getResourceWarnings($playerId)
    {
        try {
            $warnings = GameQueryEnrichService::getResourceCapacityWarnings($playerId, 24)->get();
            
            return response()->json([
                'success' => true,
                'data' => $warnings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function village($village)
    {
        return view('game.village', compact('village'));
    }

    public function troops()
    {
        return view('game.troops');
    }

    public function movements()
    {
        return view('game.movements');
    }

    public function alliance()
    {
        return view('game.alliance');
    }

    public function quests()
    {
        return view('game.quests');
    }

    public function technology()
    {
        return view('game.technology');
    }

    public function reports()
    {
        return view('game.reports');
    }

    public function map()
    {
        return view('game.map');
    }

    public function statistics()
    {
        return view('game.statistics');
    }

    public function realTime()
    {
        return view('game.real-time');
    }

    public function battles()
    {
        return view('game.battles');
    }

    public function market()
    {
        return view('game.market');
    }
}
