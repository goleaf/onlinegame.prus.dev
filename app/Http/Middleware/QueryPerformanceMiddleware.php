<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Closure;

class QueryPerformanceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only enable in non-production environments
        if (app()->isProduction()) {
            return $next($request);
        }

        $startTime = microtime(true);
        $startQueries = count(DB::getQueryLog());

        ds('QueryPerformanceMiddleware: Query monitoring started', [
            'middleware' => 'QueryPerformanceMiddleware',
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'initial_queries' => $startQueries,
            'monitoring_start_time' => now()
        ]);

        // Enable query logging
        DB::enableQueryLog();

        $response = $next($request);

        $endTime = microtime(true);
        $endQueries = count(DB::getQueryLog());
        $executionTime = ($endTime - $startTime) * 1000;  // Convert to milliseconds
        $queryCount = $endQueries - $startQueries;

        ds('QueryPerformanceMiddleware: Query monitoring completed', [
            'total_execution_time_ms' => round($executionTime, 2),
            'total_queries_executed' => $queryCount,
            'final_query_count' => $endQueries,
            'status_code' => $response->getStatusCode(),
            'memory_usage' => memory_get_usage(true),
            'slow_query_detected' => $executionTime > (config('mysql-performance.slow_query_log.long_query_time', 1.0) * 1000)
        ]);

        // Log performance metrics
        $this->logPerformanceMetrics($request, $executionTime, $queryCount);

        // Add performance headers for debugging
        $response->headers->set('X-Query-Count', $queryCount);
        $response->headers->set('X-Execution-Time', round($executionTime, 2) . 'ms');

        return $response;
    }

    /**
     * Log performance metrics
     */
    private function logPerformanceMetrics(Request $request, float $executionTime, int $queryCount): void
    {
        $threshold = config('mysql-performance.slow_query_log.long_query_time', 1.0) * 1000;  // Convert to ms
        $slowQueryThreshold = 100;  // ms
        $highQueryThreshold = 50;  // queries

        $metrics = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'execution_time_ms' => round($executionTime, 2),
            'query_count' => $queryCount,
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // Log slow requests
        if ($executionTime > $threshold) {
            Log::warning('Slow request detected', array_merge($metrics, [
                'type' => 'slow_request',
                'threshold_ms' => $threshold,
            ]));
        }

        // Log high query count requests
        if ($queryCount > $highQueryThreshold) {
            Log::warning('High query count detected', array_merge($metrics, [
                'type' => 'high_query_count',
                'threshold' => $highQueryThreshold,
            ]));
        }

        // Log all requests in debug mode
        if (config('app.debug')) {
            Log::debug('Request performance metrics', $metrics);
        }
    }
}
