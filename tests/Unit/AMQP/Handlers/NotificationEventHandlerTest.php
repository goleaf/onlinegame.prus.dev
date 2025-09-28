<?php

namespace Tests\Unit\AMQP\Handlers;

use App\AMQP\Handlers\NotificationEventHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Usmonaliyev\SimpleRabbit\MQ\Message;

class NotificationEventHandlerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_handle_notification_event()
    {
        $handler = new NotificationEventHandler();
        $message = $this->createMockMessage([
            'event_type' => 'notification_sent',
            'user_id' => 1,
            'notification_type' => 'battle_report',
            'title' => 'Battle Report',
            'message' => 'Your attack was successful!',
            'timestamp' => now()->toISOString(),
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Notification event received', Mockery::type('array'));

        Log::shouldReceive('info')
            ->once()
            ->with('Processing notification event', Mockery::type('array'));

        $handler->handle($message);

        $message->ack();
    }

    /**
     * @test
     */
    public function it_can_handle_email_notification_event()
    {
        $handler = new NotificationEventHandler();
        $message = $this->createMockMessage([
            'event_type' => 'email_notification',
            'user_id' => 1,
            'email' => 'user@example.com',
            'subject' => 'Game Update',
            'template' => 'battle_report',
            'data' => ['battle_id' => 123],
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Notification event received', Mockery::type('array'));

        Log::shouldReceive('info')
            ->once()
            ->with('Processing email notification', Mockery::type('array'));

        $handler->handle($message);

        $message->ack();
    }

    /**
     * @test
     */
    public function it_can_handle_push_notification_event()
    {
        $handler = new NotificationEventHandler();
        $message = $this->createMockMessage([
            'event_type' => 'push_notification',
            'user_id' => 1,
            'device_token' => 'device_token_123',
            'title' => 'Village Under Attack!',
            'body' => 'Your village is being attacked by Player X',
            'data' => ['village_id' => 456],
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Notification event received', Mockery::type('array'));

        Log::shouldReceive('info')
            ->once()
            ->with('Processing push notification', Mockery::type('array'));

        $handler->handle($message);

        $message->ack();
    }

    /**
     * @test
     */
    public function it_can_handle_in_game_notification_event()
    {
        $handler = new NotificationEventHandler();
        $message = $this->createMockMessage([
            'event_type' => 'in_game_notification',
            'user_id' => 1,
            'notification_id' => 'notif_789',
            'type' => 'alliance_invite',
            'data' => ['alliance_id' => 789, 'inviter_id' => 2],
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Notification event received', Mockery::type('array'));

        Log::shouldReceive('info')
            ->once()
            ->with('Processing in-game notification', Mockery::type('array'));

        $handler->handle($message);

        $message->ack();
    }

    /**
     * @test
     */
    public function it_handles_unknown_notification_event_type()
    {
        $handler = new NotificationEventHandler();
        $message = $this->createMockMessage([
            'event_type' => 'unknown_notification_type',
            'user_id' => 1,
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Notification event received', Mockery::type('array'));

        Log::shouldReceive('warning')
            ->once()
            ->with('Unknown notification event type', Mockery::type('array'));

        $handler->handle($message);

        $message->ack();
    }

    /**
     * @test
     */
    public function it_handles_notification_processing_error()
    {
        $handler = new NotificationEventHandler();
        $message = $this->createMockMessage([
            'event_type' => 'notification_sent',
            'user_id' => 1,
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Notification event received', Mockery::type('array'));

        Log::shouldReceive('info')
            ->once()
            ->with('Processing notification event', Mockery::type('array'))
            ->andThrow(new \Exception('Processing failed'));

        Log::shouldReceive('error')
            ->once()
            ->with('Error processing notification event', Mockery::type('array'));

        $handler->handle($message);

        $message->nack(true);
    }

    /**
     * @test
     */
    public function it_can_handle_bulk_notification_event()
    {
        $handler = new NotificationEventHandler();
        $message = $this->createMockMessage([
            'event_type' => 'bulk_notification',
            'user_ids' => [1, 2, 3, 4, 5],
            'notification_type' => 'system_announcement',
            'title' => 'Server Maintenance',
            'message' => 'Server will be down for maintenance',
            'scheduled_at' => now()->addHour()->toISOString(),
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Notification event received', Mockery::type('array'));

        Log::shouldReceive('info')
            ->once()
            ->with('Processing bulk notification', Mockery::type('array'));

        $handler->handle($message);

        $message->ack();
    }

    /**
     * @test
     */
    public function it_can_handle_notification_preference_update()
    {
        $handler = new NotificationEventHandler();
        $message = $this->createMockMessage([
            'event_type' => 'notification_preference_update',
            'user_id' => 1,
            'preferences' => [
                'email_notifications' => true,
                'push_notifications' => false,
                'in_game_notifications' => true,
            ],
        ]);

        Log::shouldReceive('info')
            ->once()
            ->with('Notification event received', Mockery::type('array'));

        Log::shouldReceive('info')
            ->once()
            ->with('Processing notification preference update', Mockery::type('array'));

        $handler->handle($message);

        $message->ack();
    }

    private function createMockMessage(array $body): Message
    {
        $message = $this->createMock(Message::class);
        $message->method('getBody')->willReturn($body);
        $message->method('ack')->willReturnCallback(function (): void {});
        $message->method('nack')->willReturnCallback(function (): void {});

        return $message;
    }
}
