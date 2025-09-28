<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class LaradumpsHelperService
{
    /**
     * Debug game component initialization
     */
    public static function debugComponentMount(string $componentName, array $data = []): void
    {
        if (! self::shouldDebug()) {
            return;
        }

        $debugData = array_merge([
            'component' => $componentName,
            'user_id' => Auth::id(),
            'timestamp' => now(),
            'memory_usage' => memory_get_usage(true),
            'environment' => app()->environment(),
        ], $data);

        ds("Component Mount: {$componentName}", $debugData)->label("{$componentName} Mount");
    }

    /**
     * Debug game actions with geographic data
     */
    public static function debugGameAction(string $action, array $data = []): void
    {
        if (! self::shouldDebug()) {
            return;
        }

        $debugData = array_merge([
            'action' => $action,
            'user_id' => Auth::id(),
            'timestamp' => now(),
            'memory_usage' => memory_get_usage(true),
        ], $data);

        ds("Game Action: {$action}", $debugData)->label("Game Action: {$action}");
    }

    /**
     * Debug performance metrics
     */
    public static function debugPerformance(string $operation, float $startTime, array $additionalData = []): void
    {
        if (! self::shouldDebug()) {
            return;
        }

        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);

        $debugData = array_merge([
            'operation' => $operation,
            'execution_time_ms' => $executionTime,
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'user_id' => Auth::id(),
            'timestamp' => now(),
        ], $additionalData);

        ds("Performance: {$operation}", $debugData)->label("Performance: {$operation}");
    }

    /**
     * Debug database queries
     */
    public static function debugDatabase(string $operation, array $queryData = []): void
    {
        if (! self::shouldDebug()) {
            return;
        }

        $debugData = array_merge([
            'operation' => $operation,
            'query_count' => count(\DB::getQueryLog()),
            'user_id' => Auth::id(),
            'timestamp' => now(),
        ], $queryData);

        ds("Database: {$operation}", $debugData)->label("Database: {$operation}");
    }

    /**
     * Debug errors with context
     */
    public static function debugError(\Exception $e, array $context = []): void
    {
        if (! self::shouldDebug()) {
            return;
        }

        $debugData = array_merge([
            'error_message' => $e->getMessage(),
            'error_type' => get_class($e),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
            'user_id' => Auth::id(),
            'timestamp' => now(),
        ], $context);

        // Only include stack trace in local environment
        if (app()->environment('local')) {
            $debugData['stack_trace'] = $e->getTraceAsString();
        }

        ds("Error: {$e->getMessage()}", $debugData)->label('Error Debug');
    }

    /**
     * Debug geographic data
     */
    public static function debugGeographic(string $operation, array $geoData = []): void
    {
        if (! self::shouldDebug()) {
            return;
        }

        $debugData = array_merge([
            'operation' => $operation,
            'user_id' => Auth::id(),
            'timestamp' => now(),
        ], $geoData);

        ds("Geographic: {$operation}", $debugData)->label("Geographic: {$operation}");
    }

    /**
     * Debug analytics events
     */
    public static function debugAnalytics(string $event, array $analyticsData = []): void
    {
        if (! self::shouldDebug()) {
            return;
        }

        $debugData = array_merge([
            'event' => $event,
            'user_id' => Auth::id(),
            'timestamp' => now(),
        ], $analyticsData);

        ds("Analytics: {$event}", $debugData)->label("Analytics: {$event}");
    }

    /**
     * Debug caching operations
     */
    public static function debugCache(string $operation, array $cacheData = []): void
    {
        if (! self::shouldDebug()) {
            return;
        }

        $debugData = array_merge([
            'operation' => $operation,
            'user_id' => Auth::id(),
            'timestamp' => now(),
            'memory_usage' => memory_get_usage(true),
        ], $cacheData);

        ds("Cache: {$operation}", $debugData)->label("Cache: {$operation}");
    }

    /**
     * Debug service operations
     */
    public static function debugService(string $serviceName, string $method, array $serviceData = []): void
    {
        if (! self::shouldDebug()) {
            return;
        }

        $debugData = array_merge([
            'service' => $serviceName,
            'method' => $method,
            'user_id' => Auth::id(),
            'timestamp' => now(),
        ], $serviceData);

        ds("Service: {$serviceName}::{$method}", $debugData)->label("Service: {$serviceName}");
    }

    /**
     * Debug model operations
     */
    public static function debugModel(string $modelName, string $operation, array $modelData = []): void
    {
        if (! self::shouldDebug()) {
            return;
        }

        $debugData = array_merge([
            'model' => $modelName,
            'operation' => $operation,
            'user_id' => Auth::id(),
            'timestamp' => now(),
        ], $modelData);

        ds("Model: {$modelName}::{$operation}", $debugData)->label("Model: {$modelName}");
    }

    /**
     * Debug with custom label and data
     */
    public static function debug(string $message, array $data = [], string $label = 'Custom Debug'): void
    {
        if (! self::shouldDebug()) {
            return;
        }

        $debugData = array_merge([
            'message' => $message,
            'user_id' => Auth::id(),
            'timestamp' => now(),
        ], $data);

        ds($message, $debugData)->label($label);
    }

    /**
     * Debug with screen organization
     */
    public static function debugToScreen(string $message, array $data = [], string $screen = 'General'): void
    {
        if (! self::shouldDebug()) {
            return;
        }

        $debugData = array_merge([
            'message' => $message,
            'user_id' => Auth::id(),
            'timestamp' => now(),
        ], $data);

        ds($message, $debugData)->toScreen($screen);
    }

    /**
     * Debug with color coding
     */
    public static function debugWithColor(string $message, array $data = [], string $color = 'blue'): void
    {
        if (! self::shouldDebug()) {
            return;
        }

        $debugData = array_merge([
            'message' => $message,
            'user_id' => Auth::id(),
            'timestamp' => now(),
        ], $data);

        ds($message, $debugData)->color($color);
    }

    /**
     * Check if debugging should be enabled
     */
    private static function shouldDebug(): bool
    {
        // Only debug in development environments
        return app()->environment('local', 'development') || config('app.debug');
    }

    /**
     * Get memory usage statistics
     */
    public static function getMemoryStats(): array
    {
        return [
            'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'limit' => ini_get('memory_limit'),
        ];
    }

    /**
     * Get performance statistics
     */
    public static function getPerformanceStats(): array
    {
        return [
            'execution_time' => microtime(true) - LARAVEL_START,
            'memory_stats' => self::getMemoryStats(),
            'environment' => app()->environment(),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
        ];
    }

    /**
     * Debug system health
     */
    public static function debugSystemHealth(): void
    {
        if (! self::shouldDebug()) {
            return;
        }

        $healthData = [
            'performance_stats' => self::getPerformanceStats(),
            'database_connections' => count(\DB::getConnections()),
            'cache_status' => 'active',
            'queue_status' => 'active',
            'user_id' => Auth::id(),
            'timestamp' => now(),
        ];

        ds('System Health Check', $healthData)->label('System Health');
    }
}
