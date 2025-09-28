<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Game Performance Monitor
 * Tracks and monitors game performance metrics
 */
class GamePerformanceMonitor
{
    /**
     * Monitor response time for operations
     */
    public static function monitorResponseTime(string $operation, float $startTime): array
    {
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $metrics = [
            'operation' => $operation,
            'response_time_ms' => round($responseTime, 2),
            'timestamp' => now()->toISOString(),
            'memory_usage' => memory_get_usage(true),
        ];

        // Log slow operations
        if ($responseTime > 1000) { // More than 1 second
            Log::warning('Slow operation detected', $metrics);
        }

        // Cache performance data
        self::cachePerformanceData($operation, $metrics);

        return $metrics;
    }

    /**
     * Monitor memory usage
     */
    public static function monitorMemory(string $operation): array
    {
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);

        $metrics = [
            'operation' => $operation,
            'current_memory' => $memoryUsage,
            'peak_memory' => $peakMemory,
            'formatted_current' => self::formatBytes($memoryUsage),
            'formatted_peak' => self::formatBytes($peakMemory),
            'timestamp' => now()->toISOString(),
        ];

        // Log high memory usage
        if ($memoryUsage > 128 * 1024 * 1024) { // More than 128MB
            Log::warning('High memory usage detected', $metrics);
        }

        return $metrics;
    }

    /**
     * Monitor database query performance
     */
    public static function monitorDatabaseQueries(): array
    {
        $queries = DB::getQueryLog();
        $totalTime = 0;
        $slowQueries = [];

        foreach ($queries as $query) {
            $totalTime += $query['time'];

            if ($query['time'] > 100) { // More than 100ms
                $slowQueries[] = [
                    'query' => $query['query'],
                    'time' => $query['time'],
                    'bindings' => $query['bindings'],
                ];
            }
        }

        $metrics = [
            'total_queries' => count($queries),
            'total_time_ms' => round($totalTime, 2),
            'average_time_ms' => count($queries) > 0 ? round($totalTime / count($queries), 2) : 0,
            'slow_queries' => $slowQueries,
            'timestamp' => now()->toISOString(),
        ];

        // Log slow queries
        if (! empty($slowQueries)) {
            Log::warning('Slow database queries detected', $metrics);
        }

        return $metrics;
    }

    /**
     * Get comprehensive performance statistics
     */
    public static function getPerformanceStats(): array
    {
        return [
            'memory' => self::monitorMemory('performance_stats'),
            'database' => self::monitorDatabaseQueries(),
            'cache' => self::getCachePerformance(),
            'system' => self::getSystemMetrics(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Get cache performance metrics
     */
    private static function getCachePerformance(): array
    {
        try {
            $cacheStats = Cache::getStore() instanceof \Illuminate\Cache\RedisStore
                ? \Illuminate\Support\Facades\Redis::info('stats')
                : [];

            return [
                'hits' => $cacheStats['keyspace_hits'] ?? 0,
                'misses' => $cacheStats['keyspace_misses'] ?? 0,
                'hit_ratio' => self::calculateHitRatio($cacheStats),
                'memory_used' => $cacheStats['used_memory'] ?? 0,
                'formatted_memory' => isset($cacheStats['used_memory'])
                    ? self::formatBytes($cacheStats['used_memory'])
                    : 'N/A',
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Unable to retrieve cache performance',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get system metrics
     */
    private static function getSystemMetrics(): array
    {
        return [
            'load_average' => sys_getloadavg(),
            'disk_free' => disk_free_space('/'),
            'disk_total' => disk_total_space('/'),
            'formatted_disk_free' => self::formatBytes(disk_free_space('/')),
            'formatted_disk_total' => self::formatBytes(disk_total_space('/')),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
        ];
    }

    /**
     * Cache performance data
     */
    private static function cachePerformanceData(string $operation, array $metrics): void
    {
        $cacheKey = "performance:{$operation}:".now()->format('Y-m-d-H');

        Cache::remember($cacheKey, now()->addHour(), function () use ($metrics) {
            return $metrics;
        });
    }

    /**
     * Calculate cache hit ratio
     */
    private static function calculateHitRatio(array $stats): float
    {
        $hits = $stats['keyspace_hits'] ?? 0;
        $misses = $stats['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
    }

    /**
     * Format bytes to human readable format
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2).' '.$units[$pow];
    }

    /**
     * Generate performance report
     */
    public static function generatePerformanceReport(): array
    {
        $report = [
            'summary' => self::getPerformanceStats(),
            'recommendations' => self::getPerformanceRecommendations(),
            'generated_at' => now()->toISOString(),
        ];

        // Log the report
        Log::info('Performance report generated', $report);

        return $report;
    }

    /**
     * Get performance recommendations
     */
    private static function getPerformanceRecommendations(): array
    {
        $recommendations = [];
        $stats = self::getPerformanceStats();

        // Memory recommendations
        if ($stats['memory']['current_memory'] > 64 * 1024 * 1024) {
            $recommendations[] = 'Consider optimizing memory usage - current usage is high';
        }

        // Database recommendations
        if ($stats['database']['average_time_ms'] > 50) {
            $recommendations[] = 'Database queries are slow - consider adding indexes or optimizing queries';
        }

        // Cache recommendations
        if ($stats['cache']['hit_ratio'] < 80) {
            $recommendations[] = 'Cache hit ratio is low - consider increasing cache duration or improving cache keys';
        }

        // System recommendations
        if ($stats['system']['load_average'][0] > 2.0) {
            $recommendations[] = 'System load is high - consider scaling or optimizing resource usage';
        }

        return $recommendations;
    }
}
