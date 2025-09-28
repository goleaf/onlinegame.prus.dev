<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameErrorHandler
{
    protected $errorLog = [];

    protected $maxLogSize = 1000;

    /**
     * Handle game errors
     */
    public function handleError(string $context, Exception $exception, array $data = []): void
    {
        try {
            $errorData = [
                'context' => $context,
                'error_message' => $exception->getMessage(),
                'error_code' => $exception->getCode(),
                'error_file' => $exception->getFile(),
                'error_line' => $exception->getLine(),
                'error_trace' => $exception->getTraceAsString(),
                'data' => $data,
                'timestamp' => now()->toISOString(),
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ];

            // Log error
            Log::error('Game error occurred', $errorData);

            // Store error in memory log
            $this->storeErrorLog($errorData);

            // Store error in database if needed
            $this->storeErrorInDatabase($errorData);

        } catch (Exception $e) {
            Log::critical('Failed to handle game error', [
                'original_error' => $exception->getMessage(),
                'handler_error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle validation errors
     */
    public function handleValidationError(string $context, array $errors, array $data = []): void
    {
        try {
            $errorData = [
                'context' => $context,
                'error_type' => 'validation',
                'validation_errors' => $errors,
                'data' => $data,
                'timestamp' => now()->toISOString(),
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ];

            Log::warning('Game validation error', $errorData);
            $this->storeErrorLog($errorData);

        } catch (Exception $e) {
            Log::critical('Failed to handle validation error', [
                'original_errors' => $errors,
                'handler_error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle performance errors
     */
    public function handlePerformanceError(string $context, float $executionTime, array $data = []): void
    {
        try {
            $errorData = [
                'context' => $context,
                'error_type' => 'performance',
                'execution_time' => $executionTime,
                'threshold_exceeded' => $executionTime > 5.0, // 5 second threshold
                'data' => $data,
                'timestamp' => now()->toISOString(),
                'user_id' => auth()->id(),
            ];

            if ($executionTime > 5.0) {
                Log::warning('Game performance issue', $errorData);
                $this->storeErrorLog($errorData);
            }

        } catch (Exception $e) {
            Log::critical('Failed to handle performance error', [
                'execution_time' => $executionTime,
                'handler_error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle database errors
     */
    public function handleDatabaseError(string $context, Exception $exception, array $query = []): void
    {
        try {
            $errorData = [
                'context' => $context,
                'error_type' => 'database',
                'error_message' => $exception->getMessage(),
                'error_code' => $exception->getCode(),
                'query' => $query,
                'timestamp' => now()->toISOString(),
                'user_id' => auth()->id(),
            ];

            Log::error('Game database error', $errorData);
            $this->storeErrorLog($errorData);

        } catch (Exception $e) {
            Log::critical('Failed to handle database error', [
                'original_error' => $exception->getMessage(),
                'handler_error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle cache errors
     */
    public function handleCacheError(string $context, Exception $exception, array $cacheData = []): void
    {
        try {
            $errorData = [
                'context' => $context,
                'error_type' => 'cache',
                'error_message' => $exception->getMessage(),
                'cache_data' => $cacheData,
                'timestamp' => now()->toISOString(),
                'user_id' => auth()->id(),
            ];

            Log::warning('Game cache error', $errorData);
            $this->storeErrorLog($errorData);

        } catch (Exception $e) {
            Log::critical('Failed to handle cache error', [
                'original_error' => $exception->getMessage(),
                'handler_error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Store error in memory log
     */
    private function storeErrorLog(array $errorData): void
    {
        $this->errorLog[] = $errorData;

        // Keep only the last N errors
        if (count($this->errorLog) > $this->maxLogSize) {
            $this->errorLog = array_slice($this->errorLog, -$this->maxLogSize);
        }
    }

    /**
     * Store error in database
     */
    private function storeErrorInDatabase(array $errorData): void
    {
        try {
            // Only store critical errors in database
            if ($errorData['error_type'] === 'critical' || $errorData['error_code'] >= 500) {
                DB::table('game_errors')->insert([
                    'context' => $errorData['context'],
                    'error_message' => $errorData['error_message'],
                    'error_code' => $errorData['error_code'],
                    'error_file' => $errorData['error_file'],
                    'error_line' => $errorData['error_line'],
                    'data' => json_encode($errorData['data']),
                    'user_id' => $errorData['user_id'],
                    'ip_address' => $errorData['ip_address'],
                    'user_agent' => $errorData['user_agent'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

        } catch (Exception $e) {
            Log::critical('Failed to store error in database', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get error statistics
     */
    public function getErrorStats(): array
    {
        try {
            $stats = [
                'total_errors' => count($this->errorLog),
                'errors_by_context' => $this->getErrorsByContext(),
                'errors_by_type' => $this->getErrorsByType(),
                'recent_errors' => array_slice($this->errorLog, -10),
                'error_rate' => $this->calculateErrorRate(),
            ];

            return $stats;

        } catch (Exception $e) {
            Log::critical('Failed to get error statistics', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get errors by context
     */
    private function getErrorsByContext(): array
    {
        $contexts = [];
        foreach ($this->errorLog as $error) {
            $context = $error['context'] ?? 'unknown';
            $contexts[$context] = ($contexts[$context] ?? 0) + 1;
        }

        return $contexts;
    }

    /**
     * Get errors by type
     */
    private function getErrorsByType(): array
    {
        $types = [];
        foreach ($this->errorLog as $error) {
            $type = $error['error_type'] ?? 'unknown';
            $types[$type] = ($types[$type] ?? 0) + 1;
        }

        return $types;
    }

    /**
     * Calculate error rate
     */
    private function calculateErrorRate(): float
    {
        $totalRequests = 1000; // This should be tracked separately
        $totalErrors = count($this->errorLog);

        return $totalRequests > 0 ? ($totalErrors / $totalRequests) * 100 : 0;
    }

    /**
     * Get recent errors
     */
    public function getRecentErrors(int $limit = 50): array
    {
        return array_slice($this->errorLog, -$limit);
    }

    /**
     * Clear error log
     */
    public function clearErrorLog(): int
    {
        $count = count($this->errorLog);
        $this->errorLog = [];

        Log::info('Error log cleared', [
            'cleared_count' => $count,
        ]);

        return $count;
    }

    /**
     * Cleanup old errors
     */
    public function cleanup(): int
    {
        try {
            // Clean up memory log
            $cleared = $this->clearErrorLog();

            // Clean up database errors older than 30 days
            $dbCleared = DB::table('game_errors')
                ->where('created_at', '<', now()->subDays(30))
                ->delete();

            Log::info('Error cleanup completed', [
                'memory_cleared' => $cleared,
                'database_cleared' => $dbCleared,
            ]);

            return $cleared + $dbCleared;

        } catch (Exception $e) {
            Log::critical('Failed to cleanup errors', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Get error trends
     */
    public function getErrorTrends(): array
    {
        try {
            $trends = [
                'hourly' => $this->getHourlyTrends(),
                'daily' => $this->getDailyTrends(),
                'weekly' => $this->getWeeklyTrends(),
            ];

            return $trends;

        } catch (Exception $e) {
            Log::critical('Failed to get error trends', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get hourly trends
     */
    private function getHourlyTrends(): array
    {
        $trends = [];
        $now = now();

        for ($i = 0; $i < 24; $i++) {
            $hour = $now->subHours($i);
            $count = 0;

            foreach ($this->errorLog as $error) {
                if (isset($error['timestamp'])) {
                    $errorTime = \Carbon\Carbon::parse($error['timestamp']);
                    if ($errorTime->isSameHour($hour)) {
                        $count++;
                    }
                }
            }

            $trends[$hour->format('H:00')] = $count;
        }

        return $trends;
    }

    /**
     * Get daily trends
     */
    private function getDailyTrends(): array
    {
        $trends = [];
        $now = now();

        for ($i = 0; $i < 7; $i++) {
            $day = $now->subDays($i);
            $count = 0;

            foreach ($this->errorLog as $error) {
                if (isset($error['timestamp'])) {
                    $errorTime = \Carbon\Carbon::parse($error['timestamp']);
                    if ($errorTime->isSameDay($day)) {
                        $count++;
                    }
                }
            }

            $trends[$day->format('Y-m-d')] = $count;
        }

        return $trends;
    }

    /**
     * Get weekly trends
     */
    private function getWeeklyTrends(): array
    {
        $trends = [];
        $now = now();

        for ($i = 0; $i < 4; $i++) {
            $week = $now->subWeeks($i);
            $count = 0;

            foreach ($this->errorLog as $error) {
                if (isset($error['timestamp'])) {
                    $errorTime = \Carbon\Carbon::parse($error['timestamp']);
                    if ($errorTime->isSameWeek($week)) {
                        $count++;
                    }
                }
            }

            $trends[$week->format('Y-W')] = $count;
        }

        return $trends;
    }
}
