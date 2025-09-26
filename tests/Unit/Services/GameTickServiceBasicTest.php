<?php

namespace Tests\Unit\Services;

use App\Models\Game\Village;
use App\Services\GameTickService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameTickServiceBasicTest extends TestCase
{
    use RefreshDatabase;

    protected $gameTickService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gameTickService = new GameTickService();
    }

    public function test_can_instantiate_game_tick_service()
    {
        $this->assertInstanceOf(GameTickService::class, $this->gameTickService);
    }

    public function test_calculate_resource_production_returns_integer()
    {
        $reflection = new \ReflectionClass($this->gameTickService);
        $method = $reflection->getMethod('calculateResourceProduction');
        $method->setAccessible(true);

        // Create a mock village
        $village = new Village();
        $village->id = 1;

        $production = $method->invoke($this->gameTickService, $village, 'wood');

        $this->assertIsInt($production);
        $this->assertGreaterThan(0, $production);
    }

    public function test_get_resource_building_levels_returns_array()
    {
        $reflection = new \ReflectionClass($this->gameTickService);
        $method = $reflection->getMethod('getResourceBuildingLevels');
        $method->setAccessible(true);

        // Create a mock village
        $village = new Village();
        $village->id = 1;

        $levels = $method->invoke($this->gameTickService, $village, 'wood');

        $this->assertIsArray($levels);
    }

    public function test_create_game_event_creates_event()
    {
        // Create test data first
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $world = \App\Models\Game\World::create([
            'name' => 'Test World',
            'description' => 'Test World Description',
            'is_active' => true,
        ]);

        $player = \App\Models\Game\Player::create([
            'user_id' => $user->id,
            'world_id' => $world->id,
            'name' => 'Test Player',
            'tribe' => 'roman',
            'points' => 1000,
            'is_online' => true,
            'last_active_at' => now(),
            'population' => 100,
            'villages_count' => 1,
            'is_active' => true,
            'last_login' => now(),
        ]);

        $village = \App\Models\Game\Village::create([
            'player_id' => $player->id,
            'world_id' => $world->id,
            'name' => 'Test Village',
            'x_coordinate' => 0,
            'y_coordinate' => 0,
            'population' => 100,
            'culture_points' => 1000,
            'is_capital' => true,
        ]);

        $reflection = new \ReflectionClass($this->gameTickService);
        $method = $reflection->getMethod('createGameEvent');
        $method->setAccessible(true);

        $method->invoke($this->gameTickService, $player, $village, 'test_event', 'Test Event', ['test' => 'data']);

        $this->assertDatabaseHas('game_events', [
            'player_id' => $player->id,
            'village_id' => $village->id,
            'event_type' => 'test_event',
            'description' => 'Test Event',
        ]);
    }

    public function test_log_resource_production_creates_log()
    {
        // Create test data first
        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $world = \App\Models\Game\World::create([
            'name' => 'Test World',
            'description' => 'Test World Description',
            'is_active' => true,
        ]);

        $player = \App\Models\Game\Player::create([
            'user_id' => $user->id,
            'world_id' => $world->id,
            'name' => 'Test Player',
            'tribe' => 'roman',
            'points' => 1000,
            'is_online' => true,
            'last_active_at' => now(),
            'population' => 100,
            'villages_count' => 1,
            'is_active' => true,
            'last_login' => now(),
        ]);

        $village = \App\Models\Game\Village::create([
            'player_id' => $player->id,
            'world_id' => $world->id,
            'name' => 'Test Village',
            'x_coordinate' => 0,
            'y_coordinate' => 0,
            'population' => 100,
            'culture_points' => 1000,
            'is_capital' => true,
        ]);

        $reflection = new \ReflectionClass($this->gameTickService);
        $method = $reflection->getMethod('logResourceProduction');
        $method->setAccessible(true);

        $method->invoke($this->gameTickService, $village, 'wood', 100, 1100);

        $this->assertDatabaseHas('resource_production_logs', [
            'village_id' => $village->id,
            'type' => 'wood',
            'amount_produced' => 100,
            'final_amount' => 1100,
        ]);
    }
}
