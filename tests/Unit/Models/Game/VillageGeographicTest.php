<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Village;
use App\Models\Game\Player;
use App\Models\Game\World;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VillageGeographicTest extends TestCase
{
    use RefreshDatabase;

    private Village $village1;
    private Village $village2;
    private Player $player;
    private World $world;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $user = User::factory()->create();
        $this->world = World::factory()->create();
        $this->player = Player::factory()->create([
            'user_id' => $user->id,
            'world_id' => $this->world->id
        ]);

        $this->village1 = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'x_coordinate' => 100,
            'y_coordinate' => 100,
            'latitude' => 52.520008,
            'longitude' => 13.404954
        ]);

        $this->village2 = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'x_coordinate' => 200,
            'y_coordinate' => 200,
            'latitude' => 48.8566,
            'longitude' => 2.3522
        ]);
    }

    public function test_get_real_world_coordinates_returns_stored_coordinates()
    {
        $coords = $this->village1->getRealWorldCoordinates();

        $this->assertIsArray($coords);
        $this->assertArrayHasKey('lat', $coords);
        $this->assertArrayHasKey('lon', $coords);
        $this->assertEquals(52.520008, $coords['lat']);
        $this->assertEquals(13.404954, $coords['lon']);
    }

    public function test_get_real_world_coordinates_calculates_from_game_coords()
    {
        // Create village without stored coordinates
        $village = Village::factory()->create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'x_coordinate' => 500,
            'y_coordinate' => 500,
            'latitude' => null,
            'longitude' => null
        ]);

        $coords = $village->getRealWorldCoordinates();

        $this->assertIsArray($coords);
        $this->assertArrayHasKey('lat', $coords);
        $this->assertArrayHasKey('lon', $coords);
        $this->assertIsFloat($coords['lat']);
        $this->assertIsFloat($coords['lon']);
    }

    public function test_update_geographic_data()
    {
        $this->village1->updateGeographicData();

        $this->village1->refresh();

        $this->assertNotNull($this->village1->latitude);
        $this->assertNotNull($this->village1->longitude);
        $this->assertNotNull($this->village1->geohash);
        $this->assertIsFloat($this->village1->latitude);
        $this->assertIsFloat($this->village1->longitude);
        $this->assertIsString($this->village1->geohash);
    }

    public function test_distance_to_calculates_correct_distance()
    {
        $distance = $this->village1->distanceTo($this->village2);

        $this->assertIsFloat($distance);
        $this->assertGreaterThan(0, $distance);
    }

    public function test_real_world_distance_to_calculates_correct_distance()
    {
        $distance = $this->village1->realWorldDistanceTo($this->village2);

        $this->assertIsFloat($distance);
        $this->assertGreaterThan(0, $distance);
        // Berlin to Paris is approximately 878 km
        $this->assertGreaterThan(800, $distance);
        $this->assertLessThan(1000, $distance);
    }

    public function test_get_geohash_returns_geohash()
    {
        $geohash = $this->village1->getGeohash();

        $this->assertIsString($geohash);
        $this->assertGreaterThan(0, strlen($geohash));
    }

    public function test_bearing_to_calculates_correct_bearing()
    {
        $bearing = $this->village1->bearingTo($this->village2);

        $this->assertIsFloat($bearing);
        $this->assertGreaterThanOrEqual(0, $bearing);
        $this->assertLessThan(360, $bearing);
    }

    public function test_village_has_geographic_fillable_fields()
    {
        $fillable = $this->village1->getFillable();

        $this->assertContains('latitude', $fillable);
        $this->assertContains('longitude', $fillable);
        $this->assertContains('geohash', $fillable);
        $this->assertContains('elevation', $fillable);
        $this->assertContains('geographic_metadata', $fillable);
    }

    public function test_village_has_geographic_casts()
    {
        $casts = $this->village1->getCasts();

        $this->assertArrayHasKey('latitude', $casts);
        $this->assertArrayHasKey('longitude', $casts);
        $this->assertArrayHasKey('geographic_metadata', $casts);
        $this->assertEquals('float', $casts['latitude']);
        $this->assertEquals('float', $casts['longitude']);
        $this->assertEquals('array', $casts['geographic_metadata']);
    }
}

