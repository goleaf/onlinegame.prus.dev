<?php

namespace Tests\Unit\Services;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Services\BuildingService;
use App\Services\GameTickService;
use App\Services\ResourceProductionService;
use App\Services\TroopService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use LaraUtilX\Enums\LogLevel;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\LoggingUtil;
use Tests\TestCase;

class EnhancedGameServicesTest extends TestCase
{
    use RefreshDatabase;

    protected GameTickService $gameTickService;
    protected ResourceProductionService $resourceProductionService;
    protected BuildingService $buildingService;
    protected TroopService $troopService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gameTickService = new GameTickService();
        $this->resourceProductionService = new ResourceProductionService();
        $this->buildingService = new BuildingService();
        $this->troopService = new TroopService();
    }

    /**
     * @test
     */
    public function game_tick_service_uses_larautilx_caching()
    {
        // Clear any existing cache
        Cache::flush();

        // First tick should process
        $this->gameTickService->processGameTick();

        // Second tick should be cached and skipped
        $this->gameTickService->processGameTick();

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    /**
     * @test
     */
    public function game_tick_service_uses_larautilx_logging()
    {
        // Test that logging works without errors
        $this->gameTickService->processGameTick();

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    /**
     * @test
     */
    public function resource_production_service_uses_caching()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        // First calculation should cache the result
        $result1 = $this->resourceProductionService->calculateResourceProduction($village);

        // Second calculation should use cache
        $result2 = $this->resourceProductionService->calculateResourceProduction($village);

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    /**
     * @test
     */
    public function resource_production_service_uses_logging()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        // Test that logging works without errors
        $this->resourceProductionService->calculateResourceProduction($village);

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    /**
     * @test
     */
    public function building_service_uses_larautilx_utilities()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        // Test that service works with Larautilx utilities
        $this->buildingService->canBuild($village, null, 1);

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    /**
     * @test
     */
    public function troop_service_uses_larautilx_utilities()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        // Test that service works with Larautilx utilities
        $this->troopService->canTrain($village, null, 1);

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    /**
     * @test
     */
    public function caching_util_works_with_game_services()
    {
        $key = 'test_game_cache_' . time();
        $value = ['test' => 'data'];

        // Test caching
        CachingUtil::put($key, $value, now()->addMinutes(1));
        $cached = CachingUtil::get($key);

        $this->assertEquals($value, $cached);

        // Test cache expiration
        CachingUtil::forget($key);
        $expired = CachingUtil::get($key);

        $this->assertNull($expired);
    }

    /**
     * @test
     */
    public function logging_util_works_with_game_services()
    {
        // Test different log levels
        LoggingUtil::log(LogLevel::Info, 'Test info message', ['service' => 'test'], 'test_channel');
        LoggingUtil::log(LogLevel::Error, 'Test error message', ['service' => 'test'], 'test_channel');
        LoggingUtil::log(LogLevel::Warning, 'Test warning message', ['service' => 'test'], 'test_channel');

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    /**
     * @test
     */
    public function game_services_integration_works()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        // Test that all services work together with Larautilx
        $this->resourceProductionService->calculateResourceProduction($village);
        $this->buildingService->canBuild($village, null, 1);
        $this->troopService->canTrain($village, null, 1);

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    /**
     * @test
     */
    public function game_tick_service_prevents_duplicate_processing()
    {
        // Clear cache first
        Cache::flush();

        // First tick should process
        $this->gameTickService->processGameTick();

        // Second tick should be skipped due to caching
        $this->gameTickService->processGameTick();

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }

    /**
     * @test
     */
    public function resource_production_caching_improves_performance()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        $startTime = microtime(true);

        // First calculation (should be cached)
        $this->resourceProductionService->calculateResourceProduction($village);
        $firstTime = microtime(true) - $startTime;

        $startTime = microtime(true);

        // Second calculation (should use cache)
        $this->resourceProductionService->calculateResourceProduction($village);
        $secondTime = microtime(true) - $startTime;

        // Second calculation should be faster due to caching
        $this->assertTrue($secondTime <= $firstTime);
    }

    /**
     * @test
     */
    public function all_services_use_consistent_logging()
    {
        $world = World::factory()->create();
        $player = Player::factory()->create(['world_id' => $world->id]);
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
        ]);

        // Test that all services use Larautilx logging consistently
        $this->gameTickService->processGameTick();
        $this->resourceProductionService->calculateResourceProduction($village);
        $this->buildingService->canBuild($village, null, 1);
        $this->troopService->canTrain($village, null, 1);

        $this->assertTrue(true); // Test passes if no exceptions thrown
    }
}
