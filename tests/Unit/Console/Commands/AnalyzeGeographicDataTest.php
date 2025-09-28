<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\AnalyzeGeographicData;
use App\Models\Game\World;
use App\Services\GeographicAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyzeGeographicDataTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_analyze_geographic_data_for_specific_world()
    {
        $world = World::factory()->create(['name' => 'Test World']);

        $this->mock(GeographicAnalysisService::class, function ($mock): void {
            $mock
                ->shouldReceive('analyzeVillageDistribution')
                ->once()
                ->andReturn([
                    'total_villages' => 100,
                    'with_coordinates' => 95,
                    'coverage_percentage' => 95.0,
                    'geographic_bounds' => [
                        'north' => 50.0,
                        'south' => 40.0,
                        'east' => 10.0,
                        'west' => 0.0,
                        'center_lat' => 45.0,
                        'center_lon' => 5.0,
                        'span_lat' => 10.0,
                        'span_lon' => 10.0,
                    ],
                    'density_analysis' => [
                        'total_area_km2' => 10000.0,
                        'village_density' => 0.01,
                        'density_category' => 'medium',
                    ],
                    'clustering_analysis' => [
                        'total_clusters' => 5,
                        'largest_cluster_size' => 30,
                        'average_cluster_size' => 20.0,
                    ],
                ]);

            $mock
                ->shouldReceive('analyzeTravelPatterns')
                ->once()
                ->andReturn([
                    'average_distance' => 50.5,
                    'max_distance' => 200.0,
                    'min_distance' => 5.0,
                    'distance_distribution' => [
                        'short' => 40,
                        'medium' => 30,
                        'long' => 20,
                    ],
                    'bearing_analysis' => [
                        'north' => 25,
                        'south' => 20,
                        'east' => 30,
                        'west' => 25,
                    ],
                ]);

            $mock
                ->shouldReceive('findOptimalLocations')
                ->with(\Mockery::any(), 5)
                ->once()
                ->andReturn([
                    [
                        'game_x' => 100,
                        'game_y' => 200,
                        'latitude' => 45.5,
                        'longitude' => 5.5,
                    ],
                    [
                        'game_x' => 150,
                        'game_y' => 250,
                        'latitude' => 46.0,
                        'longitude' => 6.0,
                    ],
                ]);
        });

        $this
            ->artisan('geographic:analyze', ['world_id' => $world->id])
            ->expectsOutput('Analyzing World: Test World (ID: '.$world->id.')')
            ->expectsOutput('==================================================')
            ->expectsOutput('Total Villages: 100')
            ->expectsOutput('With Coordinates: 95')
            ->expectsOutput('Coverage: 95.00%')
            ->expectsOutput('Geographic Bounds:')
            ->expectsOutput('  North: 50°')
            ->expectsOutput('  South: 40°')
            ->expectsOutput('  East: 10°')
            ->expectsOutput('  West: 0°')
            ->expectsOutput('  Center: 45°, 5°')
            ->expectsOutput('  Span: 10° × 10°')
            ->expectsOutput('Density Analysis:')
            ->expectsOutput('  Total Area: 10,000.00 km²')
            ->expectsOutput('  Village Density: 0.0100 villages/km²')
            ->expectsOutput('  Density Category: medium')
            ->expectsOutput('Clustering Analysis:')
            ->expectsOutput('  Total Clusters: 5')
            ->expectsOutput('  Largest Cluster: 30 villages')
            ->expectsOutput('  Average Cluster Size: 20.00')
            ->expectsOutput('Analyzing travel patterns...')
            ->expectsOutput('  Average Distance: 50.50 km')
            ->expectsOutput('  Max Distance: 200.00 km')
            ->expectsOutput('  Min Distance: 5.00 km')
            ->expectsOutput('  Distance Distribution:')
            ->expectsOutput('    Short: 40')
            ->expectsOutput('    Medium: 30')
            ->expectsOutput('    Long: 20')
            ->expectsOutput('  Direction Analysis:')
            ->expectsOutput('    North: 25')
            ->expectsOutput('    South: 20')
            ->expectsOutput('    East: 30')
            ->expectsOutput('    West: 25')
            ->expectsOutput('Finding optimal locations...')
            ->expectsOutput('  Top 5 Optimal Locations:')
            ->expectsOutput('    1. (100, 200) - 45.5°, 5.5°')
            ->expectsOutput('    2. (150, 250) - 46.0°, 6.0°')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_analyze_geographic_data_for_all_worlds()
    {
        $world1 = World::factory()->create(['name' => 'World 1']);
        $world2 = World::factory()->create(['name' => 'World 2']);

        $this->mock(GeographicAnalysisService::class, function ($mock): void {
            $mock
                ->shouldReceive('analyzeVillageDistribution')
                ->twice()
                ->andReturn([
                    'total_villages' => 50,
                    'with_coordinates' => 45,
                    'coverage_percentage' => 90.0,
                    'geographic_bounds' => [
                        'north' => 50.0,
                        'south' => 40.0,
                        'east' => 10.0,
                        'west' => 0.0,
                        'center_lat' => 45.0,
                        'center_lon' => 5.0,
                        'span_lat' => 10.0,
                        'span_lon' => 10.0,
                    ],
                    'density_analysis' => [
                        'total_area_km2' => 5000.0,
                        'village_density' => 0.01,
                        'density_category' => 'medium',
                    ],
                    'clustering_analysis' => [
                        'total_clusters' => 3,
                        'largest_cluster_size' => 20,
                        'average_cluster_size' => 16.67,
                    ],
                ]);

            $mock
                ->shouldReceive('analyzeTravelPatterns')
                ->twice()
                ->andReturn([
                    'average_distance' => 40.0,
                    'max_distance' => 150.0,
                    'min_distance' => 5.0,
                    'distance_distribution' => [
                        'short' => 30,
                        'medium' => 20,
                        'long' => 15,
                    ],
                    'bearing_analysis' => [
                        'north' => 20,
                        'south' => 15,
                        'east' => 25,
                        'west' => 20,
                    ],
                ]);

            $mock
                ->shouldReceive('findOptimalLocations')
                ->with(\Mockery::any(), 5)
                ->twice()
                ->andReturn([
                    [
                        'game_x' => 100,
                        'game_y' => 200,
                        'latitude' => 45.5,
                        'longitude' => 5.5,
                    ],
                ]);
        });

        $this
            ->artisan('geographic:analyze')
            ->expectsOutput('Analyzing all worlds...')
            ->expectsOutput('Analyzing World: World 1 (ID: '.$world1->id.')')
            ->expectsOutput('==================================================')
            ->expectsOutput('Total Villages: 50')
            ->expectsOutput('With Coordinates: 45')
            ->expectsOutput('Coverage: 90.00%')
            ->expectsOutput('Geographic Bounds:')
            ->expectsOutput('  North: 50°')
            ->expectsOutput('  South: 40°')
            ->expectsOutput('  East: 10°')
            ->expectsOutput('  West: 0°')
            ->expectsOutput('  Center: 45°, 5°')
            ->expectsOutput('  Span: 10° × 10°')
            ->expectsOutput('Density Analysis:')
            ->expectsOutput('  Total Area: 5,000.00 km²')
            ->expectsOutput('  Village Density: 0.0100 villages/km²')
            ->expectsOutput('  Density Category: medium')
            ->expectsOutput('Clustering Analysis:')
            ->expectsOutput('  Total Clusters: 3')
            ->expectsOutput('  Largest Cluster: 20 villages')
            ->expectsOutput('  Average Cluster Size: 16.67')
            ->expectsOutput('Analyzing travel patterns...')
            ->expectsOutput('  Average Distance: 40.00 km')
            ->expectsOutput('  Max Distance: 150.00 km')
            ->expectsOutput('  Min Distance: 5.00 km')
            ->expectsOutput('  Distance Distribution:')
            ->expectsOutput('    Short: 30')
            ->expectsOutput('    Medium: 20')
            ->expectsOutput('    Long: 15')
            ->expectsOutput('  Direction Analysis:')
            ->expectsOutput('    North: 20')
            ->expectsOutput('    South: 15')
            ->expectsOutput('    East: 25')
            ->expectsOutput('    West: 20')
            ->expectsOutput('Finding optimal locations...')
            ->expectsOutput('  Top 5 Optimal Locations:')
            ->expectsOutput('    1. (100, 200) - 45.5°, 5.5°')
            ->expectsOutput('Analyzing World: World 2 (ID: '.$world2->id.')')
            ->expectsOutput('==================================================')
            ->expectsOutput('Total Villages: 50')
            ->expectsOutput('With Coordinates: 45')
            ->expectsOutput('Coverage: 90.00%')
            ->expectsOutput('Geographic Bounds:')
            ->expectsOutput('  North: 50°')
            ->expectsOutput('  South: 40°')
            ->expectsOutput('  East: 10°')
            ->expectsOutput('  West: 0°')
            ->expectsOutput('  Center: 45°, 5°')
            ->expectsOutput('  Span: 10° × 10°')
            ->expectsOutput('Density Analysis:')
            ->expectsOutput('  Total Area: 5,000.00 km²')
            ->expectsOutput('  Village Density: 0.0100 villages/km²')
            ->expectsOutput('  Density Category: medium')
            ->expectsOutput('Clustering Analysis:')
            ->expectsOutput('  Total Clusters: 3')
            ->expectsOutput('  Largest Cluster: 20 villages')
            ->expectsOutput('  Average Cluster Size: 16.67')
            ->expectsOutput('Analyzing travel patterns...')
            ->expectsOutput('  Average Distance: 40.00 km')
            ->expectsOutput('  Max Distance: 150.00 km')
            ->expectsOutput('  Min Distance: 5.00 km')
            ->expectsOutput('  Distance Distribution:')
            ->expectsOutput('    Short: 30')
            ->expectsOutput('    Medium: 20')
            ->expectsOutput('    Long: 15')
            ->expectsOutput('  Direction Analysis:')
            ->expectsOutput('    North: 20')
            ->expectsOutput('    South: 15')
            ->expectsOutput('    East: 25')
            ->expectsOutput('    West: 20')
            ->expectsOutput('Finding optimal locations...')
            ->expectsOutput('  Top 5 Optimal Locations:')
            ->expectsOutput('    1. (100, 200) - 45.5°, 5.5°')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_nonexistent_world()
    {
        $this
            ->artisan('geographic:analyze', ['world_id' => 999])
            ->expectsOutput('World with ID 999 not found.')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_no_worlds_found()
    {
        $this
            ->artisan('geographic:analyze')
            ->expectsOutput('No worlds found.')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_analyze_geographic_data_with_minimal_analysis()
    {
        $world = World::factory()->create(['name' => 'Test World']);

        $this->mock(GeographicAnalysisService::class, function ($mock): void {
            $mock
                ->shouldReceive('analyzeVillageDistribution')
                ->once()
                ->andReturn([
                    'total_villages' => 10,
                    'with_coordinates' => 5,
                    'coverage_percentage' => 50.0,
                    'geographic_bounds' => null,
                    'density_analysis' => [],
                    'clustering_analysis' => [],
                ]);

            $mock
                ->shouldReceive('analyzeTravelPatterns')
                ->once()
                ->andReturn([]);

            $mock
                ->shouldReceive('findOptimalLocations')
                ->with(\Mockery::any(), 5)
                ->once()
                ->andReturn([]);
        });

        $this
            ->artisan('geographic:analyze', ['world_id' => $world->id])
            ->expectsOutput('Analyzing World: Test World (ID: '.$world->id.')')
            ->expectsOutput('==================================================')
            ->expectsOutput('Total Villages: 10')
            ->expectsOutput('With Coordinates: 5')
            ->expectsOutput('Coverage: 50.00%')
            ->expectsOutput('Analyzing travel patterns...')
            ->expectsOutput('Finding optimal locations...')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_analyze_geographic_data_with_partial_analysis()
    {
        $world = World::factory()->create(['name' => 'Test World']);

        $this->mock(GeographicAnalysisService::class, function ($mock): void {
            $mock
                ->shouldReceive('analyzeVillageDistribution')
                ->once()
                ->andReturn([
                    'total_villages' => 25,
                    'with_coordinates' => 20,
                    'coverage_percentage' => 80.0,
                    'geographic_bounds' => [
                        'north' => 50.0,
                        'south' => 40.0,
                        'east' => 10.0,
                        'west' => 0.0,
                        'center_lat' => 45.0,
                        'center_lon' => 5.0,
                        'span_lat' => 10.0,
                        'span_lon' => 10.0,
                    ],
                    'density_analysis' => [],
                    'clustering_analysis' => [],
                ]);

            $mock
                ->shouldReceive('analyzeTravelPatterns')
                ->once()
                ->andReturn([
                    'average_distance' => 30.0,
                    'max_distance' => 100.0,
                    'min_distance' => 5.0,
                    'distance_distribution' => [],
                    'bearing_analysis' => [],
                ]);

            $mock
                ->shouldReceive('findOptimalLocations')
                ->with(\Mockery::any(), 5)
                ->once()
                ->andReturn([]);
        });

        $this
            ->artisan('geographic:analyze', ['world_id' => $world->id])
            ->expectsOutput('Analyzing World: Test World (ID: '.$world->id.')')
            ->expectsOutput('==================================================')
            ->expectsOutput('Total Villages: 25')
            ->expectsOutput('With Coordinates: 20')
            ->expectsOutput('Coverage: 80.00%')
            ->expectsOutput('Geographic Bounds:')
            ->expectsOutput('  North: 50°')
            ->expectsOutput('  South: 40°')
            ->expectsOutput('  East: 10°')
            ->expectsOutput('  West: 0°')
            ->expectsOutput('  Center: 45°, 5°')
            ->expectsOutput('  Span: 10° × 10°')
            ->expectsOutput('Analyzing travel patterns...')
            ->expectsOutput('  Average Distance: 30.00 km')
            ->expectsOutput('  Max Distance: 100.00 km')
            ->expectsOutput('  Min Distance: 5.00 km')
            ->expectsOutput('Finding optimal locations...')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new AnalyzeGeographicData();
        $this->assertEquals('geographic:analyze', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new AnalyzeGeographicData();
        $this->assertEquals('Analyze geographic data and patterns for villages', $command->getDescription());
    }
}
