<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Battle;
use App\Models\Game\Building;
use App\Models\Game\BuildingQueue;
use App\Models\Game\Movement;
use App\Models\Game\Player;
use App\Models\Game\Report;
use App\Models\Game\Resource;
use App\Models\Game\ResourceProductionLog;
use App\Models\Game\TrainingQueue;
use App\Models\Game\Troop;
use App\Models\Game\Village;
use App\Models\Game\World;
use App\ValueObjects\Coordinates;
use App\ValueObjects\VillageResources;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VillageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_a_village()
    {
        $player = Player::factory()->create();
        $world = World::factory()->create();
        $village = Village::factory()->create([
            'player_id' => $player->id,
            'world_id' => $world->id,
            'name' => 'Test Village',
            'x_coordinate' => 100,
            'y_coordinate' => 200,
            'population' => 1000,
        ]);

        $this->assertInstanceOf(Village::class, $village);
        $this->assertEquals('Test Village', $village->name);
        $this->assertEquals(100, $village->x_coordinate);
        $this->assertEquals(200, $village->y_coordinate);
        $this->assertEquals(1000, $village->population);
        $this->assertEquals($player->id, $village->player_id);
        $this->assertEquals($world->id, $village->world_id);
    }

    /**
     * @test
     */
    public function it_has_fillable_attributes()
    {
        $village = new Village();
        $fillable = $village->getFillable();

        $this->assertContains('player_id', $fillable);
        $this->assertContains('world_id', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('x_coordinate', $fillable);
        $this->assertContains('y_coordinate', $fillable);
        $this->assertContains('latitude', $fillable);
        $this->assertContains('longitude', $fillable);
        $this->assertContains('geohash', $fillable);
        $this->assertContains('elevation', $fillable);
        $this->assertContains('geographic_metadata', $fillable);
        $this->assertContains('population', $fillable);
        $this->assertContains('is_capital', $fillable);
        $this->assertContains('is_active', $fillable);
        $this->assertContains('reference_number', $fillable);
    }

    /**
     * @test
     */
    public function it_casts_attributes_correctly()
    {
        $village = Village::factory()->create();
        $casts = $village->getCasts();

        $this->assertArrayHasKey('is_capital', $casts);
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertArrayHasKey('latitude', $casts);
        $this->assertArrayHasKey('longitude', $casts);
        $this->assertArrayHasKey('elevation', $casts);
        $this->assertArrayHasKey('geographic_metadata', $casts);
    }

    /**
     * @test
     */
    public function it_has_player_relationship()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $village->player());
        $this->assertEquals($player->id, $village->player->id);
    }

    /**
     * @test
     */
    public function it_has_world_relationship()
    {
        $world = World::factory()->create();
        $village = Village::factory()->create(['world_id' => $world->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $village->world());
        $this->assertEquals($world->id, $village->world->id);
    }

    /**
     * @test
     */
    public function it_has_buildings_relationship()
    {
        $village = Village::factory()->create();
        $building1 = Building::factory()->create(['village_id' => $village->id]);
        $building2 = Building::factory()->create(['village_id' => $village->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $village->buildings());
        $this->assertTrue($village->buildings->contains($building1));
        $this->assertTrue($village->buildings->contains($building2));
    }

    /**
     * @test
     */
    public function it_has_resources_relationship()
    {
        $village = Village::factory()->create();
        $resource1 = Resource::factory()->create(['village_id' => $village->id]);
        $resource2 = Resource::factory()->create(['village_id' => $village->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $village->resources());
        $this->assertTrue($village->resources->contains($resource1));
        $this->assertTrue($village->resources->contains($resource2));
    }

    /**
     * @test
     */
    public function it_has_resource_production_logs_relationship()
    {
        $village = Village::factory()->create();
        $log1 = ResourceProductionLog::factory()->create(['village_id' => $village->id]);
        $log2 = ResourceProductionLog::factory()->create(['village_id' => $village->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $village->resourceProductionLogs());
        $this->assertTrue($village->resourceProductionLogs->contains($log1));
        $this->assertTrue($village->resourceProductionLogs->contains($log2));
    }

    /**
     * @test
     */
    public function it_has_training_queues_relationship()
    {
        $village = Village::factory()->create();
        $queue1 = TrainingQueue::factory()->create(['village_id' => $village->id]);
        $queue2 = TrainingQueue::factory()->create(['village_id' => $village->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $village->trainingQueues());
        $this->assertTrue($village->trainingQueues->contains($queue1));
        $this->assertTrue($village->trainingQueues->contains($queue2));
    }

    /**
     * @test
     */
    public function it_has_building_queues_relationship()
    {
        $village = Village::factory()->create();
        $queue1 = BuildingQueue::factory()->create(['village_id' => $village->id]);
        $queue2 = BuildingQueue::factory()->create(['village_id' => $village->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $village->buildingQueues());
        $this->assertTrue($village->buildingQueues->contains($queue1));
        $this->assertTrue($village->buildingQueues->contains($queue2));
    }

    /**
     * @test
     */
    public function it_has_troops_relationship()
    {
        $village = Village::factory()->create();
        $troop1 = Troop::factory()->create(['village_id' => $village->id]);
        $troop2 = Troop::factory()->create(['village_id' => $village->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $village->troops());
        $this->assertTrue($village->troops->contains($troop1));
        $this->assertTrue($village->troops->contains($troop2));
    }

    /**
     * @test
     */
    public function it_has_movements_from_relationship()
    {
        $village = Village::factory()->create();
        $movement1 = Movement::factory()->create(['from_village_id' => $village->id]);
        $movement2 = Movement::factory()->create(['from_village_id' => $village->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $village->movementsFrom());
        $this->assertTrue($village->movementsFrom->contains($movement1));
        $this->assertTrue($village->movementsFrom->contains($movement2));
    }

    /**
     * @test
     */
    public function it_has_movements_to_relationship()
    {
        $village = Village::factory()->create();
        $movement1 = Movement::factory()->create(['to_village_id' => $village->id]);
        $movement2 = Movement::factory()->create(['to_village_id' => $village->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $village->movementsTo());
        $this->assertTrue($village->movementsTo->contains($movement1));
        $this->assertTrue($village->movementsTo->contains($movement2));
    }

    /**
     * @test
     */
    public function it_has_battles_relationship()
    {
        $village = Village::factory()->create();
        $battle1 = Battle::factory()->create(['village_id' => $village->id]);
        $battle2 = Battle::factory()->create(['village_id' => $village->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $village->battles());
        $this->assertTrue($village->battles->contains($battle1));
        $this->assertTrue($village->battles->contains($battle2));
    }

    /**
     * @test
     */
    public function it_has_reports_relationship()
    {
        $village = Village::factory()->create();
        $report1 = Report::factory()->create(['village_id' => $village->id]);
        $report2 = Report::factory()->create(['village_id' => $village->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $village->reports());
        $this->assertTrue($village->reports->contains($report1));
        $this->assertTrue($village->reports->contains($report2));
    }

    /**
     * @test
     */
    public function it_has_coordinates_attribute()
    {
        $village = Village::factory()->create([
            'x_coordinate' => 100,
            'y_coordinate' => 200,
        ]);

        $this->assertEquals('(100|200)', $village->coordinates);
    }

    /**
     * @test
     */
    public function it_has_coordinates_value_object()
    {
        $village = Village::factory()->create([
            'x_coordinate' => 100,
            'y_coordinate' => 200,
            'latitude' => 40.7128,
            'longitude' => -74.006,
            'elevation' => 10.5,
            'geohash' => 'dr5regy3',
        ]);

        $coordinates = $village->coordinates;

        $this->assertInstanceOf(Coordinates::class, $coordinates);
        $this->assertEquals(100, $coordinates->x);
        $this->assertEquals(200, $coordinates->y);
        $this->assertEquals(40.7128, $coordinates->latitude);
        $this->assertEquals(-74.006, $coordinates->longitude);
        $this->assertEquals(10.5, $coordinates->elevation);
        $this->assertEquals('dr5regy3', $coordinates->geohash);
    }

    /**
     * @test
     */
    public function it_can_set_coordinates_value_object()
    {
        $village = Village::factory()->create();
        $newCoordinates = new Coordinates(
            x: 150,
            y: 250,
            latitude: 41.8781,
            longitude: -87.6298,
            elevation: 15.0,
            geohash: 'dp3wjth9'
        );

        $village->coordinates = $newCoordinates;
        $village->save();

        $this->assertEquals(150, $village->x_coordinate);
        $this->assertEquals(250, $village->y_coordinate);
        $this->assertEquals(41.8781, $village->latitude);
        $this->assertEquals(-87.6298, $village->longitude);
        $this->assertEquals(15.0, $village->elevation);
        $this->assertEquals('dp3wjth9', $village->geohash);
    }

    /**
     * @test
     */
    public function it_has_village_resources_value_object()
    {
        $village = Village::factory()->create();
        $resource = Resource::factory()->create([
            'village_id' => $village->id,
            'wood' => 1000,
            'clay' => 2000,
            'iron' => 1500,
            'crop' => 800,
        ]);

        $villageResources = $village->villageResources;

        $this->assertInstanceOf(VillageResources::class, $villageResources);
        $this->assertEquals(1000, $villageResources->wood);
        $this->assertEquals(2000, $villageResources->clay);
        $this->assertEquals(1500, $villageResources->iron);
        $this->assertEquals(800, $villageResources->crop);
    }

    /**
     * @test
     */
    public function it_returns_default_village_resources_when_no_resource_exists()
    {
        $village = Village::factory()->create();

        $villageResources = $village->villageResources;

        $this->assertInstanceOf(VillageResources::class, $villageResources);
        $this->assertEquals(0, $villageResources->wood);
        $this->assertEquals(0, $villageResources->clay);
        $this->assertEquals(0, $villageResources->iron);
        $this->assertEquals(0, $villageResources->crop);
    }

    /**
     * @test
     */
    public function it_can_scope_by_player()
    {
        $player1 = Player::factory()->create();
        $player2 = Player::factory()->create();
        $village1 = Village::factory()->create(['player_id' => $player1->id]);
        $village2 = Village::factory()->create(['player_id' => $player2->id]);

        $player1Villages = Village::byPlayer($player1->id)->get();

        $this->assertTrue($player1Villages->contains($village1));
        $this->assertFalse($player1Villages->contains($village2));
    }

    /**
     * @test
     */
    public function it_can_scope_by_world()
    {
        $world1 = World::factory()->create();
        $world2 = World::factory()->create();
        $village1 = Village::factory()->create(['world_id' => $world1->id]);
        $village2 = Village::factory()->create(['world_id' => $world2->id]);

        $world1Villages = Village::byWorld($world1->id)->get();

        $this->assertTrue($world1Villages->contains($village1));
        $this->assertFalse($world1Villages->contains($village2));
    }

    /**
     * @test
     */
    public function it_can_scope_active_villages()
    {
        $activeVillage = Village::factory()->create(['is_active' => true]);
        $inactiveVillage = Village::factory()->create(['is_active' => false]);

        $activeVillages = Village::active()->get();

        $this->assertTrue($activeVillages->contains($activeVillage));
        $this->assertFalse($activeVillages->contains($inactiveVillage));
    }

    /**
     * @test
     */
    public function it_can_scope_capital_villages()
    {
        $capitalVillage = Village::factory()->create(['is_capital' => true]);
        $regularVillage = Village::factory()->create(['is_capital' => false]);

        $capitalVillages = Village::capital()->get();

        $this->assertTrue($capitalVillages->contains($capitalVillage));
        $this->assertFalse($capitalVillages->contains($regularVillage));
    }

    /**
     * @test
     */
    public function it_can_scope_by_coordinates()
    {
        $village1 = Village::factory()->create(['x_coordinate' => 100, 'y_coordinate' => 200]);
        $village2 = Village::factory()->create(['x_coordinate' => 150, 'y_coordinate' => 250]);

        $exactMatch = Village::byCoordinates(100, 200)->get();
        $radiusMatch = Village::byCoordinates(100, 200, 100)->get();

        $this->assertTrue($exactMatch->contains($village1));
        $this->assertFalse($exactMatch->contains($village2));
        $this->assertTrue($radiusMatch->contains($village1));
        $this->assertTrue($radiusMatch->contains($village2));
    }

    /**
     * @test
     */
    public function it_can_scope_by_population()
    {
        $village1 = Village::factory()->create(['population' => 500]);
        $village2 = Village::factory()->create(['population' => 1000]);
        $village3 = Village::factory()->create(['population' => 1500]);

        $minPopulation = Village::byPopulation(800)->get();
        $maxPopulation = Village::byPopulation(null, 1200)->get();
        $rangePopulation = Village::byPopulation(800, 1200)->get();

        $this->assertFalse($minPopulation->contains($village1));
        $this->assertTrue($minPopulation->contains($village2));
        $this->assertTrue($minPopulation->contains($village3));

        $this->assertTrue($maxPopulation->contains($village1));
        $this->assertTrue($maxPopulation->contains($village2));
        $this->assertFalse($maxPopulation->contains($village3));

        $this->assertFalse($rangePopulation->contains($village1));
        $this->assertTrue($rangePopulation->contains($village2));
        $this->assertFalse($rangePopulation->contains($village3));
    }

    /**
     * @test
     */
    public function it_can_scope_top_villages()
    {
        $village1 = Village::factory()->create(['population' => 500]);
        $village2 = Village::factory()->create(['population' => 1500]);
        $village3 = Village::factory()->create(['population' => 1000]);

        $topVillages = Village::topVillages(2)->get();

        $this->assertFalse($topVillages->contains($village1));
        $this->assertTrue($topVillages->contains($village2));
        $this->assertTrue($topVillages->contains($village3));
    }

    /**
     * @test
     */
    public function it_can_scope_recent_villages()
    {
        $recentVillage = Village::factory()->create(['created_at' => now()->subDays(3)]);
        $oldVillage = Village::factory()->create(['created_at' => now()->subDays(10)]);

        $recentVillages = Village::recent(7)->get();

        $this->assertTrue($recentVillages->contains($recentVillage));
        $this->assertFalse($recentVillages->contains($oldVillage));
    }

    /**
     * @test
     */
    public function it_can_scope_search_villages()
    {
        $player1 = Player::factory()->create(['name' => 'John Doe']);
        $player2 = Player::factory()->create(['name' => 'Jane Smith']);
        $village1 = Village::factory()->create(['name' => 'Test Village', 'player_id' => $player1->id]);
        $village2 = Village::factory()->create(['name' => 'Another Village', 'player_id' => $player2->id]);

        $searchResults = Village::search('Test')->get();

        $this->assertTrue($searchResults->contains($village1));
        $this->assertFalse($searchResults->contains($village2));
    }

    /**
     * @test
     */
    public function it_can_scope_within_radius()
    {
        $village1 = Village::factory()->create(['x_coordinate' => 100, 'y_coordinate' => 100]);
        $village2 = Village::factory()->create(['x_coordinate' => 150, 'y_coordinate' => 150]);
        $village3 = Village::factory()->create(['x_coordinate' => 300, 'y_coordinate' => 300]);

        $withinRadius = Village::withinRadius(100, 100, 100)->get();

        $this->assertTrue($withinRadius->contains($village1));
        $this->assertTrue($withinRadius->contains($village2));
        $this->assertFalse($withinRadius->contains($village3));
    }

    /**
     * @test
     */
    public function it_can_scope_order_by_distance()
    {
        $village1 = Village::factory()->create(['x_coordinate' => 100, 'y_coordinate' => 100]);
        $village2 = Village::factory()->create(['x_coordinate' => 150, 'y_coordinate' => 150]);
        $village3 = Village::factory()->create(['x_coordinate' => 200, 'y_coordinate' => 200]);

        $orderedVillages = Village::orderByDistance(100, 100)->get();

        $this->assertEquals($village1->id, $orderedVillages->first()->id);
        $this->assertEquals($village2->id, $orderedVillages->skip(1)->first()->id);
        $this->assertEquals($village3->id, $orderedVillages->last()->id);
    }

    /**
     * @test
     */
    public function it_has_allowed_filters()
    {
        $village = new Village();
        $filters = $village->allowedFilters();

        $this->assertInstanceOf(\IndexZer0\EloquentFiltering\Filter\Contracts\AllowedFilterList::class, $filters);
    }

    /**
     * @test
     */
    public function it_can_get_real_world_coordinates()
    {
        $village = Village::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.006,
        ]);

        $coords = $village->getRealWorldCoordinates();

        $this->assertEquals(40.7128, $coords['lat']);
        $this->assertEquals(-74.006, $coords['lon']);
    }

    /**
     * @test
     */
    public function it_can_update_geographic_data()
    {
        $village = Village::factory()->create([
            'x_coordinate' => 100,
            'y_coordinate' => 200,
        ]);

        // Mock the GeographicService
        $this->mock(\App\Services\GeographicService::class, function ($mock): void {
            $mock->shouldReceive('gameToRealWorld')->andReturn(['lat' => 40.7128, 'lon' => -74.006]);
            $mock->shouldReceive('generateGeohash')->andReturn('dr5regy3');
        });

        $village->updateGeographicData();

        $this->assertEquals(40.7128, $village->latitude);
        $this->assertEquals(-74.006, $village->longitude);
        $this->assertEquals('dr5regy3', $village->geohash);
    }

    /**
     * @test
     */
    public function it_can_calculate_distance_to_another_village()
    {
        $village1 = Village::factory()->create(['x_coordinate' => 100, 'y_coordinate' => 100]);
        $village2 = Village::factory()->create(['x_coordinate' => 150, 'y_coordinate' => 150]);

        // Mock the GeographicService
        $this->mock(\App\Services\GeographicService::class, function ($mock): void {
            $mock->shouldReceive('calculateGameDistance')->andReturn(70.71);
        });

        $distance = $village1->distanceTo($village2);

        $this->assertEquals(70.71, $distance);
    }

    /**
     * @test
     */
    public function it_can_calculate_real_world_distance_to_another_village()
    {
        $village1 = Village::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.006,
        ]);
        $village2 = Village::factory()->create([
            'latitude' => 41.8781,
            'longitude' => -87.6298,
        ]);

        // Mock the GeographicService
        $this->mock(\App\Services\GeographicService::class, function ($mock): void {
            $mock->shouldReceive('calculateDistance')->andReturn(1200.5);
        });

        $distance = $village1->realWorldDistanceTo($village2);

        $this->assertEquals(1200.5, $distance);
    }

    /**
     * @test
     */
    public function it_can_get_geohash()
    {
        $village = Village::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.006,
        ]);

        // Mock the GeographicService
        $this->mock(\App\Services\GeographicService::class, function ($mock): void {
            $mock->shouldReceive('generateGeohash')->andReturn('dr5regy3');
        });

        $geohash = $village->getGeohash(8);

        $this->assertEquals('dr5regy3', $geohash);
    }

    /**
     * @test
     */
    public function it_can_get_bearing_to_another_village()
    {
        $village1 = Village::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.006,
        ]);
        $village2 = Village::factory()->create([
            'latitude' => 41.8781,
            'longitude' => -87.6298,
        ]);

        // Mock the GeographicService
        $this->mock(\App\Services\GeographicService::class, function ($mock): void {
            $mock->shouldReceive('getBearing')->andReturn(270.5);
        });

        $bearing = $village1->bearingTo($village2);

        $this->assertEquals(270.5, $bearing);
    }
}
