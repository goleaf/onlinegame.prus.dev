<?php

namespace Tests\Unit\AMQP\Handlers;

use App\AMQP\Handlers\MovementEventHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovementEventHandlerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_handle_troop_movement_started_event()
    {
        $handler = new MovementEventHandler();

        $message = [
            'event_type' => 'troop_movement_started',
            'movement_id' => 1,
            'data' => [
                'player_id' => 1,
                'from_village_id' => 1,
                'to_village_id' => 2,
                'troops' => ['legionnaires' => 100, 'praetorians' => 50],
                'movement_type' => 'attack',
                'started_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_troop_movement_completed_event()
    {
        $handler = new MovementEventHandler();

        $message = [
            'event_type' => 'troop_movement_completed',
            'movement_id' => 1,
            'data' => [
                'player_id' => 1,
                'from_village_id' => 1,
                'to_village_id' => 2,
                'movement_type' => 'attack',
                'result' => 'success',
                'completed_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_troop_movement_cancelled_event()
    {
        $handler = new MovementEventHandler();

        $message = [
            'event_type' => 'troop_movement_cancelled',
            'movement_id' => 1,
            'data' => [
                'player_id' => 1,
                'reason' => 'player_cancelled',
                'cancelled_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_troop_movement_intercepted_event()
    {
        $handler = new MovementEventHandler();

        $message = [
            'event_type' => 'troop_movement_intercepted',
            'movement_id' => 1,
            'data' => [
                'player_id' => 1,
                'interceptor_id' => 2,
                'interception_point' => ['x' => 100, 'y' => 200],
                'intercepted_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_troop_movement_delayed_event()
    {
        $handler = new MovementEventHandler();

        $message = [
            'event_type' => 'troop_movement_delayed',
            'movement_id' => 1,
            'data' => [
                'player_id' => 1,
                'delay_reason' => 'weather_conditions',
                'delay_duration' => 3600,
                'new_eta' => now()->addHour()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_troop_movement_speed_boost_event()
    {
        $handler = new MovementEventHandler();

        $message = [
            'event_type' => 'troop_movement_speed_boost',
            'movement_id' => 1,
            'data' => [
                'player_id' => 1,
                'boost_type' => 'artifact_effect',
                'boost_multiplier' => 1.5,
                'boost_duration' => 1800,
                'boosted_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_troop_movement_return_event()
    {
        $handler = new MovementEventHandler();

        $message = [
            'event_type' => 'troop_movement_return',
            'movement_id' => 1,
            'data' => [
                'player_id' => 1,
                'from_village_id' => 2,
                'to_village_id' => 1,
                'return_reason' => 'battle_completed',
                'returned_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_handles_invalid_event_type()
    {
        $handler = new MovementEventHandler();

        $message = [
            'event_type' => 'invalid_event',
            'movement_id' => 1,
            'data' => [],
        ];

        $result = $handler->handle($message);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_handles_missing_movement_id()
    {
        $handler = new MovementEventHandler();

        $message = [
            'event_type' => 'troop_movement_started',
            'data' => [],
        ];

        $result = $handler->handle($message);

        $this->assertFalse($result);
    }
}
