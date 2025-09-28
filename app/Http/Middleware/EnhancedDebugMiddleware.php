<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enhanced Debug Middleware for Laravel 12.29.0+ features
 * Provides improved debug page experience with dark/light mode detection
 */
class EnhancedDebugMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        ds('EnhancedDebugMiddleware: Request processing started', [
            'middleware' => 'EnhancedDebugMiddleware',
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'request_time' => now(),
        ]);

        $response = $next($request);

        // Only apply enhanced debug features in development
        if (app()->environment('local', 'development') && config('app.debug')) {
            $this->addDebugHeaders($request, $response);
        }

        $totalTime = round((microtime(true) - $startTime) * 1000, 2);

        ds('EnhancedDebugMiddleware: Request processing completed', [
            'total_time_ms' => $totalTime,
            'status_code' => $response->getStatusCode(),
            'response_size' => strlen($response->getContent()),
            'memory_usage' => memory_get_usage(true),
            'debug_enabled' => app()->environment('local', 'development') && config('app.debug'),
        ]);

        return $response;
    }

    /**
     * Add enhanced debug headers
     */
    protected function addDebugHeaders(Request $request, Response $response): void
    {
        // Detect user's preferred color scheme
        $prefersDark = $this->detectDarkMode($request);

        // Add debug enhancement headers
        $response->headers->set('X-Debug-Enhanced', 'true');
        $response->headers->set('X-Debug-Theme', $prefersDark ? 'dark' : 'light');
        $response->headers->set('X-Debug-Version', '12.29.0+');
        $response->headers->set('X-Debug-Features', 'enhanced-ui,auto-theme,performance-metrics');

        // Add performance metrics
        $this->addPerformanceMetrics($response);
    }

    /**
     * Detect if user prefers dark mode
     */
    protected function detectDarkMode(Request $request): bool
    {
        // Check for explicit theme preference
        $theme = $request->cookie('theme');
        if ($theme) {
            return $theme === 'dark';
        }

        // Check for system preference
        $prefersColorScheme = $request->header('Sec-CH-Prefers-Color-Scheme');
        if ($prefersColorScheme) {
            return $prefersColorScheme === 'dark';
        }

        // Check for legacy header
        $prefersColorScheme = $request->header('prefers-color-scheme');
        if ($prefersColorScheme) {
            return $prefersColorScheme === 'dark';
        }

        // Default to light mode
        return false;
    }

    /**
     * Add performance metrics to response
     */
    protected function addPerformanceMetrics(Response $response): void
    {
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);

        $response->headers->set('X-Debug-Execution-Time', $executionTime.'ms');
        $response->headers->set('X-Debug-Memory-Usage', $this->formatBytes(memory_get_usage(true)));
        $response->headers->set('X-Debug-Peak-Memory', $this->formatBytes(memory_get_peak_usage(true)));

        // Add query count if available
        if (class_exists('\Illuminate\Database\Events\QueryExecuted')) {
            $queryCount = $this->getQueryCount();
            $response->headers->set('X-Debug-Query-Count', $queryCount);
        }
    }

    /**
     * Get query count for performance metrics
     */
    protected function getQueryCount(): int
    {
        try {
            $events = app('events');
            $queryCount = 0;

            $events->listen('Illuminate\Database\Events\QueryExecuted', function () use (&$queryCount): void {
                $queryCount++;
            });

            return $queryCount;
        } catch (\Exception $e) {
            return 0;
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

        return round($bytes, 2).' '.$units[$pow];
    }
}
