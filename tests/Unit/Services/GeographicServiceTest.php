<?php

namespace Tests\Unit\Services;

use App\Services\GeographicService;
use Tests\TestCase;

class GeographicServiceTest extends TestCase
{
    private GeographicService $geographicService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->geographicService = new GeographicService();
    }

    public function test_calculate_distance_returns_correct_distance()
    {
        // Test distance between two known points
        $lat1 = 52.520008; // Berlin
        $lon1 = 13.404954;
        $lat2 = 48.8566;   // Paris
        $lon2 = 2.3522;

        $distance = $this->geographicService->calculateDistance($lat1, $lon1, $lat2, $lon2);

        // Berlin to Paris is approximately 878 km
        $this->assertGreaterThan(870, $distance);
        $this->assertLessThan(890, $distance);
    }

    public function test_calculate_game_distance_returns_correct_distance()
    {
        $x1 = 0;
        $y1 = 0;
        $x2 = 3;
        $y2 = 4;

        $distance = $this->geographicService->calculateGameDistance($x1, $y1, $x2, $y2);

        // Should be 5 (3-4-5 triangle)
        $this->assertEquals(5, $distance);
    }

    public function test_game_to_real_world_conversion()
    {
        $x = 500;
        $y = 500;
        $worldSize = 1000;

        $result = $this->geographicService->gameToRealWorld($x, $y, $worldSize);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('lat', $result);
        $this->assertArrayHasKey('lon', $result);
        $this->assertIsFloat($result['lat']);
        $this->assertIsFloat($result['lon']);
    }

    public function test_real_world_to_game_conversion()
    {
        $lat = 52.520008;
        $lon = 13.404954;
        $worldSize = 1000;

        $result = $this->geographicService->realWorldToGame($lat, $lon, $worldSize);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('x', $result);
        $this->assertArrayHasKey('y', $result);
        $this->assertIsInt($result['x']);
        $this->assertIsInt($result['y']);
    }

    public function test_generate_geohash()
    {
        $lat = 52.520008;
        $lon = 13.404954;
        $precision = 8;

        $geohash = $this->geographicService->generateGeohash($lat, $lon, $precision);

        $this->assertIsString($geohash);
        $this->assertEquals($precision, strlen($geohash));
    }

    public function test_decode_geohash()
    {
        $geohash = 'u33d8b5j';

        $result = $this->geographicService->decodeGeohash($geohash);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('lat', $result);
        $this->assertArrayHasKey('lon', $result);
        $this->assertIsFloat($result['lat']);
        $this->assertIsFloat($result['lon']);
    }

    public function test_calculate_travel_time()
    {
        $distanceKm = 100;
        $speedKmh = 50;

        $travelTime = $this->geographicService->calculateTravelTime($distanceKm, $speedKmh);

        // 100 km at 50 km/h = 2 hours = 7200 seconds
        $this->assertEquals(7200, $travelTime);
    }

    public function test_get_bearing()
    {
        $lat1 = 52.520008;
        $lon1 = 13.404954;
        $lat2 = 48.8566;
        $lon2 = 2.3522;

        $bearing = $this->geographicService->getBearing($lat1, $lon1, $lat2, $lon2);

        $this->assertIsFloat($bearing);
        $this->assertGreaterThanOrEqual(0, $bearing);
        $this->assertLessThan(360, $bearing);
    }

    public function test_is_point_in_bounds()
    {
        $lat = 52.520008;
        $lon = 13.404954;
        $minLat = 52.0;
        $maxLat = 53.0;
        $minLon = 13.0;
        $maxLon = 14.0;

        $result = $this->geographicService->isPointInBounds($lat, $lon, $minLat, $maxLat, $minLon, $maxLon);

        $this->assertTrue($result);
    }

    public function test_calculate_center_point()
    {
        $coordinates = [
            ['lat' => 52.0, 'lon' => 13.0],
            ['lat' => 53.0, 'lon' => 14.0],
            ['lat' => 51.0, 'lon' => 12.0],
        ];

        $center = $this->geographicService->calculateCenterPoint($coordinates);

        $this->assertIsArray($center);
        $this->assertArrayHasKey('lat', $center);
        $this->assertArrayHasKey('lon', $center);
        $this->assertIsFloat($center['lat']);
        $this->assertIsFloat($center['lon']);
    }

    public function test_generate_random_coordinate()
    {
        $minLat = 50.0;
        $maxLat = 55.0;
        $minLon = 10.0;
        $maxLon = 15.0;

        $coordinate = $this->geographicService->generateRandomCoordinate($minLat, $maxLat, $minLon, $maxLon);

        $this->assertIsArray($coordinate);
        $this->assertArrayHasKey('lat', $coordinate);
        $this->assertArrayHasKey('lon', $coordinate);
        $this->assertGreaterThanOrEqual($minLat, $coordinate['lat']);
        $this->assertLessThanOrEqual($maxLat, $coordinate['lat']);
        $this->assertGreaterThanOrEqual($minLon, $coordinate['lon']);
        $this->assertLessThanOrEqual($maxLon, $coordinate['lon']);
    }
}
