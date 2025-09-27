<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Game Error Handler
 * Handles game-specific errors and logging
 */
class GameErrorHandler
{
    /**
     * Log game action
     */
    public static function logGameAction(string $action, array $data = []): void
    {
        try {
            Log::info('Game action logged', [
                'action' => $action,
                'data' => $data,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toISOString(),
            ]);

            // Store in cache for quick access
            $cacheKey = "game_action_log:{$action}:" . now()->format('Y-m-d-H');
            Cache::remember($cacheKey, now()->addHour(), function () use ($action, $data) {
                return [
                    'action' => $action,
                    'data' => $data,
                    'count' => 1,
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to log game action', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle game error
     */
    public static function handleGameError(\Exception $exception, array $context = []): void
    {
        $errorData = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        // Log the error
        Log::error('Game error occurred', $errorData);

        // Store error statistics
        self::storeErrorStatistics($exception, $context);

        // Send notification for critical errors
        if (self::isCriticalError($exception)) {
            self::sendCriticalErrorNotification($errorData);
        }
    }

    /**
     * Handle database error
     */
    public static function handleDatabaseError(\Exception $exception, string $query = null): void
    {
        $errorData = [
            'type' => 'database_error',
            'message' => $exception->getMessage(),
            'query' => $query,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
        ];

        Log::error('Database error occurred', $errorData);

        // Store database error statistics
        self::storeDatabaseErrorStatistics($exception, $query);
    }

    /**
     * Handle cache error
     */
    public static function handleCacheError(\Exception $exception, string $operation = null): void
    {
        $errorData = [
            'type' => 'cache_error',
            'message' => $exception->getMessage(),
            'operation' => $operation,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString(),
        ];

        Log::error('Cache error occurred', $errorData);

        // Store cache error statistics
        self::storeCacheErrorStatistics($exception, $operation);
    }

    /**
     * Get error statistics
     */
    public static function getErrorStatistics(): array
    {
        $cacheKey = 'error_statistics';

        return Cache::remember($cacheKey, now()->addMinutes(15), function () {
            return [
                'total_errors' => self::getTotalErrorCount(),
                'errors_today' => self::getTodayErrorCount(),
                'errors_this_week' => self::getWeekErrorCount(),
                'error_types' => self::getErrorTypeStatistics(),
                'critical_errors' => self::getCriticalErrorCount(),
                'database_errors' => self::getDatabaseErrorCount(),
                'cache_errors' => self::getCacheErrorCount(),
            ];
        });
    }

    /**
     * Get error trends
     */
    public static function getErrorTrends(int $days = 7): array
    {
        $cacheKey = "error_trends:{$days}";

        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($days) {
            $trends = [];
            
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $trends[$date] = self::getErrorCountForDate($date);
            }

            return $trends;
        });
    }

    /**
     * Store error statistics
     */
    private static function storeErrorStatistics(\Exception $exception, array $context): void
    {
        try {
            $errorType = self::getErrorType($exception);
            $cacheKey = "error_stats:{$errorType}:" . now()->format('Y-m-d');

            Cache::increment($cacheKey);
        } catch (\Exception $e) {
            // Silently fail to avoid infinite loops
        }
    }

    /**
     * Store database error statistics
     */
    private static function storeDatabaseErrorStatistics(\Exception $exception, string $query = null): void
    {
        try {
            $cacheKey = 'database_error_stats:' . now()->format('Y-m-d');
            Cache::increment($cacheKey);
        } catch (\Exception $e) {
            // Silently fail
        }
    }

    /**
     * Store cache error statistics
     */
    private static function storeCacheErrorStatistics(\Exception $exception, string $operation = null): void
    {
        try {
            $cacheKey = 'cache_error_stats:' . now()->format('Y-m-d');
            Cache::increment($cacheKey);
        } catch (\Exception $e) {
            // Silently fail
        }
    }

    /**
     * Check if error is critical
     */
    private static function isCriticalError(\Exception $exception): bool
    {
        $criticalPatterns = [
            'database connection',
            'memory limit',
            'fatal error',
            'out of memory',
            'connection refused',
        ];

        $message = strtolower($exception->getMessage());

        foreach ($criticalPatterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send critical error notification
     */
    private static function sendCriticalErrorNotification(array $errorData): void
    {
        try {
            // Log critical error
            Log::critical('Critical game error occurred', $errorData);

            // Store in cache for monitoring
            $cacheKey = 'critical_errors:' . now()->format('Y-m-d-H');
            Cache::put($cacheKey, $errorData, now()->addHour());
        } catch (\Exception $e) {
            // Silently fail to avoid infinite loops
        }
    }

    /**
     * Get error type
     */
    private static function getErrorType(\Exception $exception): string
    {
        $className = get_class($exception);
        $parts = explode('\\', $className);
        return end($parts);
    }

    /**
     * Get total error count
     */
    private static function getTotalErrorCount(): int
    {
        try {
            $cacheKey = 'total_error_count';
            return Cache::get($cacheKey, 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get today's error count
     */
    private static function getTodayErrorCount(): int
    {
        try {
            $cacheKey = 'error_count:' . now()->format('Y-m-d');
            return Cache::get($cacheKey, 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get week's error count
     */
    private static function getWeekErrorCount(): int
    {
        try {
            $count = 0;
            for ($i = 0; $i < 7; $i++) {
                $date = now()->subDays($i)->format('Y-m-d');
                $count += Cache::get("error_count:{$date}", 0);
            }
            return $count;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get error type statistics
     */
    private static function getErrorTypeStatistics(): array
    {
        try {
            $types = ['Exception', 'Error', 'DatabaseError', 'CacheError'];
            $stats = [];

            foreach ($types as $type) {
                $cacheKey = "error_stats:{$type}:" . now()->format('Y-m-d');
                $stats[$type] = Cache::get($cacheKey, 0);
            }

            return $stats;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get critical error count
     */
    private static function getCriticalErrorCount(): int
    {
        try {
            $cacheKey = 'critical_error_count:' . now()->format('Y-m-d');
            return Cache::get($cacheKey, 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get database error count
     */
    private static function getDatabaseErrorCount(): int
    {
        try {
            $cacheKey = 'database_error_stats:' . now()->format('Y-m-d');
            return Cache::get($cacheKey, 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get cache error count
     */
    private static function getCacheErrorCount(): int
    {
        try {
            $cacheKey = 'cache_error_stats:' . now()->format('Y-m-d');
            return Cache::get($cacheKey, 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get error count for specific date
     */
    private static function getErrorCountForDate(string $date): int
    {
        try {
            $cacheKey = "error_count:{$date}";
            return Cache::get($cacheKey, 0);
        } catch (\Exception $e) {
            return 0;
        }
    }
}