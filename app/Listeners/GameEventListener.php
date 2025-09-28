<?php

namespace App\Listeners;

use App\Events\GameEvent;
use App\Services\GameNotificationService;
use App\Services\RealTimeGameService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class GameEventListener implements ShouldQueue
{
    use InteractsWithQueue;

    protected GameNotificationService $notificationService;

    protected RealTimeGameService $realTimeService;

    public function __construct(
        GameNotificationService $notificationService,
        RealTimeGameService $realTimeService
    ) {
        $this->notificationService = $notificationService;
        $this->realTimeService = $realTimeService;
    }

    /**
     * Handle the event.
     */
    public function handle(GameEvent $event): void
    {
        try {
            // Log the game event
            Log::info('Game event received', [
                'user_id' => $event->userId,
                'event_type' => $event->eventType,
                'data' => $event->data,
                'timestamp' => $event->timestamp,
            ]);

            // Send real-time update
            $this->realTimeService->sendUserUpdate(
                $event->userId,
                $event->eventType,
                $event->data
            );

            // Send notification if needed
            if ($this->shouldSendNotification($event->eventType)) {
                $this->notificationService->sendUserNotification(
                    $event->userId,
                    $event->eventType,
                    $event->data
                );
            }

        } catch (\Exception $e) {
            Log::error('Failed to handle game event', [
                'user_id' => $event->userId,
                'event_type' => $event->eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Determine if notification should be sent for this event type
     */
    private function shouldSendNotification(string $eventType): bool
    {
        $notificationEvents = [
            'village_attacked',
            'building_completed',
            'quest_completed',
            'alliance_joined',
            'message_received',
        ];

        return in_array($eventType, $notificationEvents);
    }
}
