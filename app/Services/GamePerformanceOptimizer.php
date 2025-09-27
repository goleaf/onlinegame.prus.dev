<?php

namespace App\Services;

use App\Services\EnhancedCacheService;
use App\Services\EnhancedSessionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SmartCache\Facades\SmartCache;

/**
 * Game Performance Optimizer using Laravel 12.29.0+ features
 * Integrates enhanced caching and session management for optimal game performance
 */
class GamePerformanceOptimizer
{
    protected EnhancedCacheService $cacheService;
    protected EnhancedSessionService $sessionService;
    protected array $performanceMetrics = [];

    public function __construct(
        EnhancedCacheService $cacheService,
        EnhancedSessionService $sessionService
    ) {
        $this->cacheService = $cacheService;
        $this->sessionService = $sessionService;
    }

    /**
     * Optimize game data loading with SmartCache
     */
    public function optimizeGameData(string $userId, array $dataTypes = []): array
    {
        $startTime = microtime(true);
        $results = [];

        foreach ($dataTypes as $type) {
            $cacheKey = "game_data_{$userId}_{$type}";

            $results[$type] = SmartCache::remember(
                $cacheKey,
                now()->addMinutes(30),
                function () use ($type, $userId) {
                    return $this->loadGameData($type, $userId);
                }
            );
        }

        $this->performanceMetrics['game_data_loading'] = microtime(true) - $startTime;
        return $results;
    }

    /**
     * Optimize session data with compression
     */
    public function optimizeSessionData(string $userId, array $sessionData): void
    {
        $sessionKey = "optimized_game_session_{$userId}";
        $tags = ["user:{$userId}", "session_data"];

        $this->sessionService->putWithTags($sessionKey, $sessionData, $tags);
    }

    /**
     * Warm up cache for frequently accessed game data using SmartCache
     */
    public function warmUpGameCache(array $userIds): void
    {
        foreach ($userIds as $userId) {
            // Warm up user stats
            SmartCache::remember(
                "user_stats_{$userId}",
                now()->addMinutes(30),
                function () use ($userId) {
                    return $this->loadUserStats($userId);
                }
            );

            // Warm up village data
            SmartCache::remember(
                "village_data_{$userId}",
                now()->addMinutes(30),
                function () use ($userId) {
                    return $this->loadVillageData($userId);
                }
            );

            // Warm up troop data
            SmartCache::remember(
                "troop_data_{$userId}",
                now()->addMinutes(30),
                function () use ($userId) {
                    return $this->loadTroopData($userId);
                }
            );
        }
    }

    /**
     * Optimize database queries with SmartCache
     */
    public function optimizeQueries(string $queryType, array $params = []): mixed
    {
        $cacheKey = "optimized_query_{$queryType}_" . md5(serialize($params));

        return SmartCache::remember(
            $cacheKey,
            now()->addMinutes(15),
            function () use ($queryType, $params) {
                return $this->executeOptimizedQuery($queryType, $params);
            }
        );
    }

    /**
     * Get comprehensive performance metrics
     */
    public function getPerformanceMetrics(): array
    {
        $cacheStats = $this->cacheService->getStats();
        $sessionStats = $this->sessionService->getStats();

        return [
            'cache_performance' => $cacheStats,
            'session_performance' => $sessionStats,
            'optimization_metrics' => $this->performanceMetrics,
            'database_metrics' => $this->getDatabaseMetrics(),
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'formatted_current' => $this->formatBytes(memory_get_usage(true)),
                'formatted_peak' => $this->formatBytes(memory_get_peak_usage(true)),
            ],
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Clean up expired cache and session data
     */
    public function cleanupExpiredData(): array
    {
        $results = [
            'cache_cleaned' => 0,
            'sessions_cleaned' => 0,
            'execution_time' => 0,
        ];

        $startTime = microtime(true);

        try {
            // Clean up expired cache entries using SmartCache
            SmartCache::flush();
            
            // Clean up expired sessions
            $results['sessions_cleaned'] = $this->sessionService->cleanupExpiredSessions();
            
            $results['cache_cleaned'] = 1; // Cache cleanup completed
        } catch (\Exception $e) {
            Log::error('Game performance optimizer cleanup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        $results['execution_time'] = microtime(true) - $startTime;
        return $results;
    }

    /**
     * Load game data based on type
     */
    protected function loadGameData(string $type, string $userId): array
    {
        return match ($type) {
            'user_stats' => $this->loadUserStats($userId),
            'village_data' => $this->loadVillageData($userId),
            'troop_data' => $this->loadTroopData($userId),
            'building_data' => $this->loadBuildingData($userId),
            'resource_data' => $this->loadResourceData($userId),
            default => [],
        };
    }

    /**
     * Load user statistics
     */
    protected function loadUserStats(string $userId): array
    {
        $user = DB::table('users')
            ->select([
                'id', 'name', 'email', 'created_at', 'updated_at'
            ])
            ->where('id', $userId)
            ->first();
            
        return $user ? (array) $user : [];
    }

    /**
     * Load village data
     */
    protected function loadVillageData(string $userId): array
    {
        // Check if villages table exists and has data
        try {
            $villages = DB::table('villages')
                ->select(['id', 'name', 'created_at'])
                ->where('user_id', $userId)
                ->get();
                
            return $villages ? $villages->toArray() : [];
        } catch (\Exception $e) {
            // Return empty array if table doesn't exist
            return [];
        }
    }

    /**
     * Load troop data
     */
    protected function loadTroopData(string $userId): array
    {
        try {
            $troops = DB::table('troops')
                ->select(['id', 'created_at'])
                ->where('user_id', $userId)
                ->get();
                
            return $troops ? $troops->toArray() : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Load building data
     */
    protected function loadBuildingData(string $userId): array
    {
        try {
            $buildings = DB::table('buildings')
                ->select(['id', 'created_at'])
                ->where('user_id', $userId)
                ->get();
                
            return $buildings ? $buildings->toArray() : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Load resource data
     */
    protected function loadResourceData(string $userId): array
    {
        try {
            $resources = DB::table('resources')
                ->select(['id', 'created_at'])
                ->where('user_id', $userId)
                ->get();
                
            return $resources ? $resources->toArray() : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Execute optimized database query
     */
    protected function executeOptimizedQuery(string $queryType, array $params): mixed
    {
        return match ($queryType) {
            'user_rankings' => $this->getUserRankings($params),
            'village_statistics' => $this->getVillageStatistics($params),
            'battle_history' => $this->getBattleHistory($params),
            'resource_production' => $this->getResourceProduction($params),
            default => null,
        };
    }

    /**
     * Get user rankings
     */
    protected function getUserRankings(array $params): array
    {
        $limit = $params['limit'] ?? 100;
        
        $users = DB::table('users')
            ->select(['id', 'name', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
            
        return $users ? $users->toArray() : [];
    }

    /**
     * Get village statistics
     */
    protected function getVillageStatistics(array $params): array
    {
        try {
            return DB::table('villages')
                ->selectRaw('COUNT(*) as total_villages')
                ->first()
                ?->toArray() ?? [];
        } catch (\Exception $e) {
            return ['total_villages' => 0];
        }
    }

    /**
     * Get battle history
     */
    protected function getBattleHistory(array $params): array
    {
        $limit = $params['limit'] ?? 50;
        
        try {
            $battles = DB::table('battles')
                ->select(['id', 'created_at'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
                
            return $battles ? $battles->toArray() : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get resource production data
     */
    protected function getResourceProduction(array $params): array
    {
        try {
            return DB::table('resources')
                ->selectRaw('COUNT(*) as total_resources')
                ->first()
                ?->toArray() ?? [];
        } catch (\Exception $e) {
            return ['total_resources' => 0];
        }
    }

    /**
     * Get database performance metrics
     */
    protected function getDatabaseMetrics(): array
    {
        try {
            $queryCount = DB::getQueryLog();
            $connectionCount = DB::getConnections();
            
            return [
                'total_queries' => count($queryCount),
                'active_connections' => count($connectionCount),
                'query_execution_time' => array_sum(array_column($queryCount, 'time')),
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Unable to retrieve database metrics',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
