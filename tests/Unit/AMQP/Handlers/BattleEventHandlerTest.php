<?php

namespace Tests\Unit\AMQP\Handlers;

use App\AMQP\Handlers\BattleEventHandler;
use App\Models\Game\Battle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BattleEventHandlerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_handle_battle_started_event()
    {
        $handler = new BattleEventHandler();
        $battle = Battle::factory()->create();

        $message = [
            'event_type' => 'battle_started',
            'battle_id' => $battle->id,
            'data' => [
                'attacker_id' => $battle->attacker_id,
                'defender_id' => $battle->defender_id,
                'village_id' => $battle->village_id,
                'started_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_battle_ended_event()
    {
        $handler = new BattleEventHandler();
        $battle = Battle::factory()->create();

        $message = [
            'event_type' => 'battle_ended',
            'battle_id' => $battle->id,
            'data' => [
                'result' => 'victory',
                'attacker_losses' => ['legionnaires' => 20],
                'defender_losses' => ['legionnaires' => 60],
                'loot' => ['wood' => 1000, 'clay' => 800],
                'ended_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_battle_victory_event()
    {
        $handler = new BattleEventHandler();
        $battle = Battle::factory()->create();

        $message = [
            'event_type' => 'battle_victory',
            'battle_id' => $battle->id,
            'data' => [
                'victor_id' => $battle->attacker_id,
                'victory_type' => 'total_victory',
                'victory_points' => 100,
                'victory_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_battle_defeat_event()
    {
        $handler = new BattleEventHandler();
        $battle = Battle::factory()->create();

        $message = [
            'event_type' => 'battle_defeat',
            'battle_id' => $battle->id,
            'data' => [
                'defeated_id' => $battle->attacker_id,
                'defeat_type' => 'total_defeat',
                'defeat_points' => -50,
                'defeat_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_battle_draw_event()
    {
        $handler = new BattleEventHandler();
        $battle = Battle::factory()->create();

        $message = [
            'event_type' => 'battle_draw',
            'battle_id' => $battle->id,
            'data' => [
                'draw_type' => 'stalemate',
                'draw_points' => 0,
                'draw_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_war_battle_event()
    {
        $handler = new BattleEventHandler();
        $battle = Battle::factory()->create(['war_id' => 1]);

        $message = [
            'event_type' => 'war_battle',
            'battle_id' => $battle->id,
            'data' => [
                'war_id' => 1,
                'alliance_1_id' => 1,
                'alliance_2_id' => 2,
                'battle_impact' => 'major',
                'battle_at' => now()->toISOString(),
            ],
        ];

        $result = $handler->handle($message);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_handle_battle_statistics_update_event()
    {
        $handler = new BattleEventHandler();
        $battle = Battle::factory()->create();

        $message = [
            'event_type' => 'battle_statistics_update',
            'battle_id' => $battle->id,
            'data' => [
                'total_troops_killed' => 100,
                'total_troops_lost' => 50,
                'total_loot_gained' => 5000,
                'updated_at' => now()->toISOString(),
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
        $handler = new BattleEventHandler();

        $message = [
            'event_type' => 'invalid_event',
            'battle_id' => 1,
            'data' => [],
        ];

        $result = $handler->handle($message);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_handles_missing_battle_id()
    {
        $handler = new BattleEventHandler();

        $message = [
            'event_type' => 'battle_started',
            'data' => [],
        ];

        $result = $handler->handle($message);

        $this->assertFalse($result);
    }
}
