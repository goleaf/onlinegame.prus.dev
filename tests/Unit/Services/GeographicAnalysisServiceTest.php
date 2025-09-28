<?php

namespace Tests\Unit\Services;

use App\Services\GeographicAnalysisService;
use App\Services\GeographicService;
use Tests\TestCase;

class GeographicAnalysisServiceTest extends TestCase
{
    private GeographicAnalysisService $analysisService;

    protected function setUp(): void
    {
        parent::setUp();
        $geoService = new GeographicService();
        $this->analysisService = new GeographicAnalysisService($geoService);
    }

    public function test_calculate_geographic_bounds()
    {
        $villages = collect([
            (object) ['latitude' => 50.0, 'longitude' => 8.0],
            (object) ['latitude' => 50.4, 'longitude' => 8.4],
            (object) ['latitude' => 50.2, 'longitude' => 8.2],
        ]);

        $bounds = $this->invokeMethod($this->analysisService, 'calculateGeographicBounds', [$villages]);

        $this->assertIsArray($bounds);
        $this->assertEquals(50.4, $bounds['north']);
        $this->assertEquals(50.0, $bounds['south']);
        $this->assertEquals(8.4, $bounds['east']);
        $this->assertEquals(8.0, $bounds['west']);
        $this->assertEqualsWithDelta(50.2, $bounds['center_lat'], 0.0001);
        $this->assertEqualsWithDelta(8.2, $bounds['center_lon'], 0.0001);
    }

    public function test_calculate_area()
    {
        $area = $this->invokeMethod($this->analysisService, 'calculateArea', [50.4, 50.0, 8.4, 8.0]);

        $this->assertIsFloat($area);
        $this->assertGreaterThan(0, $area);
    }

    public function test_categorize_density()
    {
        $categories = [
            'very_short' => 0,
            'short' => 0,
            'medium' => 0,
            'long' => 0,
            'very_long' => 0,
        ];

        $distances = [2, 10, 30, 80, 150]; // km
        $result = $this->invokeMethod($this->analysisService, 'categorizeDistances', [$distances]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('very_short', $result);
        $this->assertArrayHasKey('short', $result);
        $this->assertArrayHasKey('medium', $result);
        $this->assertArrayHasKey('long', $result);
        $this->assertArrayHasKey('very_long', $result);
    }

    public function test_analyze_bearings()
    {
        $bearings = [0, 45, 90, 135, 180, 225, 270, 315];
        $result = $this->invokeMethod($this->analysisService, 'analyzeBearings', [$bearings]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('north', $result);
        $this->assertArrayHasKey('northeast', $result);
        $this->assertArrayHasKey('east', $result);
        $this->assertArrayHasKey('southeast', $result);
        $this->assertArrayHasKey('south', $result);
        $this->assertArrayHasKey('southwest', $result);
        $this->assertArrayHasKey('west', $result);
        $this->assertArrayHasKey('northwest', $result);
    }

    /**
     * Helper method to invoke private/protected methods for testing
     */
    private function invokeMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
