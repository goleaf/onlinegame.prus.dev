<?php

namespace Tests\Unit\Services;

use App\Services\GeographicService;
use Tests\TestCase;

class GeographicServiceTest extends TestCase
{
    private GeographicService $geoService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->geoService = new GeographicService();
    }

    public function test_calculate_distance_returns_float()
    {
        $distance = $this->geoService->calculateDistance(52.520008, 13.404954, 48.8566, 2.3522);

        $this->assertIsFloat($distance);
        $this->assertGreaterThan(0, $distance);
    }

    public function test_calculate_distance_berlin_to_paris()
    {
        // Berlin to Paris is approximately 878 km
        $distance = $this->geoService->calculateDistance(52.520008, 13.404954, 48.8566, 2.3522);

        $this->assertGreaterThan(800, $distance);
        $this->assertLessThan(1000, $distance);
    }

    public function test_calculate_game_distance()
    {
        $distance = $this->geoService->calculateGameDistance(100, 100, 200, 200);

        $this->assertIsFloat($distance);
        $this->assertGreaterThan(0, $distance);
        // Should be approximately 141.42 (sqrt(100^2 + 100^2))
        $this->assertGreaterThan(140, $distance);
        $this->assertLessThan(142, $distance);
    }

    public function test_calculate_bearing()
    {
        $bearing = $this->geoService->calculateBearing(52.520008, 13.404954, 48.8566, 2.3522);

        $this->assertIsFloat($bearing);
        $this->assertGreaterThanOrEqual(0, $bearing);
        $this->assertLessThan(360, $bearing);
    }

    public function test_game_to_real_world_conversion()
    {
        $coords = $this->geoService->gameToRealWorld(500, 500);

        $this->assertIsArray($coords);
        $this->assertArrayHasKey('lat', $coords);
        $this->assertArrayHasKey('lon', $coords);
        $this->assertIsFloat($coords['lat']);
        $this->assertIsFloat($coords['lon']);
    }

    public function test_real_world_to_game_conversion()
    {
        $coords = $this->geoService->realWorldToGame(50.1, 8.1);

        $this->assertIsArray($coords);
        $this->assertArrayHasKey('x', $coords);
        $this->assertArrayHasKey('y', $coords);
        $this->assertIsInt($coords['x']);
        $this->assertIsInt($coords['y']);
    }

    public function test_generate_geohash()
    {
        $geohash = $this->geoService->generateGeohash(52.520008, 13.404954);

        $this->assertIsString($geohash);
        $this->assertGreaterThan(0, strlen($geohash));
    }

    public function test_calculate_travel_time()
    {
        $time = $this->geoService->calculateTravelTimeFromCoordinates(52.520008, 13.404954, 48.8566, 2.3522, 50);

        $this->assertIsInt($time);
        $this->assertGreaterThan(0, $time);
    }
}
