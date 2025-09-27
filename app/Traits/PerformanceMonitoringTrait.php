<?php

namespace App\Traits;

use App\Services\PerformanceMonitoringService;

trait PerformanceMonitoringTrait
{
    /**
     * Monitor queries for Livewire component methods
     */
    protected function monitorQueries(callable $callback, string $method = 'unknown'): mixed
    {
        $componentName = class_basename(static::class);
        return PerformanceMonitoringService::monitorQueries($callback, "{$componentName}::{$method}");
    }
    
    /**
     * Get performance statistics for this component
     */
    protected function getPerformanceStats(): array
    {
        $componentName = class_basename(static::class);
        return PerformanceMonitoringService::getPerformanceStats($componentName);
    }
    
    /**
     * Optimize queries with eager loading
     */
    protected function optimizeQueries($query, array $relationships = []): object
    {
        return PerformanceMonitoringService::optimizeQueries($query, $relationships);
    }
    
    /**
     * Get performance metrics for debugging
     */
    protected function getPerformanceMetrics(): array
    {
        return [
            'component' => class_basename(static::class),
            'monitoring_enabled' => config('mysql-performance.middleware.enabled', true),
            'slow_threshold_ms' => config('mysql-performance.middleware.slow_request_threshold', 100),
            'high_query_threshold' => config('mysql-performance.middleware.high_query_threshold', 50),
        ];
    }
}
