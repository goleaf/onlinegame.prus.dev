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
        try {
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login');
            }

            $player = \App\Models\Game\Player::where('user_id', $user->id)->first();
            if (!$player) {
                return view('game.no-player', compact('user'));
            }

            // Use Query Enrich service for enhanced dashboard data
            $dashboardData = GameQueryEnrichService::getPlayerDashboardData($player->id, $player->world_id);
            
            return view('game.dashboard', compact('dashboardData'));
        } catch (\Exception $e) {
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
