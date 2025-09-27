<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GamePerformanceMonitor
{
    /**
     * Monitor database query performance
     */
    public static function monitorQuery(string $query, float $executionTime): void
    {
        $threshold = config('game.performance.query_threshold', 1.0); // 1 second threshold
        
        if ($executionTime > $threshold) {
            Log::channel('performance')->warning('Slow Query Detected', [
                'query' => $query,
                'execution_time' => $executionTime,
                'threshold' => $threshold,
                'timestamp' => now()->toISOString(),
            ]);
        }

        // Store query statistics
        $stats = Cache::get('query_stats', []);
        $stats[] = [
            'query' => substr($query, 0, 100), // Truncate for storage
            'execution_time' => $executionTime,
            'timestamp' => now()->timestamp,
        ];

        // Keep only last 100 queries
        if (count($stats) > 100) {
            $stats = array_slice($stats, -100);
        }

        Cache::put('query_stats', $stats, 3600); // Cache for 1 hour
    }

    /**
     * Monitor memory usage
     */
    public static function monitorMemory(string $operation): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        
        $metrics = [
            'operation' => $operation,
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'memory_peak_mb' => round($memoryPeak / 1024 / 1024, 2),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('performance')->info('Memory Usage', $metrics);

        return $metrics;
    }

    /**
     * Monitor response time for game actions
     */
    public static function monitorResponseTime(string $action, float $startTime): void
    {
        $responseTime = microtime(true) - $startTime;
        $threshold = config('game.performance.response_threshold', 2.0); // 2 seconds threshold

        if ($responseTime > $threshold) {
            Log::channel('performance')->warning('Slow Response Time', [
                'action' => $action,
                'response_time' => $responseTime,
                'threshold' => $threshold,
                'timestamp' => now()->toISOString(),
            ]);
        }

        // Store response time statistics
        $stats = Cache::get('response_stats', []);
        $stats[] = [
            'action' => $action,
            'response_time' => $responseTime,
            'timestamp' => now()->timestamp,
        ];

        // Keep only last 100 responses
        if (count($stats) > 100) {
            $stats = array_slice($stats, -100);
        }

        Cache::put('response_stats', $stats, 3600);
    }

    /**
     * Get performance statistics
     */
    public static function getPerformanceStats(): array
    {
        $queryStats = Cache::get('query_stats', []);
        $responseStats = Cache::get('response_stats', []);
        $memoryStats = Cache::get('memory_stats', []);

        return [
            'queries' => [
                'total' => count($queryStats),
                'average_time' => count($queryStats) > 0 ? 
                    round(array_sum(array_column($queryStats, 'execution_time')) / count($queryStats), 4) : 0,
                'slow_queries' => count(array_filter($queryStats, function($q) {
                    return $q['execution_time'] > 1.0;
                })),
            ],
            'responses' => [
                'total' => count($responseStats),
                'average_time' => count($responseStats) > 0 ? 
                    round(array_sum(array_column($responseStats, 'response_time')) / count($responseStats), 4) : 0,
                'slow_responses' => count(array_filter($responseStats, function($r) {
                    return $r['response_time'] > 2.0;
                })),
            ],
            'memory' => [
                'current_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'limit_mb' => round(ini_get('memory_limit') ?: 128, 2),
            ],
            'database' => [
                'active_connections' => DB::select('SHOW STATUS LIKE "Threads_connected"')[0]->Value ?? 0,
                'slow_queries' => DB::select('SHOW STATUS LIKE "Slow_queries"')[0]->Value ?? 0,
            ],
        ];
    }

    /**
     * Monitor concurrent users
     */
    public static function monitorConcurrentUsers(): int
    {
        $activeUsers = Cache::get('active_users', []);
        $currentTime = now()->timestamp;
        
        // Remove users inactive for more than 5 minutes
        $activeUsers = array_filter($activeUsers, function($lastActivity) use ($currentTime) {
            return ($currentTime - $lastActivity) < 300; // 5 minutes
        });

        Cache::put('active_users', $activeUsers, 600); // Cache for 10 minutes
        
        return count($activeUsers);
    }

    /**
     * Track user activity
     */
    public static function trackUserActivity(int $userId): void
    {
        $activeUsers = Cache::get('active_users', []);
        $activeUsers[$userId] = now()->timestamp;
        
        // Clean up old entries
        $currentTime = now()->timestamp;
        $activeUsers = array_filter($activeUsers, function($lastActivity) use ($currentTime) {
            return ($currentTime - $lastActivity) < 300; // 5 minutes
        });

        Cache::put('active_users', $activeUsers, 600);
    }

    /**
     * Monitor game server load
     */
    public static function monitorServerLoad(): array
    {
        $loadAvg = sys_getloadavg();
        $memoryInfo = [
            'total' => 0,
            'free' => 0,
            'used' => 0,
        ];

        if (is_readable('/proc/meminfo')) {
            $meminfo = file_get_contents('/proc/meminfo');
            preg_match('/MemTotal:\s+(\d+)/', $meminfo, $total);
            preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $available);
            
            if (isset($total[1]) && isset($available[1])) {
                $memoryInfo['total'] = $total[1] * 1024; // Convert from KB to bytes
                $memoryInfo['free'] = $available[1] * 1024;
                $memoryInfo['used'] = $memoryInfo['total'] - $memoryInfo['free'];
            }
        }

        return [
            'load_average' => $loadAvg,
            'memory' => $memoryInfo,
            'cpu_count' => PHP_OS_FAMILY === 'Linux' ? 
                (int) shell_exec('nproc') : 1,
            'uptime' => PHP_OS_FAMILY === 'Linux' ? 
                trim(shell_exec('uptime')) : 'Unknown',
        ];
    }

    /**
     * Generate performance report
     */
    public static function generatePerformanceReport(): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'performance_stats' => self::getPerformanceStats(),
            'concurrent_users' => self::monitorConcurrentUsers(),
            'server_load' => self::monitorServerLoad(),
            'recommendations' => self::getPerformanceRecommendations(),
        ];
    }

    /**
     * Get performance recommendations
     */
    private static function getPerformanceRecommendations(): array
    {
        $stats = self::getPerformanceStats();
        $recommendations = [];

        if ($stats['queries']['average_time'] > 0.5) {
            $recommendations[] = 'Consider optimizing database queries - average query time is high.';
        }

        if ($stats['responses']['average_time'] > 1.0) {
            $recommendations[] = 'Consider optimizing response times - average response time is high.';
        }

        if ($stats['memory']['current_usage_mb'] > 100) {
            $recommendations[] = 'High memory usage detected - consider implementing caching or memory optimization.';
        }

        if ($stats['database']['slow_queries'] > 10) {
            $recommendations[] = 'High number of slow queries detected - review database indexes.';
        }

        return $recommendations;
    }

    /**
     * Clear performance statistics
     */
    public static function clearPerformanceStats(): void
    {
        Cache::forget('query_stats');
        Cache::forget('response_stats');
        Cache::forget('memory_stats');
        Cache::forget('active_users');
        
        Log::info('Performance statistics cleared');
    }
}
