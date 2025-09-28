<?php

namespace Tests\Unit\AMQP\Handlers;

use App\AMQP\Handlers\GameEventHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;
use Usmonaliyev\SimpleRabbit\MQ\Message;

class GameEventHandlerTest extends TestCase
{
    use RefreshDatabase;

    private GameEventHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new GameEventHandler();
    }

    /**
     * @test
     */
    public function it_can_handle_player_action_events()
    {
        Log::shouldReceive('info')
            ->with('Game event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('info')
            ->with('Processing player action', Mockery::type('array'))
            ->once();

        $message = $this->createMockMessage([
            'event_type' => 'player_action',
            'player_id' => 1,
            'action' => 'login',
        ]);

        $message->shouldReceive('ack')->once();

        $this->handler->handle($message);
    }

    /**
     * @test
     */
    public function it_can_handle_building_completed_events()
    {
        Log::shouldReceive('info')
            ->with('Game event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('info')
            ->with('Building completed', Mockery::type('array'))
            ->once();

        $message = $this->createMockMessage([
            'event_type' => 'building_completed',
            'village_id' => 1,
            'building_type' => 'barracks',
        ]);

        $message->shouldReceive('ack')->once();

        $this->handler->handle($message);
    }

    /**
     * @test
     */
    public function it_can_handle_battle_result_events()
    {
        Log::shouldReceive('info')
            ->with('Game event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('info')
            ->with('Battle result processed', Mockery::type('array'))
            ->once();

        $message = $this->createMockMessage([
            'event_type' => 'battle_result',
            'battle_id' => 1,
            'attacker_id' => 1,
            'defender_id' => 2,
        ]);

        $message->shouldReceive('ack')->once();

        $this->handler->handle($message);
    }

    /**
     * @test
     */
    public function it_can_handle_resource_update_events()
    {
        Log::shouldReceive('info')
            ->with('Game event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('info')
            ->with('Resource update processed', Mockery::type('array'))
            ->once();

        $message = $this->createMockMessage([
            'event_type' => 'resource_update',
            'village_id' => 1,
            'resources' => ['wood' => 1000, 'clay' => 500],
        ]);

        $message->shouldReceive('ack')->once();

        $this->handler->handle($message);
    }

    /**
     * @test
     */
    public function it_can_handle_spy_caught_events()
    {
        Log::shouldReceive('info')
            ->with('Game event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('info')
            ->with('Spy caught event processed', Mockery::type('array'))
            ->once();

        $message = $this->createMockMessage([
            'event_type' => 'spy_caught',
            'spy_id' => 1,
            'target_id' => 2,
        ]);

        $message->shouldReceive('ack')->once();

        $this->handler->handle($message);
    }

    /**
     * @test
     */
    public function it_can_handle_spy_success_events()
    {
        Log::shouldReceive('info')
            ->with('Game event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('info')
            ->with('Spy success event processed', Mockery::type('array'))
            ->once();

        $message = $this->createMockMessage([
            'event_type' => 'spy_success',
            'spy_id' => 1,
            'target_id' => 2,
            'intelligence_data' => ['troops' => 100],
        ]);

        $message->shouldReceive('ack')->once();

        $this->handler->handle($message);
    }

    /**
     * @test
     */
    public function it_can_handle_battle_simulation_events()
    {
        Log::shouldReceive('info')
            ->with('Game event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('info')
            ->with('Battle simulation event processed', Mockery::type('array'))
            ->once();

        $message = $this->createMockMessage([
            'event_type' => 'battle_simulation',
            'simulation_id' => 1,
            'results' => ['attacker_wins' => true],
        ]);

        $message->shouldReceive('ack')->once();

        $this->handler->handle($message);
    }

    /**
     * @test
     */
    public function it_handles_unknown_event_types()
    {
        Log::shouldReceive('info')
            ->with('Game event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('warning')
            ->with('Unknown game event type', Mockery::type('array'))
            ->once();

        $message = $this->createMockMessage([
            'event_type' => 'unknown_event',
            'data' => 'test',
        ]);

        $message->shouldReceive('ack')->once();

        $this->handler->handle($message);
    }

    /**
     * @test
     */
    public function it_handles_missing_event_type()
    {
        Log::shouldReceive('info')
            ->with('Game event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('warning')
            ->with('Unknown game event type', Mockery::type('array'))
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
            ->with('Game event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('error')
            ->with('Error processing game event', Mockery::type('array'))
            ->once();

        $message = $this->createMockMessage([
            'event_type' => 'player_action',
        ]);

        // Make the message throw an exception when ack is called
        $message->shouldReceive('ack')->andThrow(new \Exception('Test exception'));
        $message->shouldReceive('nack')->with(true)->once();

        $this->handler->handle($message);
    }

    /**
     * @test
     */
    public function it_handles_exceptions_during_event_processing()
    {
        Log::shouldReceive('info')
            ->with('Game event received', Mockery::type('array'))
            ->once();
        Log::shouldReceive('error')
            ->with('Error processing game event', Mockery::type('array'))
            ->once();

        $message = $this->createMockMessage([
            'event_type' => 'player_action',
        ]);

        // Make getBody throw an exception
        $message->shouldReceive('getBody')->andThrow(new \Exception('Test exception'));
        $message->shouldReceive('nack')->with(true)->once();

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
