<?php

namespace App\Http\Controllers\Game;

use App\Services\GameQueryEnrichService;
use App\Services\ValueObjectService;
use App\Utilities\LoggingUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LaraUtilX\Http\Controllers\CrudController;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Traits\FileProcessingTrait;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\RateLimiterUtil;

class GameController extends CrudController
{
    use ApiResponseTrait;
    use FileProcessingTrait;

    protected RateLimiterUtil $rateLimiter;

    public function __construct(RateLimiterUtil $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
        // Middleware is applied in routes/game.php
        parent::__construct(null); // GameController doesn't use a specific model
    }

    public function dashboard()
    {
        $startTime = microtime(true);

        LoggingUtil::info('GameController: Dashboard request started', [
            'controller' => 'GameController',
            'method' => 'dashboard',
            'request_time' => now(),
            'user_id' => Auth::id(),
        ], 'game_system');

        try {
            $user = Auth::user();
            if (! $user) {
                LoggingUtil::warning('GameController: User not authenticated', [
                    'redirect_to' => 'login',
                ], 'game_system');

                return redirect()->route('login');
            }

            $player = \App\Models\Game\Player::where('user_id', $user->id)->first();
            if (! $player) {
                LoggingUtil::warning('GameController: No player found for user', [
                    'user_id' => $user->id,
                ], 'game_system');

                return view('game.no-player', compact('user'));
            }

            // Cache dashboard data for better performance
            $cacheKey = "player_dashboard_{$player->id}_{$player->world_id}";

            $dashboardData = CachingUtil::remember($cacheKey, now()->addMinutes(5), function () use ($player) {
                // Use Query Enrich service for enhanced dashboard data
                $queryStart = microtime(true);
                $data = GameQueryEnrichService::getPlayerDashboardData($player->id, $player->world_id);

                // Enhance with value objects
                $valueObjectService = app(ValueObjectService::class);
                $playerStats = $player->stats;
                $data['player_stats'] = $playerStats;
                $data['value_objects_integration'] = true;

                $queryTime = round((microtime(true) - $queryStart) * 1000, 2);
                $data['query_time_ms'] = $queryTime;

                return $data;
            });

            $totalTime = round((microtime(true) - $startTime) * 1000, 2);
            $dashboardData['total_time_ms'] = $totalTime;

            LoggingUtil::info('GameController: Dashboard data loaded successfully', [
                'player_id' => $player->id,
                'world_id' => $player->world_id,
                'query_time_ms' => $dashboardData['query_time_ms'] ?? 0,
                'total_time_ms' => $totalTime,
                'dashboard_data_keys' => array_keys($dashboardData ?? []),
            ], 'game_system');

            return view('game.dashboard', compact('dashboardData'));
        } catch (\Exception $e) {
            LoggingUtil::error('GameController: Dashboard error occurred', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'processing_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            ], 'game_system');

            return view('game.error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get enhanced player statistics using Query Enrich
     */
    public function getPlayerStats(Request $request, $playerId)
    {
        try {
            // Rate limiting for player stats
            $rateLimitKey = 'player_stats_'.($request->ip() ?? 'unknown');
            if (! $this->rateLimiter->attempt($rateLimitKey, 50, 1)) {
                return $this->errorResponse('Too many requests. Please try again later.', 429);
            }

            $cacheKey = "player_stats_{$playerId}";

            $dashboardData = CachingUtil::remember($cacheKey, now()->addMinutes(10), function () use ($playerId) {
                return GameQueryEnrichService::getPlayerDashboardData($playerId);
            });

            LoggingUtil::info('Player statistics retrieved', [
                'user_id' => auth()->id(),
                'player_id' => $playerId,
            ], 'game_system');

            return $this->successResponse($dashboardData, 'Player statistics retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving player statistics', [
                'error' => $e->getMessage(),
                'player_id' => $playerId,
            ], 'game_system');

            return $this->errorResponse('Failed to retrieve player statistics.', 500);
        }
    }

    /**
     * Get world leaderboard using Query Enrich
     */
    public function getWorldLeaderboard(Request $request, $worldId)
    {
        try {
            // Rate limiting for leaderboard
            $rateLimitKey = 'world_leaderboard_'.($request->ip() ?? 'unknown');
            if (! $this->rateLimiter->attempt($rateLimitKey, 20, 1)) {
                return $this->errorResponse('Too many requests. Please try again later.', 429);
            }

            $cacheKey = "world_leaderboard_{$worldId}";

            $leaderboard = CachingUtil::remember($cacheKey, now()->addMinutes(15), function () use ($worldId) {
                return GameQueryEnrichService::getWorldLeaderboard($worldId, 100)->get();
            });

            LoggingUtil::info('World leaderboard retrieved', [
                'user_id' => auth()->id(),
                'world_id' => $worldId,
                'leaderboard_count' => $leaderboard->count(),
            ], 'game_system');

            return $this->successResponse($leaderboard, 'World leaderboard retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving world leaderboard', [
                'error' => $e->getMessage(),
                'world_id' => $worldId,
            ], 'game_system');

            return $this->errorResponse('Failed to retrieve world leaderboard.', 500);
        }
    }

    /**
     * Get building statistics using Query Enrich
     */
    public function getBuildingStats(Request $request, $playerId)
    {
        try {
            $cacheKey = "building_stats_{$playerId}";

            $buildingStats = CachingUtil::remember($cacheKey, now()->addMinutes(10), function () use ($playerId) {
                return GameQueryEnrichService::getBuildingStatistics($playerId)->get();
            });

            LoggingUtil::info('Building statistics retrieved', [
                'user_id' => auth()->id(),
                'player_id' => $playerId,
            ], 'game_system');

            return $this->successResponse($buildingStats, 'Building statistics retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving building statistics', [
                'error' => $e->getMessage(),
                'player_id' => $playerId,
            ], 'game_system');

            return $this->errorResponse('Failed to retrieve building statistics.', 500);
        }
    }

    /**
     * Get resource capacity warnings using Query Enrich
     */
    public function getResourceWarnings(Request $request, $playerId)
    {
        try {
            $cacheKey = "resource_warnings_{$playerId}";

            $warnings = CachingUtil::remember($cacheKey, now()->addMinutes(5), function () use ($playerId) {
                return GameQueryEnrichService::getResourceCapacityWarnings($playerId, 24)->get();
            });

            LoggingUtil::info('Resource warnings retrieved', [
                'user_id' => auth()->id(),
                'player_id' => $playerId,
                'warnings_count' => $warnings->count(),
            ], 'game_system');

            return $this->successResponse($warnings, 'Resource warnings retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving resource warnings', [
                'error' => $e->getMessage(),
                'player_id' => $playerId,
            ], 'game_system');

            return $this->errorResponse('Failed to retrieve resource warnings.', 500);
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
