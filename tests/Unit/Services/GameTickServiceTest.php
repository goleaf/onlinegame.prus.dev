<?php

namespace Tests\Unit\Services;

use App\Models\Game\BuildingQueue;
use App\Models\Game\GameEvent;
use App\Models\Game\Player;
use App\Models\Game\Resource;
use App\Models\Game\TrainingQueue;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Services\GameTickService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameTickServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_tick_processes_successfully()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $resource = Resource::factory()->create([
            'village_id' => $village->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10,
        ]);

        $gameTickService = new GameTickService();
        $gameTickService->processGameTick();

        $this->assertDatabaseHas('resources', [
            'id' => $resource->id,
            'amount' => 1010,  // 1000 + 10 production
        ]);
    }

    public function test_game_tick_processes_building_queues()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $buildingQueue = BuildingQueue::factory()->create([
            'village_id' => $village->id,
            'completed_at' => now()->subMinute(),
            'is_completed' => false,
        ]);

        $gameTickService = new GameTickService();
        $gameTickService->processGameTick();

        $this->assertDatabaseHas('building_queues', [
            'id' => $buildingQueue->id,
            'is_completed' => true,
        ]);
    }

    public function test_game_tick_processes_training_queues()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $trainingQueue = TrainingQueue::factory()->create([
            'village_id' => $village->id,
            'completed_at' => now()->subMinute(),
            'is_completed' => false,
        ]);

        $gameTickService = new GameTickService();
        $gameTickService->processGameTick();

        $this->assertDatabaseHas('training_queues', [
            'id' => $trainingQueue->id,
            'is_completed' => true,
        ]);
    }

    public function test_game_tick_processes_game_events()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $event = GameEvent::factory()->create([
            'player_id' => $player->id,
            'village_id' => $village->id,
            'triggered_at' => now()->subMinute(),
            'is_completed' => false,
        ]);

        $gameTickService = new GameTickService();
        $gameTickService->processGameTick();

        $this->assertDatabaseHas('game_events', [
            'id' => $event->id,
            'is_completed' => true,
        ]);
    }

    public function test_game_tick_updates_player_statistics()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
            'population' => 1000,
        ]);

        $gameTickService = new GameTickService();
        $gameTickService->processGameTick();

        $player->refresh();
        $this->assertEquals(1000, $player->population);
        $this->assertEquals(1, $player->villages_count);
    }

    public function test_get_game_tick_status()
    {
        $gameTickService = new GameTickService();
        $status = $gameTickService->getGameTickStatus();

        $this->assertArrayHasKey('last_tick', $status);
        $this->assertArrayHasKey('pending_buildings', $status);
        $this->assertArrayHasKey('pending_training', $status);
        $this->assertArrayHasKey('pending_events', $status);
    }
}
