<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceMonitoringService
{
    /**
     * Monitor query performance for Livewire components
     */
    public static function monitorQueries(callable $callback, string $component = 'unknown')
    {
        $startTime = microtime(true);
        $startQueries = count(DB::getQueryLog());
        
        DB::enableQueryLog();
        
        try {
            $result = $callback();
            
            $endTime = microtime(true);
            $endQueries = count(DB::getQueryLog());
            
            $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            $queryCount = $endQueries - $startQueries;
            
            // Log performance metrics
            self::logPerformanceMetrics($component, $executionTime, $queryCount);
            
            return $result;
        } catch (\Exception $e) {
            Log::error("Performance monitoring error in {$component}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Log performance metrics
     */
    private static function logPerformanceMetrics(string $component, float $executionTime, int $queryCount): void
    {
        $config = config('mysql-performance.middleware', []);
        $slowThreshold = $config['slow_request_threshold'] ?? 100;
        $highQueryThreshold = $config['high_query_threshold'] ?? 50;
        
        $metrics = [
            'component' => $component,
            'execution_time_ms' => round($executionTime, 2),
            'query_count' => $queryCount,
            'is_slow_request' => $executionTime > $slowThreshold,
            'is_high_query_count' => $queryCount > $highQueryThreshold,
        ];
        
        if ($metrics['is_slow_request'] || $metrics['is_high_query_count']) {
            Log::warning("Performance issue detected in {$component}", $metrics);
        } else {
            Log::info("Performance metrics for {$component}", $metrics);
        }
    }
    
    /**
     * Get performance statistics for a component
     */
    public static function getPerformanceStats(string $component): array
    {
        return [
            'component' => $component,
            'monitoring_enabled' => config('mysql-performance.middleware.enabled', true),
            'slow_threshold' => config('mysql-performance.middleware.slow_request_threshold', 100),
            'high_query_threshold' => config('mysql-performance.middleware.high_query_threshold', 50),
        ];
    }
    
    /**
     * Optimize queries with eager loading
     */
    public static function optimizeQueries($query, array $relationships = []): object
    {
        if (!empty($relationships)) {
            return $query->with($relationships);
        }
        
        return $query;
    }
}
