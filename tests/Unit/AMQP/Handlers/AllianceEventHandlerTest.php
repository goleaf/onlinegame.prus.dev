<?php

namespace Tests\Unit\AMQP\Handlers;

use App\AMQP\Handlers\AllianceEventHandler;
use App\Models\Game\Alliance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllianceEventHandlerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_handle_alliance_created_event()
    {
        $handler = new AllianceEventHandler();
        $alliance = Alliance::factory()->create();

        $message = [
            'event_type' => 'alliance_created',
            'alliance_id' => $alliance->id,
            'data' => [
                'name' => $alliance->name,
                'tag' => $alliance->tag,
                'leader_id' => $alliance->leader_id,
                'created_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_alliance_member_joined_event()
    {
        $handler = new AllianceEventHandler();
        $alliance = Alliance::factory()->create();

        $message = [
            'event_type' => 'alliance_member_joined',
            'alliance_id' => $alliance->id,
            'data' => [
                'player_id' => 1,
                'player_name' => 'TestPlayer',
                'joined_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_alliance_member_left_event()
    {
        $handler = new AllianceEventHandler();
        $alliance = Alliance::factory()->create();

        $message = [
            'event_type' => 'alliance_member_left',
            'alliance_id' => $alliance->id,
            'data' => [
                'player_id' => 1,
                'player_name' => 'TestPlayer',
                'left_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_alliance_war_declared_event()
    {
        $handler = new AllianceEventHandler();
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $message = [
            'event_type' => 'alliance_war_declared',
            'alliance_id' => $alliance1->id,
            'data' => [
                'target_alliance_id' => $alliance2->id,
                'target_alliance_name' => $alliance2->name,
                'declared_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_alliance_war_ended_event()
    {
        $handler = new AllianceEventHandler();
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $message = [
            'event_type' => 'alliance_war_ended',
            'alliance_id' => $alliance1->id,
            'data' => [
                'target_alliance_id' => $alliance2->id,
                'result' => 'victory',
                'ended_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_alliance_diplomacy_event()
    {
        $handler = new AllianceEventHandler();
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        $message = [
            'event_type' => 'alliance_diplomacy',
            'alliance_id' => $alliance1->id,
            'data' => [
                'target_alliance_id' => $alliance2->id,
                'diplomacy_type' => 'alliance',
                'status' => 'active',
                'initiated_at' => now()->toISOString(),
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
        $handler = new AllianceEventHandler();

        $message = [
            'event_type' => 'invalid_event',
            'alliance_id' => 1,
            'data' => [],
        ];

        $result = $handler->handle($message);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_handles_missing_alliance_id()
    {
        $handler = new AllianceEventHandler();

        $message = [
            'event_type' => 'alliance_created',
            'data' => [],
        ];

        $result = $handler->handle($message);

        $this->assertFalse($result);
    }
}
