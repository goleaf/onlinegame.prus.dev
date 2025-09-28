<?php

namespace Tests\Unit\Services\Game;

use App\Models\Game\Village;
use App\Models\Game\World;
use App\Services\Game\MapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapServiceTest extends TestCase
{
    use RefreshDatabase;

    private MapService $mapService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapService = new MapService();
    }

    /**
     * @test
     */
    public function it_can_get_villages_in_radius()
    {
        $world = World::factory()->create();

        // Create villages at different distances
        $centerVillage = Village::factory()->create([
            'world_id' => $world->id,
            'x_coordinate' => 500,
            'y_coordinate' => 500,
        ]);

        Village::factory()->create([
            'world_id' => $world->id,
            'x_coordinate' => 510,
            'y_coordinate' => 500,
        ]);

        Village::factory()->create([
            'world_id' => $world->id,
            'x_coordinate' => 600,
            'y_coordinate' => 600,
        ]);

        $villages = $this->mapService->getVillagesInRadius(
            $centerVillage->x_coordinate,
            $centerVillage->y_coordinate,
            50
        );

        $this->assertCount(2, $villages);  // Center village + 1 nearby
    }

    /**
     * @test
     */
    public function it_can_calculate_distance_between_villages()
    {
        $village1 = Village::factory()->create([
            'x_coordinate' => 0,
            'y_coordinate' => 0,
        ]);

        $village2 = Village::factory()->create([
            'x_coordinate' => 100,
            'y_coordinate' => 100,
        ]);

        $distance = $this->mapService->calculateDistance(
            $village1->x_coordinate,
            $village1->y_coordinate,
            $village2->x_coordinate,
            $village2->y_coordinate
        );

        $this->assertIsFloat($distance);
        $this->assertGreaterThan(0, $distance);
    }

    /**
     * @test
     */
    public function it_can_find_nearest_villages()
    {
        $world = World::factory()->create();

        $centerVillage = Village::factory()->create([
            'world_id' => $world->id,
            'x_coordinate' => 500,
            'y_coordinate' => 500,
        ]);

        Village::factory()->count(5)->create([
            'world_id' => $world->id,
            'x_coordinate' => function () {
                return rand(400, 600);
            },
            'y_coordinate' => function () {
                return rand(400, 600);
            },
        ]);

        $nearestVillages = $this->mapService->findNearestVillages(
            $centerVillage->x_coordinate,
            $centerVillage->y_coordinate,
            3
        );

        $this->assertCount(3, $nearestVillages);
    }

    /**
     * @test
     */
    public function it_can_get_map_sector()
    {
        $world = World::factory()->create();

        Village::factory()->count(10)->create([
            'world_id' => $world->id,
            'x_coordinate' => function () {
                return rand(0, 100);
            },
            'y_coordinate' => function () {
                return rand(0, 100);
            },
        ]);

        $sector = $this->mapService->getMapSector(0, 0, 100, 100);

        $this->assertIsArray($sector);
        $this->assertArrayHasKey('villages', $sector);
        $this->assertCount(10, $sector['villages']);
    }

    /**
     * @test
     */
    public function it_can_get_village_by_coordinates()
    {
        $world = World::factory()->create();

        $village = Village::factory()->create([
            'world_id' => $world->id,
            'x_coordinate' => 250,
            'y_coordinate' => 250,
        ]);

        $foundVillage = $this->mapService->getVillageByCoordinates(250, 250);

        $this->assertNotNull($foundVillage);
        $this->assertEquals($village->id, $foundVillage->id);
    }

    /**
     * @test
     */
    public function it_returns_null_for_nonexistent_coordinates()
    {
        $foundVillage = $this->mapService->getVillageByCoordinates(999, 999);

        $this->assertNull($foundVillage);
    }

    /**
     * @test
     */
    public function it_can_get_map_statistics()
    {
        $world = World::factory()->create();

        Village::factory()->count(5)->create([
            'world_id' => $world->id,
            'x_coordinate' => function () {
                return rand(0, 1000);
            },
            'y_coordinate' => function () {
                return rand(0, 1000);
            },
        ]);

        $stats = $this->mapService->getMapStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_villages', $stats);
        $this->assertArrayHasKey('occupied_villages', $stats);
        $this->assertArrayHasKey('empty_villages', $stats);
        $this->assertEquals(5, $stats['total_villages']);
    }

    /**
     * @test
     */
    public function it_can_find_empty_villages_in_area()
    {
        $world = World::factory()->create();

        Village::factory()->create([
            'world_id' => $world->id,
            'x_coordinate' => 500,
            'y_coordinate' => 500,
            'player_id' => null,  // Empty village
        ]);

        Village::factory()->create([
            'world_id' => $world->id,
            'x_coordinate' => 510,
            'y_coordinate' => 510,
            'player_id' => 1,  // Occupied village
        ]);

        $emptyVillages = $this->mapService->findEmptyVillagesInArea(500, 500, 50);

        $this->assertIsArray($emptyVillages);
        $this->assertCount(1, $emptyVillages);
    }

    /**
     * @test
     */
    public function it_can_get_village_density_map()
    {
        $world = World::factory()->create();

        // Create villages in a specific area
        Village::factory()->count(10)->create([
            'world_id' => $world->id,
            'x_coordinate' => function () {
                return rand(100, 200);
            },
            'y_coordinate' => function () {
                return rand(100, 200);
            },
        ]);

        $densityMap = $this->mapService->getVillageDensityMap(100, 100, 200, 200, 10);

        $this->assertIsArray($densityMap);
        $this->assertArrayHasKey('grid', $densityMap);
        $this->assertArrayHasKey('max_density', $densityMap);
        $this->assertArrayHasKey('min_density', $densityMap);
    }

    /**
     * @test
     */
    public function it_can_validate_coordinates()
    {
        $this->assertTrue($this->mapService->isValidCoordinates(0, 0));
        $this->assertTrue($this->mapService->isValidCoordinates(1000, 1000));
        $this->assertFalse($this->mapService->isValidCoordinates(-1, 0));
        $this->assertFalse($this->mapService->isValidCoordinates(0, -1));
        $this->assertFalse($this->mapService->isValidCoordinates(1001, 0));
        $this->assertFalse($this->mapService->isValidCoordinates(0, 1001));
    }

    /**
     * @test
     */
    public function it_can_get_world_boundaries()
    {
        $world = World::factory()->create([
            'size_x' => 1000,
            'size_y' => 1000,
        ]);

        $boundaries = $this->mapService->getWorldBoundaries($world->id);

        $this->assertIsArray($boundaries);
        $this->assertArrayHasKey('min_x', $boundaries);
        $this->assertArrayHasKey('max_x', $boundaries);
        $this->assertArrayHasKey('min_y', $boundaries);
        $this->assertArrayHasKey('max_y', $boundaries);
        $this->assertEquals(0, $boundaries['min_x']);
        $this->assertEquals(1000, $boundaries['max_x']);
        $this->assertEquals(0, $boundaries['min_y']);
        $this->assertEquals(1000, $boundaries['max_y']);
    }

    /**
     * @test
     */
    public function it_can_find_villages_by_player_in_area()
    {
        $world = World::factory()->create();
        $playerId = 1;

        Village::factory()->create([
            'world_id' => $world->id,
            'player_id' => $playerId,
            'x_coordinate' => 500,
            'y_coordinate' => 500,
        ]);

        Village::factory()->create([
            'world_id' => $world->id,
            'player_id' => 2,
            'x_coordinate' => 510,
            'y_coordinate' => 510,
        ]);

        $playerVillages = $this->mapService->findVillagesByPlayerInArea(
            $playerId,
            490,
            490,
            30
        );

        $this->assertIsArray($playerVillages);
        $this->assertCount(1, $playerVillages);
        $this->assertEquals($playerId, $playerVillages[0]->player_id);
    }

    /**
     * @test
     */
    public function it_can_calculate_travel_time()
    {
        $travelTime = $this->mapService->calculateTravelTime(
            0,
            0,
            100,
            100,
            50  // speed
        );

        $this->assertIsInt($travelTime);
        $this->assertGreaterThan(0, $travelTime);
    }
}
