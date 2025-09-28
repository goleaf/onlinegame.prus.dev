<?php

namespace Tests\Unit\Services;

use App\Models\Game\Building;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Services\BuildingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BuildingServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_construct_building()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $data = [
            'village_id' => $village->id,
            'type' => 'woodcutter',
            'level' => 1,
        ];

        $service = new BuildingService();
        $result = $service->constructBuilding($player, $village, $data);

        $this->assertInstanceOf(Building::class, $result);
        $this->assertEquals($data['village_id'], $result->village_id);
        $this->assertEquals($data['type'], $result->type);
        $this->assertEquals($data['level'], $result->level);
    }

    /**
     * @test
     */
    public function it_can_upgrade_building()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $building = Building::factory()->create([
            'village_id' => $village->id,
            'level' => 1,
        ]);

        $service = new BuildingService();
        $result = $service->upgradeBuilding($player, $building);

        $this->assertTrue($result);
        $this->assertEquals(2, $building->level);
    }

    /**
     * @test
     */
    public function it_can_downgrade_building()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $building = Building::factory()->create([
            'village_id' => $village->id,
            'level' => 5,
        ]);

        $service = new BuildingService();
        $result = $service->downgradeBuilding($player, $building);

        $this->assertTrue($result);
        $this->assertEquals(4, $building->level);
    }

    /**
     * @test
     */
    public function it_can_destroy_building()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $building = Building::factory()->create([
            'village_id' => $village->id,
        ]);

        $service = new BuildingService();
        $result = $service->destroyBuilding($player, $building);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_get_village_buildings()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id]),
            Building::factory()->create(['village_id' => $village->id]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getVillageBuildings($village);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_type()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'type' => 'woodcutter']),
            Building::factory()->create(['village_id' => $village->id, 'type' => 'clay_pit']),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByType($village, 'woodcutter');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_level()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'level' => 5]),
            Building::factory()->create(['village_id' => $village->id, 'level' => 10]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByLevel($village, 5);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_status()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'status' => 'active']),
            Building::factory()->create(['village_id' => $village->id, 'status' => 'inactive']),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByStatus($village, 'active');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_construction_date()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'constructed_at' => now()]),
            Building::factory()->create(['village_id' => $village->id, 'constructed_at' => now()->subDays(1)]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByConstructionDate($village, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_upgrade_date()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'upgraded_at' => now()]),
            Building::factory()->create(['village_id' => $village->id, 'upgraded_at' => now()->subDays(1)]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByUpgradeDate($village, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_destruction_date()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'destroyed_at' => now()]),
            Building::factory()->create(['village_id' => $village->id, 'destroyed_at' => now()->subDays(1)]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByDestructionDate($village, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_construction_cost()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'construction_cost' => 1000]),
            Building::factory()->create(['village_id' => $village->id, 'construction_cost' => 2000]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByConstructionCost($village, 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_upgrade_cost()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'upgrade_cost' => 1000]),
            Building::factory()->create(['village_id' => $village->id, 'upgrade_cost' => 2000]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByUpgradeCost($village, 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_destruction_cost()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'destruction_cost' => 1000]),
            Building::factory()->create(['village_id' => $village->id, 'destruction_cost' => 2000]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByDestructionCost($village, 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_construction_time()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'construction_time' => 3600]),
            Building::factory()->create(['village_id' => $village->id, 'construction_time' => 7200]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByConstructionTime($village, 3600);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_upgrade_time()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'upgrade_time' => 3600]),
            Building::factory()->create(['village_id' => $village->id, 'upgrade_time' => 7200]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByUpgradeTime($village, 3600);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_destruction_time()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'destruction_time' => 3600]),
            Building::factory()->create(['village_id' => $village->id, 'destruction_time' => 7200]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByDestructionTime($village, 3600);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_construction_resources()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'construction_resources' => ['wood' => 1000]]),
            Building::factory()->create(['village_id' => $village->id, 'construction_resources' => ['clay' => 500]]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByConstructionResources($village, 'wood', 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_upgrade_resources()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'upgrade_resources' => ['wood' => 1000]]),
            Building::factory()->create(['village_id' => $village->id, 'upgrade_resources' => ['clay' => 500]]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByUpgradeResources($village, 'wood', 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_destruction_resources()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'destruction_resources' => ['wood' => 1000]]),
            Building::factory()->create(['village_id' => $village->id, 'destruction_resources' => ['clay' => 500]]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByDestructionResources($village, 'wood', 1000);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_construction_resources_range()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'construction_resources' => ['wood' => 1000]]),
            Building::factory()->create(['village_id' => $village->id, 'construction_resources' => ['clay' => 500]]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByConstructionResourcesRange($village, 'wood', 500, 1500);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_upgrade_resources_range()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'upgrade_resources' => ['wood' => 1000]]),
            Building::factory()->create(['village_id' => $village->id, 'upgrade_resources' => ['clay' => 500]]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByUpgradeResourcesRange($village, 'wood', 500, 1500);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_destruction_resources_range()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'destruction_resources' => ['wood' => 1000]]),
            Building::factory()->create(['village_id' => $village->id, 'destruction_resources' => ['clay' => 500]]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByDestructionResourcesRange($village, 'wood', 500, 1500);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_combined_filters()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'type' => 'woodcutter', 'level' => 5]),
            Building::factory()->create(['village_id' => $village->id, 'type' => 'clay_pit', 'level' => 10]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByCombinedFilters($village, [
            'type' => 'woodcutter',
            'level' => 5,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_search()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'name' => 'Test Building']),
            Building::factory()->create(['village_id' => $village->id, 'name' => 'Another Building']),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingBySearch($village, 'Test');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_sort()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id, 'level' => 5]),
            Building::factory()->create(['village_id' => $village->id, 'level' => 10]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingBySort($village, 'level', 'desc');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_by_pagination()
    {
        $village = Village::factory()->create();
        $buildings = collect([
            Building::factory()->create(['village_id' => $village->id]),
            Building::factory()->create(['village_id' => $village->id]),
        ]);

        $village->shouldReceive('buildings')->andReturn($buildings);

        $service = new BuildingService();
        $result = $service->getBuildingByPagination($village, 1, 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_statistics()
    {
        $service = new BuildingService();
        $result = $service->getBuildingStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_buildings', $result);
        $this->assertArrayHasKey('by_type', $result);
        $this->assertArrayHasKey('by_level', $result);
    }

    /**
     * @test
     */
    public function it_can_get_building_leaderboard()
    {
        $service = new BuildingService();
        $result = $service->getBuildingLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
