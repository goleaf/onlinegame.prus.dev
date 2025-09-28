<?php

namespace App\AMQP\Handlers;

use Illuminate\Support\Facades\Log;

class AdminEventHandler
{
    /**
     * Supported admin event types keyed to their handler callbacks.
     */
    private const SUPPORTED_EVENTS = [
        'admin_action' => 'handleAdminAction',
        'system_maintenance' => 'handleSystemMaintenance',
        'security_alert' => 'handleSecurityAlert',
        'system_backup' => 'handleSystemBackup',
        'system_update' => 'handleSystemUpdate',
        'server_restart' => 'handleServerRestart',
        'database_optimization' => 'handleDatabaseOptimization',
        'cache_clear' => 'handleCacheClear',
    ];

    /**
     * Handle an incoming admin event message.
     */
    public function handle(array $message): bool
    {
        if (empty($message['admin_id'])) {
            Log::warning('Admin event rejected: missing admin_id', ['message' => $message]);

            return false;
        }

        $eventType = $message['event_type'] ?? null;

        if ($eventType === null || ! isset(self::SUPPORTED_EVENTS[$eventType])) {
            Log::warning('Admin event rejected: unsupported type', ['event_type' => $eventType]);

            return false;
        }

        $handler = self::SUPPORTED_EVENTS[$eventType];
        $payload = $message['data'] ?? [];

        $this->$handler($payload);

        return true;
    }

    private function handleAdminAction(array $payload): void
    {
        Log::info('Admin action event processed', $payload);
    }

    private function handleSystemMaintenance(array $payload): void
    {
        Log::info('System maintenance event processed', $payload);
    }

    private function handleSecurityAlert(array $payload): void
    {
        Log::info('Security alert event processed', $payload);
    }

    private function handleSystemBackup(array $payload): void
    {
        Log::info('System backup event processed', $payload);
    }

    private function handleSystemUpdate(array $payload): void
    {
        Log::info('System update event processed', $payload);
    }

    private function handleServerRestart(array $payload): void
    {
        Log::info('Server restart event processed', $payload);
    }

    private function handleDatabaseOptimization(array $payload): void
    {
        Log::info('Database optimization event processed', $payload);
    }

    private function handleCacheClear(array $payload): void
    {
        Log::info('Cache clear event processed', $payload);
    }
}
