<?php

namespace Tests\Unit\AMQP\Handlers;

use App\AMQP\Handlers\NotificationHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Usmonaliyev\SimpleRabbit\MQ\Message;

class NotificationHandlerTest extends TestCase
{
    use RefreshDatabase;

    private NotificationHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new NotificationHandler();
    }

    /**
     * @test
     */
    public function it_can_handle_email_notifications()
    {
        Log::shouldReceive('info')
            ->with('Notification event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('info')
            ->with('Processing email notification', Mockery::type('array'))
            ->once();

        $message = $this->createMockMessage([
            'notification_type' => 'email',
            'to' => 'user@example.com',
            'subject' => 'Welcome to the game!',
        ]);

        $message->shouldReceive('ack')->once();

        $this->handler->handle($message);
    }

    /**
     * @test
     */
    public function it_can_handle_in_game_notifications()
    {
        Log::shouldReceive('info')
            ->with('Notification event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('info')
            ->with('Processing in-game notification', Mockery::type('array'))
            ->once();

        $message = $this->createMockMessage([
            'notification_type' => 'in_game',
            'player_id' => 1,
            'message' => 'Your village is under attack!',
        ]);

        $message->shouldReceive('ack')->once();

        $this->handler->handle($message);
    }

    /**
     * @test
     */
    public function it_can_handle_push_notifications()
    {
        Log::shouldReceive('info')
            ->with('Notification event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('info')
            ->with('Processing push notification', Mockery::type('array'))
            ->once();

        $message = $this->createMockMessage([
            'notification_type' => 'push',
            'device_token' => 'device123',
            'title' => 'Game Alert',
            'body' => 'Your troops have returned!',
        ]);

        $message->shouldReceive('ack')->once();

        $this->handler->handle($message);
    }

    /**
     * @test
     */
    public function it_handles_unknown_notification_types()
    {
        Log::shouldReceive('info')
            ->with('Notification event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('warning')
            ->with('Unknown notification type', Mockery::type('array'))
            ->once();

        $message = $this->createMockMessage([
            'notification_type' => 'unknown_type',
            'data' => 'test',
        ]);

        $message->shouldReceive('ack')->once();

        $this->handler->handle($message);
    }

    /**
     * @test
     */
    public function it_handles_missing_notification_type()
    {
        Log::shouldReceive('info')
            ->with('Notification event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('warning')
            ->with('Unknown notification type', Mockery::type('array'))
            ->once();

        $message = $this->createMockMessage([
            'data' => 'test',
        ]);

        $message->shouldReceive('ack')->once();

        $this->handler->handle($message);
    }

    /**
     * @test
     */
    public function it_handles_exceptions_and_requeues_message()
    {
        Log::shouldReceive('info')
            ->with('Notification event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('error')
            ->with('Error processing notification', Mockery::type('array'))
            ->once();

        $message = $this->createMockMessage([
            'notification_type' => 'email',
        ]);

        // Make the message throw an exception when ack is called
        $message->shouldReceive('ack')->andThrow(new \Exception('Test exception'));
        $message->shouldReceive('nack')->with(true)->once();

        $this->handler->handle($message);
    }

    /**
     * @test
     */
    public function it_handles_exceptions_during_notification_processing()
    {
        Log::shouldReceive('info')
            ->with('Notification event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('error')
            ->with('Error processing notification', Mockery::type('array'))
            ->once();

        $message = $this->createMockMessage([
            'notification_type' => 'email',
        ]);

        // Make getBody throw an exception
        $message->shouldReceive('getBody')->andThrow(new \Exception('Test exception'));
        $message->shouldReceive('nack')->with(true)->once();

        $this->handler->handle($message);
    }

    /**
     * @test
     */
    public function it_processes_email_notification_with_complete_data()
    {
        Log::shouldReceive('info')
            ->with('Notification event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('info')
            ->with('Processing email notification', [
                'notification_type' => 'email',
                'to' => 'user@example.com',
                'subject' => 'Battle Report',
                'template' => 'battle_report',
                'data' => ['battle_id' => 1],
            ])
            ->once();

        $message = $this->createMockMessage([
            'notification_type' => 'email',
            'to' => 'user@example.com',
            'subject' => 'Battle Report',
            'template' => 'battle_report',
            'data' => ['battle_id' => 1],
        ]);

        $message->shouldReceive('ack')->once();

        $this->handler->handle($message);
    }

    /**
     * @test
     */
    public function it_processes_in_game_notification_with_complete_data()
    {
        Log::shouldReceive('info')
            ->with('Notification event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('info')
            ->with('Processing in-game notification', [
                'notification_type' => 'in_game',
                'player_id' => 1,
                'type' => 'battle_alert',
                'message' => 'Your village is under attack!',
                'priority' => 'high',
            ])
            ->once();

        $message = $this->createMockMessage([
            'notification_type' => 'in_game',
            'player_id' => 1,
            'type' => 'battle_alert',
            'message' => 'Your village is under attack!',
            'priority' => 'high',
        ]);

        $message->shouldReceive('ack')->once();

        $this->handler->handle($message);
    }

    /**
     * @test
     */
    public function it_processes_push_notification_with_complete_data()
    {
        Log::shouldReceive('info')
            ->with('Notification event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('info')
            ->with('Processing push notification', [
                'notification_type' => 'push',
                'device_token' => 'device123',
                'title' => 'Game Alert',
                'body' => 'Your troops have returned!',
                'data' => ['action' => 'troop_return'],
            ])
            ->once();

        $message = $this->createMockMessage([
            'notification_type' => 'push',
            'device_token' => 'device123',
            'title' => 'Game Alert',
            'body' => 'Your troops have returned!',
            'data' => ['action' => 'troop_return'],
        ]);

        $message->shouldReceive('ack')->once();

        $this->handler->handle($message);
    }

    private function createMockMessage(array $body): Message
    {
        $message = $this->createMock(Message::class);
        $message->method('getBody')->willReturn($body);
        $message->method('ack')->willReturnCallback(function (): void {});

        return $message;
    }
}
