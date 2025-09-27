<?php

namespace App\Console\Commands;

use App\Models\Game\World;
use App\Services\GeographicAnalysisService;
use Illuminate\Console\Command;

class AnalyzeGeographicData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geographic:analyze {world_id? : World ID to analyze (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze geographic data and patterns for villages';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $worldId = $this->argument('world_id');
        $analysisService = app(GeographicAnalysisService::class);

        if ($worldId) {
            $world = World::find($worldId);
            if (!$world) {
                $this->error("World with ID {$worldId} not found.");
                return 1;
            }
            $this->analyzeWorld($world, $analysisService);
        } else {
            $worlds = World::all();
            if ($worlds->isEmpty()) {
                $this->error('No worlds found.');
                return 1;
            }

            $this->info('Analyzing all worlds...');
            foreach ($worlds as $world) {
                $this->analyzeWorld($world, $analysisService);
                $this->line('');
            }
        }

        return 0;
    }

    protected function analyzeWorld(World $world, GeographicAnalysisService $analysisService)
    {
        $this->info("Analyzing World: {$world->name} (ID: {$world->id})");
        $this->line('=' . str_repeat('=', 50));

        $analysis = $analysisService->analyzeVillageDistribution($world);

        // Basic statistics
        $this->line("Total Villages: {$analysis['total_villages']}");
        $this->line("With Coordinates: {$analysis['with_coordinates']}");
        $this->line("Coverage: " . number_format($analysis['coverage_percentage'], 2) . "%");

        if ($analysis['geographic_bounds']) {
            $bounds = $analysis['geographic_bounds'];
            $this->line('');
            $this->line('Geographic Bounds:');
            $this->line("  North: {$bounds['north']}°");
            $this->line("  South: {$bounds['south']}°");
            $this->line("  East: {$bounds['east']}°");
            $this->line("  West: {$bounds['west']}°");
            $this->line("  Center: {$bounds['center_lat']}°, {$bounds['center_lon']}°");
            $this->line("  Span: {$bounds['span_lat']}° × {$bounds['span_lon']}°");
        }

        if (!empty($analysis['density_analysis'])) {
            $density = $analysis['density_analysis'];
            $this->line('');
            $this->line('Density Analysis:');
            $this->line("  Total Area: " . number_format($density['total_area_km2'], 2) . " km²");
            $this->line("  Village Density: " . number_format($density['village_density'], 4) . " villages/km²");
            $this->line("  Density Category: {$density['density_category']}");
        }

        if (!empty($analysis['clustering_analysis'])) {
            $clustering = $analysis['clustering_analysis'];
            $this->line('');
            $this->line('Clustering Analysis:');
            $this->line("  Total Clusters: {$clustering['total_clusters']}");
            $this->line("  Largest Cluster: {$clustering['largest_cluster_size']} villages");
            $this->line("  Average Cluster Size: " . number_format($clustering['average_cluster_size'], 2));
        }

        // Travel patterns analysis
        $this->line('');
        $this->info('Analyzing travel patterns...');
        $travelAnalysis = $analysisService->analyzeTravelPatterns($world);

        if (!empty($travelAnalysis)) {
            $this->line("  Average Distance: " . number_format($travelAnalysis['average_distance'], 2) . " km");
            $this->line("  Max Distance: " . number_format($travelAnalysis['max_distance'], 2) . " km");
            $this->line("  Min Distance: " . number_format($travelAnalysis['min_distance'], 2) . " km");

            if (!empty($travelAnalysis['distance_distribution'])) {
                $this->line('');
                $this->line('  Distance Distribution:');
                foreach ($travelAnalysis['distance_distribution'] as $category => $count) {
                    $this->line("    " . ucfirst(str_replace('_', ' ', $category)) . ": {$count}");
                }
            }

            if (!empty($travelAnalysis['bearing_analysis'])) {
                $this->line('');
                $this->line('  Direction Analysis:');
                foreach ($travelAnalysis['bearing_analysis'] as $direction => $count) {
                    $this->line("    " . ucfirst($direction) . ": {$count}");
                }
            }
        }

        // Optimal locations
        $this->line('');
        $this->info('Finding optimal locations...');
        $optimalLocations = $analysisService->findOptimalLocations($world, 5);

        if (!empty($optimalLocations)) {
            $this->line('  Top 5 Optimal Locations:');
            foreach ($optimalLocations as $index => $location) {
                $this->line("    " . ($index + 1) . ". ({$location['game_x']}, {$location['game_y']}) - " .
                    "{$location['latitude']}°, {$location['longitude']}°");
            }
        }
    }
}