<?php

namespace Tests\Unit\Events;

use App\Events\GameEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GameEventTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_game_event()
    {
        $event = new GameEvent('test_event', ['data' => 'test']);

        $this->assertInstanceOf(GameEvent::class, $event);
        $this->assertEquals('test_event', $event->getEventType());
        $this->assertEquals(['data' => 'test'], $event->getData());
    }

    /**
     * @test
     */
    public function it_can_get_event_type()
    {
        $event = new GameEvent('player_action', []);

        $this->assertEquals('player_action', $event->getEventType());
    }

    /**
     * @test
     */
    public function it_can_get_data()
    {
        $data = ['player_id' => 1, 'action' => 'login'];
        $event = new GameEvent('player_action', $data);

        $this->assertEquals($data, $event->getData());
    }

    /**
     * @test
     */
    public function it_can_set_event_type()
    {
        $event = new GameEvent('test', []);
        $event->setEventType('new_event');

        $this->assertEquals('new_event', $event->getEventType());
    }

    /**
     * @test
     */
    public function it_can_set_data()
    {
        $event = new GameEvent('test', []);
        $newData = ['new' => 'data'];
        $event->setData($newData);

        $this->assertEquals($newData, $event->getData());
    }

    /**
     * @test
     */
    public function it_can_serialize_event()
    {
        $event = new GameEvent('test_event', ['key' => 'value']);
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(GameEvent::class, $unserialized);
        $this->assertEquals('test_event', $unserialized->getEventType());
        $this->assertEquals(['key' => 'value'], $unserialized->getData());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
