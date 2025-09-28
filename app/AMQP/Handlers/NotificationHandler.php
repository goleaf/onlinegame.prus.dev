<?php

namespace App\AMQP\Handlers;

use Illuminate\Support\Facades\Log;
use Usmonaliyev\SimpleRabbit\MQ\Message;

class NotificationHandler
{
    /**
     * Handle notification events from RabbitMQ
     */
    public function handle(Message $message)
    {
        try {
            $data = $message->getBody();

            Log::info('Notification event received', [
                'notification_type' => $data['notification_type'] ?? 'unknown',
                'data' => $data,
            ]);

            // Process different types of notifications
            switch ($data['notification_type'] ?? '') {
                case 'email':
                    $this->handleEmailNotification($data);

                    break;
                case 'in_game':
                    $this->handleInGameNotification($data);

                    break;
                case 'push':
                    $this->handlePushNotification($data);

                    break;
                default:
                    Log::warning('Unknown notification type', ['notification_type' => $data['notification_type'] ?? 'unknown']);
            }

            // Acknowledge the message
            $message->ack();
        } catch (\Exception $e) {
            Log::error('Error processing notification', [
                'error' => $e->getMessage(),
                'data' => $message->getBody(),
            ]);

            // Reject the message and requeue it
            $message->nack(true);
        }
    }

    /**
     * Handle email notifications
     */
    private function handleEmailNotification(array $data)
    {
        Log::info('Processing email notification', $data);

        // Add your email logic here
        // For example: send welcome emails, battle reports, etc.
    }

    /**
     * Handle in-game notifications
     */
    private function handleInGameNotification(array $data)
    {
        Log::info('Processing in-game notification', $data);

        // Add your in-game notification logic here
        // For example: store notifications in database, trigger real-time updates, etc.
    }

    /**
     * Handle push notifications
     */
    private function handlePushNotification(array $data)
    {
        Log::info('Processing push notification', $data);

        // Add your push notification logic here
        // For example: send mobile push notifications, web push notifications, etc.
    }
}
