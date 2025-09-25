<?php

namespace Tests\Unit\Services;

use App\Models\Game\Building;
use App\Models\Game\BuildingQueue;
use App\Models\Game\GameEvent;
use App\Models\Game\Movement;
use App\Models\Game\Player;
use App\Models\Game\Resource;
use App\Models\Game\TrainingQueue;
use App\Models\Game\Village;
use App\Models\User;
use App\Services\GameTickService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GameTickServiceSimpleTest extends TestCase
{
    use RefreshDatabase;

    protected $gameTickService;
    protected $player;
    protected $village;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gameTickService = new GameTickService();

        // Create test user and player manually
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create world first
        $world = \App\Models\Game\World::create([
            'name' => 'Test World',
            'description' => 'Test World Description',
            'is_active' => true,
        ]);

        $this->player = Player::create([
            'user_id' => $user->id,
            'world_id' => $world->id,
            'name' => 'Test Player',
            'tribe' => 'roman',
            'points' => 1000,
            'is_online' => true,
            'last_active_at' => now(),
        ]);

        // Create test village
        $this->village = Village::create([
            'player_id' => $this->player->id,
            'world_id' => $world->id,
            'name' => 'Test Village',
            'x_coordinate' => 0,
            'y_coordinate' => 0,
            'population' => 100,
            'culture_points' => 1000,
            'is_capital' => true,
        ]);

        // Create basic resource buildings
        $buildingTypes = [
            ['key' => 'woodcutter', 'name' => 'Woodcutter'],
            ['key' => 'clay_pit', 'name' => 'Clay Pit'],
            ['key' => 'iron_mine', 'name' => 'Iron Mine'],
            ['key' => 'crop_field', 'name' => 'Crop Field'],
        ];

        foreach ($buildingTypes as $index => $buildingType) {
            $bt = \App\Models\Game\BuildingType::create([
                'name' => $buildingType['name'],
                'key' => $buildingType['key'],
                'description' => 'Test building',
                'max_level' => 20,
                'is_active' => true,
            ]);

            \App\Models\Game\Building::create([
                'village_id' => $this->village->id,
                'building_type_id' => $bt->id,
                'name' => $buildingType['name'],
                'level' => 1,
                'x' => $index,
                'y' => 0,
                'is_active' => true,
            ]);
        }
    }

    public function test_process_game_tick_successfully()
    {
        // Create test resources with a fixed timestamp
        $pastTime = now()->subSeconds(10);
        $resource = Resource::create([
            'village_id' => $this->village->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10,
            'storage_capacity' => 10000,
            'level' => 1,
            'last_updated' => $pastTime
        ]);

        // Debug: Check if buildings exist
        $buildings = $this->village->buildings()->with('buildingType')->get();
        $this->assertGreaterThan(0, $buildings->count(), 'No buildings found');

        // Debug: Check production rate
        $productionRate = $this->gameTickService->calculateResourceProduction($this->village, 'wood');
        $this->assertGreaterThan(0, $productionRate, 'Production rate is 0');

        // Debug: Check time calculation
        $timeSinceLastUpdate = now()->diffInSeconds($resource->last_updated);
        $this->assertGreaterThan(0, $timeSinceLastUpdate, 'Time since last update is negative or zero');

        // Process game tick
        $this->gameTickService->processGameTick();

        // Assert resources were updated
        $resource->refresh();
        $this->assertGreaterThan(1000, $resource->amount, "Resource amount: {$resource->amount}, Expected: > 1000");
    }

    public function test_process_resource_production()
    {
        // Create test resources with a fixed timestamp
        $pastTime = now()->subSeconds(5);
        $woodResource = Resource::create([
            'village_id' => $this->village->id,
            'type' => 'wood',
            'amount' => 1000,
            'production_rate' => 10,
            'storage_capacity' => 10000,
            'level' => 1,
            'last_updated' => $pastTime
        ]);

        // Debug: Check time calculation
        $timeSinceLastUpdate = now()->diffInSeconds($woodResource->last_updated);
        $this->assertGreaterThan(0, $timeSinceLastUpdate, 'Time since last update is negative or zero');

        $this->gameTickService->processGameTick();

        // Assert wood resource was updated
        $woodResource->refresh();
        $this->assertGreaterThan(1000, $woodResource->amount, "Resource amount: {$woodResource->amount}, Expected: > 1000");
    }

    public function test_calculate_resource_production()
    {
        $reflection = new \ReflectionClass($this->gameTickService);
        $method = $reflection->getMethod('calculateResourceProduction');
        $method->setAccessible(true);

        $production = $method->invoke($this->gameTickService, $this->village, 'wood');

        $this->assertIsInt($production);
        $this->assertGreaterThan(0, $production);
    }

    public function test_get_resource_building_levels()
    {
        $reflection = new \ReflectionClass($this->gameTickService);
        $method = $reflection->getMethod('getResourceBuildingLevels');
        $method->setAccessible(true);

        $levels = $method->invoke($this->gameTickService, $this->village, 'wood');

        $this->assertIsArray($levels);
    }

    public function test_create_game_event()
    {
        $reflection = new \ReflectionClass($this->gameTickService);
        $method = $reflection->getMethod('createGameEvent');
        $method->setAccessible(true);

        $method->invoke($this->gameTickService, $this->player->id, $this->village->id, 'test_event', 'Test Event', ['test' => 'data']);

        $this->assertDatabaseHas('game_events', [
            'player_id' => $this->player->id,
            'village_id' => $this->village->id,
            'event_type' => 'test_event',
            'description' => 'Test Event'
        ]);
    }

    public function test_log_resource_production()
    {
        $reflection = new \ReflectionClass($this->gameTickService);
        $method = $reflection->getMethod('logResourceProduction');
        $method->setAccessible(true);

        $method->invoke($this->gameTickService, $this->village, 'wood', 100, 1100);

        $this->assertDatabaseHas('resource_production_logs', [
            'village_id' => $this->village->id,
            'type' => 'wood',
            'amount_produced' => 100,
            'final_amount' => 1100
        ]);
    }

    public function test_game_tick_with_database_transaction()
    {
        // Mock database transaction
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();
        DB::shouldReceive('rollBack')->never();

        $this->gameTickService->processGameTick();
    }

    public function test_game_tick_handles_exceptions()
    {
        // Mock database transaction to throw exception
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('rollBack')->once();
        DB::shouldReceive('commit')->never();

        // Mock a method to throw exception
        $this->mock(GameTickService::class, function ($mock) {
            $mock->shouldReceive('processGameTick')->andThrow(new \Exception('Test exception'));
        });

        $this->expectException(\Exception::class);
        $this->gameTickService->processGameTick();
    }
}
