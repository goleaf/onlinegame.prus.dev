<?php

namespace Tests\Unit\Listeners;

use App\Events\GameEvent;
use App\Listeners\GameEventListener;
use App\Services\GameNotificationService;
use App\Services\RealTimeGameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class GameEventListenerTest extends TestCase
{
    use RefreshDatabase;

    protected GameEventListener $listener;

    protected $notificationService;

    protected $realTimeService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notificationService = Mockery::mock(GameNotificationService::class);
        $this->realTimeService = Mockery::mock(RealTimeGameService::class);

        $this->listener = new GameEventListener(
            $this->notificationService,
            $this->realTimeService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_handles_game_event_successfully()
    {
        $event = new GameEvent(1, 'village_attacked', ['village_id' => 1, 'attacker_id' => 2]);

        Log::shouldReceive('info')
            ->once()
            ->with('Game event received', [
                'user_id' => 1,
                'event_type' => 'village_attacked',
                'data' => ['village_id' => 1, 'attacker_id' => 2],
                'timestamp' => $event->timestamp,
            ]);

        $this
            ->realTimeService
            ->shouldReceive('sendUserUpdate')
            ->once()
            ->with(1, 'village_attacked', ['village_id' => 1, 'attacker_id' => 2]);

        $this
            ->notificationService
            ->shouldReceive('sendUserNotification')
            ->once()
            ->with(1, 'village_attacked', ['village_id' => 1, 'attacker_id' => 2]);

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_handles_game_event_without_notification()
    {
        $event = new GameEvent(1, 'player_login', ['ip' => '192.168.1.1']);

        Log::shouldReceive('info')
            ->once()
            ->with('Game event received', [
                'user_id' => 1,
                'event_type' => 'player_login',
                'data' => ['ip' => '192.168.1.1'],
                'timestamp' => $event->timestamp,
            ]);

        $this
            ->realTimeService
            ->shouldReceive('sendUserUpdate')
            ->once()
            ->with(1, 'player_login', ['ip' => '192.168.1.1']);

        $this
            ->notificationService
            ->shouldNotReceive('sendUserNotification');

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_handles_building_completed_event_with_notification()
    {
        $event = new GameEvent(1, 'building_completed', ['building_id' => 5, 'level' => 3]);

        Log::shouldReceive('info')
            ->once()
            ->with('Game event received', [
                'user_id' => 1,
                'event_type' => 'building_completed',
                'data' => ['building_id' => 5, 'level' => 3],
                'timestamp' => $event->timestamp,
            ]);

        $this
            ->realTimeService
            ->shouldReceive('sendUserUpdate')
            ->once()
            ->with(1, 'building_completed', ['building_id' => 5, 'level' => 3]);

        $this
            ->notificationService
            ->shouldReceive('sendUserNotification')
            ->once()
            ->with(1, 'building_completed', ['building_id' => 5, 'level' => 3]);

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_handles_quest_completed_event_with_notification()
    {
        $event = new GameEvent(1, 'quest_completed', ['quest_id' => 10, 'reward' => 'gold']);

        Log::shouldReceive('info')
            ->once()
            ->with('Game event received', [
                'user_id' => 1,
                'event_type' => 'quest_completed',
                'data' => ['quest_id' => 10, 'reward' => 'gold'],
                'timestamp' => $event->timestamp,
            ]);

        $this
            ->realTimeService
            ->shouldReceive('sendUserUpdate')
            ->once()
            ->with(1, 'quest_completed', ['quest_id' => 10, 'reward' => 'gold']);

        $this
            ->notificationService
            ->shouldReceive('sendUserNotification')
            ->once()
            ->with(1, 'quest_completed', ['quest_id' => 10, 'reward' => 'gold']);

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_handles_alliance_joined_event_with_notification()
    {
        $event = new GameEvent(1, 'alliance_joined', ['alliance_id' => 5, 'alliance_name' => 'Test Alliance']);

        Log::shouldReceive('info')
            ->once()
            ->with('Game event received', [
                'user_id' => 1,
                'event_type' => 'alliance_joined',
                'data' => ['alliance_id' => 5, 'alliance_name' => 'Test Alliance'],
                'timestamp' => $event->timestamp,
            ]);

        $this
            ->realTimeService
            ->shouldReceive('sendUserUpdate')
            ->once()
            ->with(1, 'alliance_joined', ['alliance_id' => 5, 'alliance_name' => 'Test Alliance']);

        $this
            ->notificationService
            ->shouldReceive('sendUserNotification')
            ->once()
            ->with(1, 'alliance_joined', ['alliance_id' => 5, 'alliance_name' => 'Test Alliance']);

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_handles_message_received_event_with_notification()
    {
        $event = new GameEvent(1, 'message_received', ['message_id' => 100, 'sender_id' => 2]);

        Log::shouldReceive('info')
            ->once()
            ->with('Game event received', [
                'user_id' => 1,
                'event_type' => 'message_received',
                'data' => ['message_id' => 100, 'sender_id' => 2],
                'timestamp' => $event->timestamp,
            ]);

        $this
            ->realTimeService
            ->shouldReceive('sendUserUpdate')
            ->once()
            ->with(1, 'message_received', ['message_id' => 100, 'sender_id' => 2]);

        $this
            ->notificationService
            ->shouldReceive('sendUserNotification')
            ->once()
            ->with(1, 'message_received', ['message_id' => 100, 'sender_id' => 2]);

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_handles_real_time_service_exception()
    {
        $event = new GameEvent(1, 'village_attacked', ['village_id' => 1]);

        Log::shouldReceive('info')
            ->once()
            ->with('Game event received', [
                'user_id' => 1,
                'event_type' => 'village_attacked',
                'data' => ['village_id' => 1],
                'timestamp' => $event->timestamp,
            ]);

        $this
            ->realTimeService
            ->shouldReceive('sendUserUpdate')
            ->once()
            ->andThrow(new \Exception('Real-time service error'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to handle game event', [
                'user_id' => 1,
                'event_type' => 'village_attacked',
                'error' => 'Real-time service error',
                'trace' => \Mockery::type('string'),
            ]);

        $this
            ->notificationService
            ->shouldNotReceive('sendUserNotification');

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_handles_notification_service_exception()
    {
        $event = new GameEvent(1, 'village_attacked', ['village_id' => 1]);

        Log::shouldReceive('info')
            ->once()
            ->with('Game event received', [
                'user_id' => 1,
                'event_type' => 'village_attacked',
                'data' => ['village_id' => 1],
                'timestamp' => $event->timestamp,
            ]);

        $this
            ->realTimeService
            ->shouldReceive('sendUserUpdate')
            ->once()
            ->with(1, 'village_attacked', ['village_id' => 1]);

        $this
            ->notificationService
            ->shouldReceive('sendUserNotification')
            ->once()
            ->andThrow(new \Exception('Notification service error'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to handle game event', [
                'user_id' => 1,
                'event_type' => 'village_attacked',
                'error' => 'Notification service error',
                'trace' => \Mockery::type('string'),
            ]);

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_handles_logging_exception()
    {
        $event = new GameEvent(1, 'village_attacked', ['village_id' => 1]);

        Log::shouldReceive('info')
            ->once()
            ->andThrow(new \Exception('Logging error'));

        Log::shouldReceive('error')
            ->once()
            ->with('Failed to handle game event', [
                'user_id' => 1,
                'event_type' => 'village_attacked',
                'error' => 'Logging error',
                'trace' => \Mockery::type('string'),
            ]);

        $this
            ->realTimeService
            ->shouldNotReceive('sendUserUpdate');

        $this
            ->notificationService
            ->shouldNotReceive('sendUserNotification');

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_handles_empty_event_data()
    {
        $event = new GameEvent(1, 'player_logout');

        Log::shouldReceive('info')
            ->once()
            ->with('Game event received', [
                'user_id' => 1,
                'event_type' => 'player_logout',
                'data' => [],
                'timestamp' => $event->timestamp,
            ]);

        $this
            ->realTimeService
            ->shouldReceive('sendUserUpdate')
            ->once()
            ->with(1, 'player_logout', []);

        $this
            ->notificationService
            ->shouldNotReceive('sendUserNotification');

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_handles_complex_event_data()
    {
        $complexData = [
            'battle_id' => 1,
            'attacker' => [
                'id' => 1,
                'name' => 'Player 1',
                'troops' => ['infantry' => 100, 'cavalry' => 50],
            ],
            'defender' => [
                'id' => 2,
                'name' => 'Player 2',
                'troops' => ['infantry' => 80, 'cavalry' => 30],
            ],
            'result' => 'victory',
            'loot' => ['wood' => 1000, 'clay' => 500],
        ];

        $event = new GameEvent(1, 'battle_completed', $complexData);

        Log::shouldReceive('info')
            ->once()
            ->with('Game event received', [
                'user_id' => 1,
                'event_type' => 'battle_completed',
                'data' => $complexData,
                'timestamp' => $event->timestamp,
            ]);

        $this
            ->realTimeService
            ->shouldReceive('sendUserUpdate')
            ->once()
            ->with(1, 'battle_completed', $complexData);

        $this
            ->notificationService
            ->shouldNotReceive('sendUserNotification');

        $this->listener->handle($event);
    }

    /**
     * @test
     */
    public function it_implements_should_queue_interface()
    {
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $this->listener);
    }

    /**
     * @test
     */
    public function it_uses_interacts_with_queue_trait()
    {
        $this->assertTrue(method_exists($this->listener, 'onQueue'));
        $this->assertTrue(method_exists($this->listener, 'onConnection'));
        $this->assertTrue(method_exists($this->listener, 'onDelay'));
    }

    /**
     * @test
     */
    public function it_has_correct_constructor_dependencies()
    {
        $reflection = new \ReflectionClass($this->listener);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('notificationService', $parameters[0]->getName());
        $this->assertEquals('realTimeService', $parameters[1]->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_handle_method_signature()
    {
        $reflection = new \ReflectionClass($this->listener);
        $handleMethod = $reflection->getMethod('handle');
        $parameters = $handleMethod->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('event', $parameters[0]->getName());
        $this->assertEquals(GameEvent::class, $parameters[0]->getType()->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_should_send_notification_method()
    {
        $reflection = new \ReflectionClass($this->listener);
        $method = $reflection->getMethod('shouldSendNotification');
        $parameters = $method->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('eventType', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
    }

    /**
     * @test
     */
    public function it_returns_correct_notification_events()
    {
        $reflection = new \ReflectionClass($this->listener);
        $method = $reflection->getMethod('shouldSendNotification');
        $method->setAccessible(true);

        $notificationEvents = [
            'village_attacked',
            'building_completed',
            'quest_completed',
            'alliance_joined',
            'message_received',
        ];

        foreach ($notificationEvents as $eventType) {
            $this->assertTrue($method->invoke($this->listener, $eventType));
        }

        $nonNotificationEvents = [
            'player_login',
            'player_logout',
            'resource_produced',
            'troop_trained',
        ];

        foreach ($nonNotificationEvents as $eventType) {
            $this->assertFalse($method->invoke($this->listener, $eventType));
        }
    }

    /**
     * @test
     */
    public function it_handles_multiple_events_in_sequence()
    {
        $events = [
            new GameEvent(1, 'village_attacked', ['village_id' => 1]),
            new GameEvent(1, 'building_completed', ['building_id' => 5]),
            new GameEvent(1, 'player_login', ['ip' => '192.168.1.1']),
        ];

        Log::shouldReceive('info')->times(3);
        $this->realTimeService->shouldReceive('sendUserUpdate')->times(3);
        $this->notificationService->shouldReceive('sendUserNotification')->times(2);  // Only for village_attacked and building_completed

        foreach ($events as $event) {
            $this->listener->handle($event);
        }
    }
}
