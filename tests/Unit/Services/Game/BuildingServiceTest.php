<?php

namespace Tests\Unit\Services\Game;

use App\Models\Game\Building;
use App\Models\Game\BuildingType;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\Services\Game\BuildingService;
use App\Services\Game\ResourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use SmartCache\Facades\SmartCache;
use Tests\TestCase;

class BuildingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BuildingService $buildingService;

    private ResourceService $resourceService;

    private Village $village;

    private BuildingType $buildingType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resourceService = Mockery::mock(ResourceService::class);
        $this->buildingService = new BuildingService($this->resourceService);

        $world = World::factory()->create();
        $this->village = Village::factory()->create(['world_id' => $world->id]);
        $this->buildingType = BuildingType::factory()->create([
            'key' => 'barracks',
            'name' => 'Barracks',
            'costs' => ['wood' => 100, 'clay' => 50],
            'construction_time' => 60,
            'max_level' => 10,
            'is_active' => true,
            'requirements' => [],
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_can_check_requirements_with_empty_requirements()
    {
        $result = $this->buildingService->meetsRequirements($this->village, []);
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_check_building_level_requirements()
    {
        Building::factory()->create([
            'village_id' => $this->village->id,
            'building_type' => 'main_building',
            'level' => 5,
        ]);

        $requirements = [
            'building_level' => [
                'main_building' => 3,
            ],
        ];

        $result = $this->buildingService->meetsRequirements($this->village, $requirements);
        $this->assertTrue($result);

        $requirements = [
            'building_level' => [
                'main_building' => 10,
            ],
        ];

        $result = $this->buildingService->meetsRequirements($this->village, $requirements);
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_can_check_village_level_requirements()
    {
        $this->village->update(['level' => 5]);

        $requirements = ['village_level' => 3];
        $result = $this->buildingService->meetsRequirements($this->village, $requirements);
        $this->assertTrue($result);

        $requirements = ['village_level' => 10];
        $result = $this->buildingService->meetsRequirements($this->village, $requirements);
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_can_check_population_requirements()
    {
        $this->village->update(['population' => 500]);

        $requirements = ['population' => 300];
        $result = $this->buildingService->meetsRequirements($this->village, $requirements);
        $this->assertTrue($result);

        $requirements = ['population' => 1000];
        $result = $this->buildingService->meetsRequirements($this->village, $requirements);
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_can_calculate_building_costs()
    {
        $costs = $this->buildingService->getBuildingCosts($this->buildingType, 0);
        $this->assertEquals(['wood' => 100, 'clay' => 50], $costs);

        $costs = $this->buildingService->getBuildingCosts($this->buildingType, 1);
        $this->assertEquals(['wood' => 150, 'clay' => 75], $costs);

        $costs = $this->buildingService->getBuildingCosts($this->buildingType, 2);
        $this->assertEquals(['wood' => 225, 'clay' => 112.5], $costs);
    }

    /**
     * @test
     */
    public function it_can_calculate_construction_time()
    {
        $time = $this->buildingService->getConstructionTime($this->buildingType, 0);
        $this->assertEquals(60, $time);

        $time = $this->buildingService->getConstructionTime($this->buildingType, 1);
        $this->assertEquals(72, $time);

        $time = $this->buildingService->getConstructionTime($this->buildingType, 2);
        $this->assertEquals(86, $time);
    }

    /**
     * @test
     */
    public function it_can_start_construction_for_new_building()
    {
        $this
            ->resourceService
            ->shouldReceive('hasEnoughResources')
            ->once()
            ->with($this->village, ['wood' => 100, 'clay' => 50])
            ->andReturn(true);

        $this
            ->resourceService
            ->shouldReceive('deductResources')
            ->once()
            ->with($this->village, ['wood' => 100, 'clay' => 50]);

        SmartCache::shouldReceive('forget')->times(3);

        $result = $this->buildingService->startConstruction($this->village, $this->buildingType);

        $this->assertTrue($result['success']);
        $this->assertEquals('Construction started successfully', $result['message']);
        $this->assertEquals(60, $result['construction_time']);
        $this->assertInstanceOf(\Carbon\Carbon::class, $result['completion_time']);

        $this->assertDatabaseHas('buildings', [
            'village_id' => $this->village->id,
            'building_type' => 'barracks',
            'level' => 1,
        ]);
    }

    /**
     * @test
     */
    public function it_can_start_construction_for_existing_building()
    {
        $building = Building::factory()->create([
            'village_id' => $this->village->id,
            'building_type' => 'barracks',
            'level' => 1,
        ]);

        $this
            ->resourceService
            ->shouldReceive('hasEnoughResources')
            ->once()
            ->with($this->village, ['wood' => 150, 'clay' => 75])
            ->andReturn(true);

        $this
            ->resourceService
            ->shouldReceive('deductResources')
            ->once()
            ->with($this->village, ['wood' => 150, 'clay' => 75]);

        SmartCache::shouldReceive('forget')->times(3);

        $result = $this->buildingService->startConstruction($this->village, $this->buildingType);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $building->fresh()->level);
    }

    /**
     * @test
     */
    public function it_fails_construction_when_requirements_not_met()
    {
        $this->buildingType->update(['requirements' => ['village_level' => 10]]);
        $this->village->update(['level' => 5]);

        $result = $this->buildingService->startConstruction($this->village, $this->buildingType);

        $this->assertFalse($result['success']);
        $this->assertEquals('Building requirements not met', $result['message']);
        $this->assertArrayHasKey('requirements', $result);
    }

    /**
     * @test
     */
    public function it_fails_construction_when_insufficient_resources()
    {
        $this
            ->resourceService
            ->shouldReceive('hasEnoughResources')
            ->once()
            ->with($this->village, ['wood' => 100, 'clay' => 50])
            ->andReturn(false);

        $this
            ->resourceService
            ->shouldReceive('getVillageResources')
            ->once()
            ->with($this->village)
            ->andReturn(['wood' => 50, 'clay' => 25]);

        $result = $this->buildingService->startConstruction($this->village, $this->buildingType);

        $this->assertFalse($result['success']);
        $this->assertEquals('Insufficient resources for construction', $result['message']);
        $this->assertArrayHasKey('required', $result);
        $this->assertArrayHasKey('available', $result);
    }

    /**
     * @test
     */
    public function it_can_complete_construction()
    {
        $building = Building::factory()->create([
            'village_id' => $this->village->id,
            'building_type' => 'barracks',
            'level' => 1,
            'construction_started_at' => now()->subMinutes(5),
            'construction_completed_at' => now()->subMinutes(1),
        ]);

        SmartCache::shouldReceive('forget')->times(3);

        $result = $this->buildingService->completeConstruction($this->village);

        $this->assertTrue($result['success']);
        $this->assertEquals('Construction completed successfully', $result['message']);
        $this->assertCount(1, $result['results']);
        $this->assertEquals('barracks', $result['results'][0]['building_type']);
        $this->assertEquals(1, $result['results'][0]['level']);

        $building->refresh();
        $this->assertNull($building->construction_started_at);
        $this->assertNull($building->construction_completed_at);
    }

    /**
     * @test
     */
    public function it_fails_completion_when_no_completed_construction()
    {
        $result = $this->buildingService->completeConstruction($this->village);

        $this->assertFalse($result['success']);
        $this->assertEquals('No completed construction found', $result['message']);
    }

    /**
     * @test
     */
    public function it_can_demolish_building()
    {
        $building = Building::factory()->create([
            'village_id' => $this->village->id,
            'building_type' => 'barracks',
            'level' => 2,
        ]);

        $this
            ->resourceService
            ->shouldReceive('addResources')
            ->once()
            ->with($this->village, ['wood' => 75, 'clay' => 37]);

        SmartCache::shouldReceive('forget')->times(3);

        $result = $this->buildingService->demolishBuilding($this->village, $building);

        $this->assertTrue($result['success']);
        $this->assertEquals('Building demolished successfully', $result['message']);
        $this->assertEquals(['wood' => 75, 'clay' => 37], $result['refund']);
        $this->assertEquals(1, $building->fresh()->level);
    }

    /**
     * @test
     */
    public function it_fails_demolish_when_building_not_owned()
    {
        $otherVillage = Village::factory()->create();
        $building = Building::factory()->create([
            'village_id' => $otherVillage->id,
            'building_type' => 'barracks',
            'level' => 2,
        ]);

        $result = $this->buildingService->demolishBuilding($this->village, $building);

        $this->assertFalse($result['success']);
        $this->assertEquals('Building does not belong to this village', $result['message']);
    }

    /**
     * @test
     */
    public function it_fails_demolish_when_building_at_minimum_level()
    {
        $building = Building::factory()->create([
            'village_id' => $this->village->id,
            'building_type' => 'barracks',
            'level' => 0,
        ]);

        $result = $this->buildingService->demolishBuilding($this->village, $building);

        $this->assertFalse($result['success']);
        $this->assertEquals('Building is already at minimum level', $result['message']);
    }

    /**
     * @test
     */
    public function it_can_get_available_buildings()
    {
        $this->buildingType->update(['is_active' => true]);

        SmartCache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $available = $this->buildingService->getAvailableBuildings($this->village);

        $this->assertCount(1, $available);
        $this->assertEquals('barracks', $available[0]['key']);
        $this->assertEquals('Barracks', $available[0]['name']);
        $this->assertEquals(0, $available[0]['current_level']);
        $this->assertEquals(10, $available[0]['max_level']);
        $this->assertEquals(['wood' => 100, 'clay' => 50], $available[0]['costs']);
        $this->assertEquals(60, $available[0]['construction_time']);
    }

    /**
     * @test
     */
    public function it_can_get_village_buildings()
    {
        $building = Building::factory()->create([
            'village_id' => $this->village->id,
            'building_type' => 'barracks',
            'level' => 2,
            'construction_started_at' => now(),
            'construction_completed_at' => now()->addMinutes(5),
        ]);

        SmartCache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $buildings = $this->buildingService->getVillageBuildings($this->village);

        $this->assertCount(1, $buildings);
        $this->assertEquals('barracks', $buildings[0]['building_type']);
        $this->assertEquals(2, $buildings[0]['level']);
        $this->assertTrue($buildings[0]['is_under_construction']);
        $this->assertIsInt($buildings[0]['remaining_time']);
    }

    /**
     * @test
     */
    public function it_can_get_building_statistics()
    {
        Building::factory()->create([
            'village_id' => $this->village->id,
            'building_type' => 'barracks',
            'level' => 2,
        ]);

        Building::factory()->create([
            'village_id' => $this->village->id,
            'building_type' => 'warehouse',
            'level' => 3,
            'construction_started_at' => now(),
        ]);

        SmartCache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $stats = $this->buildingService->getBuildingStatistics($this->village);

        $this->assertEquals(2, $stats['total_buildings']);
        $this->assertEquals(5, $stats['total_levels']);
        $this->assertEquals(2.5, $stats['average_level']);
        $this->assertEquals(1, $stats['under_construction']);
        $this->assertCount(2, $stats['building_types']);
    }

    /**
     * @test
     */
    public function it_handles_database_transaction_rollback()
    {
        $this
            ->resourceService
            ->shouldReceive('hasEnoughResources')
            ->once()
            ->andReturn(true);

        $this
            ->resourceService
            ->shouldReceive('deductResources')
            ->once()
            ->andThrow(new \Exception('Database error'));

        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->buildingService->startConstruction($this->village, $this->buildingType);
    }

    /**
     * @test
     */
    public function it_handles_missing_building_type_in_demolish()
    {
        $building = Building::factory()->create([
            'village_id' => $this->village->id,
            'building_type' => 'nonexistent',
            'level' => 2,
        ]);

        SmartCache::shouldReceive('forget')->times(3);

        $result = $this->buildingService->demolishBuilding($this->village, $building);

        $this->assertTrue($result['success']);
        $this->assertEquals([], $result['refund']);
    }

    /**
     * @test
     */
    public function it_filters_inactive_buildings_from_available()
    {
        $this->buildingType->update(['is_active' => false]);

        SmartCache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $available = $this->buildingService->getAvailableBuildings($this->village);

        $this->assertCount(0, $available);
    }

    /**
     * @test
     */
    public function it_filters_buildings_at_max_level_from_available()
    {
        Building::factory()->create([
            'village_id' => $this->village->id,
            'building_type' => 'barracks',
            'level' => 10,  // max level
        ]);

        SmartCache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $available = $this->buildingService->getAvailableBuildings($this->village);

        $this->assertCount(0, $available);
    }

    /**
     * @test
     */
    public function it_handles_null_building_costs()
    {
        $this->buildingType->update(['costs' => null]);

        $costs = $this->buildingService->getBuildingCosts($this->buildingType, 0);
        $this->assertEquals([], $costs);
    }

    /**
     * @test
     */
    public function it_handles_null_construction_time()
    {
        $this->buildingType->update(['construction_time' => null]);

        $time = $this->buildingService->getConstructionTime($this->buildingType, 0);
        $this->assertEquals(60, $time);  // default base time
    }

    /**
     * @test
     */
    public function it_handles_zero_level_buildings_in_statistics()
    {
        // No buildings in village
        SmartCache::shouldReceive('remember')
            ->once()
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $stats = $this->buildingService->getBuildingStatistics($this->village);

        $this->assertEquals(0, $stats['total_buildings']);
        $this->assertEquals(0, $stats['total_levels']);
        $this->assertEquals(0, $stats['average_level']);
        $this->assertEquals(0, $stats['under_construction']);
        $this->assertEquals([], $stats['building_types']);
    }

    /**
     * @test
     */
    public function it_clears_cache_after_operations()
    {
        SmartCache::shouldReceive('forget')
            ->with("available_buildings:{$this->village->id}")
            ->once();
        SmartCache::shouldReceive('forget')
            ->with("village_buildings:{$this->village->id}")
            ->once();
        SmartCache::shouldReceive('forget')
            ->with("building_stats:{$this->village->id}")
            ->once();

        $reflection = new \ReflectionClass($this->buildingService);
        $method = $reflection->getMethod('clearBuildingCache');
        $method->setAccessible(true);
        $method->invoke($this->buildingService, $this->village);
    }

    /**
     * @test
     */
    public function it_can_check_if_building_can_be_built()
    {
        $reflection = new \ReflectionClass($this->buildingService);
        $method = $reflection->getMethod('canBuild');
        $method->setAccessible(true);

        // Active building with no requirements
        $this->assertTrue($method->invoke($this->buildingService, $this->village, $this->buildingType));

        // Inactive building
        $this->buildingType->update(['is_active' => false]);
        $this->assertFalse($method->invoke($this->buildingService, $this->village, $this->buildingType));

        // Building at max level
        $this->buildingType->update(['is_active' => true]);
        Building::factory()->create([
            'village_id' => $this->village->id,
            'building_type' => 'barracks',
            'level' => 10,
        ]);
        $this->assertFalse($method->invoke($this->buildingService, $this->village, $this->buildingType));
    }
}
