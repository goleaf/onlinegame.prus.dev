<?php

namespace App\Services;

use Usmonaliyev\SimpleRabbit\Facades\SimpleMQ;
use Illuminate\Support\Facades\Log;

class RabbitMQService
{
    /**
     * Publish a game event to RabbitMQ
     */
    public function publishGameEvent(string $eventType, array $data, string $handler = 'game_event'): void
    {
        try {
            $message = [
                'event_type' => $eventType,
                'timestamp' => now()->toISOString(),
                'data' => $data
            ];

            SimpleMQ::queue('game_events')
                ->setBody($message)
                ->handler($handler)
                ->publish();

            Log::info('Game event published', [
                'event_type' => $eventType,
                'handler' => $handler
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to publish game event', [
                'event_type' => $eventType,
                'error' => $e->getMessage()
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
                'data' => $data
            ];

            SimpleMQ::queue('game_events')
                ->setBody($message)
                ->handler($handler)
                ->publish();

            Log::info('Notification published', [
                'notification_type' => $notificationType,
                'handler' => $handler
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to publish notification', [
                'notification_type' => $notificationType,
                'error' => $e->getMessage()
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
            'details' => $details
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
            'building_type' => $buildingType
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
            'battle_data' => $battleData
        ]);
    }

    /**
     * Publish resource update event
     */
    public function publishResourceUpdate(int $villageId, array $resources): void
    {
        $this->publishGameEvent('resource_update', [
            'village_id' => $villageId,
            'resources' => $resources
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
            'data' => $data
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
            'data' => $data
        ]);
    }
}
