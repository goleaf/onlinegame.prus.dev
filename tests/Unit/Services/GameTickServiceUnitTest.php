<?php

namespace Tests\Unit\Services;

use App\Models\Game\Village;
use App\Services\GameTickService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameTickServiceUnitTest extends TestCase
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

    public function test_calculate_resource_production_with_different_types()
    {
        $reflection = new \ReflectionClass($this->gameTickService);
        $method = $reflection->getMethod('calculateResourceProduction');
        $method->setAccessible(true);

        $village = new Village();
        $village->id = 1;

        $woodProduction = $method->invoke($this->gameTickService, $village, 'wood');
        $clayProduction = $method->invoke($this->gameTickService, $village, 'clay');
        $ironProduction = $method->invoke($this->gameTickService, $village, 'iron');
        $cropProduction = $method->invoke($this->gameTickService, $village, 'crop');

        $this->assertIsInt($woodProduction);
        $this->assertIsInt($clayProduction);
        $this->assertIsInt($ironProduction);
        $this->assertIsInt($cropProduction);

        $this->assertGreaterThan(0, $woodProduction);
        $this->assertGreaterThan(0, $clayProduction);
        $this->assertGreaterThan(0, $ironProduction);
        $this->assertGreaterThan(0, $cropProduction);
    }

    public function test_get_resource_building_levels_with_different_types()
    {
        $reflection = new \ReflectionClass($this->gameTickService);
        $method = $reflection->getMethod('getResourceBuildingLevels');
        $method->setAccessible(true);

        $village = new Village();
        $village->id = 1;

        $woodLevels = $method->invoke($this->gameTickService, $village, 'wood');
        $clayLevels = $method->invoke($this->gameTickService, $village, 'clay');
        $ironLevels = $method->invoke($this->gameTickService, $village, 'iron');
        $cropLevels = $method->invoke($this->gameTickService, $village, 'crop');

        $this->assertIsArray($woodLevels);
        $this->assertIsArray($clayLevels);
        $this->assertIsArray($ironLevels);
        $this->assertIsArray($cropLevels);
    }
}
