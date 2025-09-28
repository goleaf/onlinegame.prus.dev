<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Usmonaliyev\SimpleRabbit\Facades\SimpleMQ;

class RabbitMQService
{
    /**
     * Publish a game event to RabbitMQ
     */
    public function publishGameEvent(string $eventType, array $data, string $handler = 'game_event'): void
    {
        $startTime = microtime(true);

        ds('RabbitMQService: Publishing game event', [
            'service' => 'RabbitMQService',
            'method' => 'publishGameEvent',
            'event_type' => $eventType,
            'handler' => $handler,
            'data_size' => count($data),
            'publish_time' => now(),
        ]);

        try {
            $message = [
                'event_type' => $eventType,
                'timestamp' => now()->toISOString(),
                'data' => $data,
            ];

            $publishStart = microtime(true);
            SimpleMQ::queue('game_events')
                ->setBody($message)
                ->handler($handler)
                ->publish();
            $publishTime = round((microtime(true) - $publishStart) * 1000, 2);

            $totalTime = round((microtime(true) - $startTime) * 1000, 2);

            ds('RabbitMQService: Game event published successfully', [
                'event_type' => $eventType,
                'handler' => $handler,
                'publish_time_ms' => $publishTime,
                'total_time_ms' => $totalTime,
            ]);

            Log::info('Game event published', [
                'event_type' => $eventType,
                'handler' => $handler,
            ]);
        } catch (\Exception $e) {
            $totalTime = round((microtime(true) - $startTime) * 1000, 2);

            ds('RabbitMQService: Game event publishing failed', [
                'event_type' => $eventType,
                'handler' => $handler,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'total_time_ms' => $totalTime,
            ]);

            Log::error('Failed to publish game event', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Publish a notification to RabbitMQ
     */
    public function publishNotification(string $notificationType, array $data, string $handler = 'notification'): void
    {
        try {
            $message = [
                'notification_type' => $notificationType,
                'timestamp' => now()->toISOString(),
                'data' => $data,
            ];

            SimpleMQ::queue('game_events')
                ->setBody($message)
                ->handler($handler)
                ->publish();

            Log::info('Notification published', [
                'notification_type' => $notificationType,
                'handler' => $handler,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to publish notification', [
                'notification_type' => $notificationType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Publish player action event
     */
    public function publishPlayerAction(int $playerId, string $action, array $details = []): void
    {
        $this->publishGameEvent('player_action', [
            'player_id' => $playerId,
            'action' => $action,
            'details' => $details,
        ]);
    }

    /**
     * Publish building completion event
     */
    public function publishBuildingCompleted(int $villageId, int $buildingId, string $buildingType): void
    {
        $this->publishGameEvent('building_completed', [
            'village_id' => $villageId,
            'building_id' => $buildingId,
            'building_type' => $buildingType,
        ]);
    }

    /**
     * Publish battle result event
     */
    public function publishBattleResult(int $attackerId, int $defenderId, array $battleData): void
    {
        $this->publishGameEvent('battle_result', [
            'attacker_id' => $attackerId,
            'defender_id' => $defenderId,
            'battle_data' => $battleData,
        ]);
    }

    /**
     * Publish resource update event
     */
    public function publishResourceUpdate(int $villageId, array $resources): void
    {
        $this->publishGameEvent('resource_update', [
            'village_id' => $villageId,
            'resources' => $resources,
        ]);
    }

    /**
     * Publish email notification
     */
    public function publishEmailNotification(string $email, string $subject, array $data): void
    {
        $this->publishNotification('email', [
            'email' => $email,
            'subject' => $subject,
            'data' => $data,
        ]);
    }

    /**
     * Publish in-game notification
     */
    public function publishInGameNotification(int $playerId, string $message, array $data = []): void
    {
        $this->publishNotification('in_game', [
            'player_id' => $playerId,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
