<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Exception;

class GameErrorHandler
{
    /**
     * Handle game-specific errors with detailed logging
     */
    public static function handleGameError(Exception $exception, array $context = []): void
    {
        $errorData = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
            'timestamp' => now()->toISOString(),
            'user_agent' => request()->userAgent(),
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
            'user_id' => auth()->id(),
        ];

        // Log to different channels based on error severity
        if (self::isCriticalError($exception)) {
            Log::channel('critical')->error('Critical Game Error', $errorData);
            self::notifyAdmins($exception, $context);
        } else {
            Log::channel('game')->error('Game Error', $errorData);
        }
    }

    /**
     * Check if error is critical and requires admin notification
     */
    private static function isCriticalError(Exception $exception): bool
    {
        $criticalPatterns = [
            'database connection',
            'memory limit',
            'fatal error',
            'maximum execution time',
            'payment processing',
            'security violation',
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
     * Notify administrators of critical errors
     */
    private static function notifyAdmins(Exception $exception, array $context): void
    {
        try {
            $admins = config('game.admin_emails', ['admin@example.com']);
            
            foreach ($admins as $adminEmail) {
                Mail::raw(
                    "Critical Game Error Occurred:\n\n" .
                    "Error: {$exception->getMessage()}\n" .
                    "File: {$exception->getFile()}\n" .
                    "Line: {$exception->getLine()}\n" .
                    "Time: " . now()->toISOString() . "\n" .
                    "Context: " . json_encode($context, JSON_PRETTY_PRINT),
                    function ($message) use ($adminEmail) {
                        $message->to($adminEmail)
                               ->subject('Critical Game Error Alert');
                    }
                );
            }
        } catch (Exception $e) {
            Log::error('Failed to send admin notification', [
                'original_error' => $exception->getMessage(),
                'notification_error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log game actions for audit trail
     */
    public static function logGameAction(string $action, array $data = []): void
    {
        Log::channel('game_actions')->info($action, [
            'user_id' => auth()->id(),
            'action' => $action,
            'data' => $data,
            'timestamp' => now()->toISOString(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Handle battle-related errors
     */
    public static function handleBattleError(Exception $exception, array $battleData): void
    {
        self::handleGameError($exception, array_merge($battleData, [
            'error_type' => 'battle',
            'battle_id' => $battleData['battle_id'] ?? null,
            'attacker_id' => $battleData['attacker_id'] ?? null,
            'defender_id' => $battleData['defender_id'] ?? null,
        ]));
    }

    /**
     * Handle building-related errors
     */
    public static function handleBuildingError(Exception $exception, array $buildingData): void
    {
        self::handleGameError($exception, array_merge($buildingData, [
            'error_type' => 'building',
            'building_id' => $buildingData['building_id'] ?? null,
            'village_id' => $buildingData['village_id'] ?? null,
            'player_id' => $buildingData['player_id'] ?? null,
        ]));
    }

    /**
     * Handle movement-related errors
     */
    public static function handleMovementError(Exception $exception, array $movementData): void
    {
        self::handleGameError($exception, array_merge($movementData, [
            'error_type' => 'movement',
            'movement_id' => $movementData['movement_id'] ?? null,
            'from_village_id' => $movementData['from_village_id'] ?? null,
            'to_village_id' => $movementData['to_village_id'] ?? null,
        ]));
    }

    /**
     * Log performance metrics
     */
    public static function logPerformance(string $operation, float $duration, array $metrics = []): void
    {
        Log::channel('performance')->info("Performance: {$operation}", [
            'operation' => $operation,
            'duration_ms' => $duration * 1000,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'metrics' => $metrics,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Create a user-friendly error message
     */
    public static function getUserFriendlyMessage(Exception $exception): string
    {
        $userMessages = [
            'battle' => 'A battle error occurred. Please try again.',
            'building' => 'Building operation failed. Please check your resources.',
            'movement' => 'Movement failed. Please check your units and destination.',
            'resource' => 'Resource operation failed. Please refresh and try again.',
            'alliance' => 'Alliance operation failed. Please try again.',
        ];

        $errorType = self::getErrorType($exception);
        
        return $userMessages[$errorType] ?? 'An unexpected error occurred. Please try again.';
    }

    /**
     * Determine error type from exception
     */
    private static function getErrorType(Exception $exception): string
    {
        $message = strtolower($exception->getMessage());
        
        if (str_contains($message, 'battle')) return 'battle';
        if (str_contains($message, 'building')) return 'building';
        if (str_contains($message, 'movement')) return 'movement';
        if (str_contains($message, 'resource')) return 'resource';
        if (str_contains($message, 'alliance')) return 'alliance';
        
        return 'general';
    }
}
