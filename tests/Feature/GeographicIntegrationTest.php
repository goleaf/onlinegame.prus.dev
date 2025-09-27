<?php

namespace Tests\Feature;

use App\Models\Game\Village;
use App\Models\Game\World;
use App\Services\GeographicService;
use App\Services\GeographicAnalysisService;
use Tests\TestCase;

class GeographicIntegrationTest extends TestCase
{
    public function test_geographic_service_calculations()
    {
        $geoService = new GeographicService();
        
        // Test distance calculation
        $distance = $geoService->calculateDistance(52.520008, 13.404954, 48.8566, 2.3522);
        $this->assertIsFloat($distance);
        $this->assertGreaterThan(800, $distance);
        $this->assertLessThan(1000, $distance);
        
        // Test bearing calculation
        $bearing = $geoService->calculateBearing(52.520008, 13.404954, 48.8566, 2.3522);
        $this->assertIsFloat($bearing);
        $this->assertGreaterThanOrEqual(0, $bearing);
        $this->assertLessThan(360, $bearing);
        
        // Test coordinate conversion
        $coords = $geoService->gameToRealWorld(500, 500);
        $this->assertIsArray($coords);
        $this->assertArrayHasKey('lat', $coords);
        $this->assertArrayHasKey('lon', $coords);
    }

    public function test_geographic_analysis_service()
    {
        $world = World::first();
        if (!$world) {
            $this->markTestSkipped('No world found for testing');
        }

        $geoService = new GeographicService();
        $analysisService = new GeographicAnalysisService($geoService);
        
        $analysis = $analysisService->analyzeVillageDistribution($world);
        
        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('total_villages', $analysis);
        $this->assertArrayHasKey('with_coordinates', $analysis);
        $this->assertArrayHasKey('coverage_percentage', $analysis);
        $this->assertArrayHasKey('density_analysis', $analysis);
        $this->assertArrayHasKey('clustering_analysis', $analysis);
        $this->assertArrayHasKey('geographic_bounds', $analysis);
    }

    public function test_village_geographic_methods()
    {
        $village = Village::whereNotNull('latitude')->whereNotNull('longitude')->first();
        if (!$village) {
            $this->markTestSkipped('No village with coordinates found for testing');
        }

        // Test real-world coordinates
        $coords = $village->getRealWorldCoordinates();
        $this->assertIsArray($coords);
        $this->assertArrayHasKey('lat', $coords);
        $this->assertArrayHasKey('lon', $coords);
        
        // Test geohash
        $geohash = $village->getGeohash();
        $this->assertIsString($geohash);
        $this->assertGreaterThan(0, strlen($geohash));
    }

    public function test_geographic_commands_exist()
    {
        // Test that the geographic commands are registered
        $this->artisan('list')->expectsOutput('villages:populate-geographic-data');
        $this->artisan('list')->expectsOutput('geographic:analyze');
    }

    public function test_advanced_map_route_exists()
    {
        $response = $this->get('/game/advanced-map');
        $this->assertTrue(in_array($response->status(), [200, 302, 401])); // 200 OK, 302 redirect, or 401 auth required
    }
}

