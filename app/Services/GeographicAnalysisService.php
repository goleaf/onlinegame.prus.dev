<?php

namespace App\Services;

use App\Models\Game\Village;
use App\Models\Game\World;
use Illuminate\Support\Collection;

class GeographicAnalysisService
{
    protected GeographicService $geoService;

    public function __construct(GeographicService $geoService)
    {
        $this->geoService = $geoService;
    }

    /**
     * Analyze village distribution patterns
     *
     * @param World $world
     * @return array
     */
    public function analyzeVillageDistribution(World $world): array
    {
        $villages = Village::where('world_id', $world->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        if ($villages->isEmpty()) {
            return [
                'total_villages' => 0,
                'with_coordinates' => 0,
                'coverage_percentage' => 0,
                'density_analysis' => [],
                'clustering_analysis' => [],
                'geographic_bounds' => null
            ];
        }

        $bounds = $this->calculateGeographicBounds($villages);
        $density = $this->calculateDensityAnalysis($villages, $bounds);
        $clustering = $this->analyzeClustering($villages);

        return [
            'total_villages' => Village::where('world_id', $world->id)->count(),
            'with_coordinates' => $villages->count(),
            'coverage_percentage' => ($villages->count() / Village::where('world_id', $world->id)->count()) * 100,
            'density_analysis' => $density,
            'clustering_analysis' => $clustering,
            'geographic_bounds' => $bounds
        ];
    }

    /**
     * Calculate geographic bounds of villages
     *
     * @param Collection $villages
     * @return array
     */
    protected function calculateGeographicBounds(Collection $villages): array
    {
        $lats = $villages->pluck('latitude')->filter();
        $lons = $villages->pluck('longitude')->filter();

        if ($lats->isEmpty() || $lons->isEmpty()) {
            return null;
        }

        return [
            'north' => $lats->max(),
            'south' => $lats->min(),
            'east' => $lons->max(),
            'west' => $lons->min(),
            'center_lat' => $lats->avg(),
            'center_lon' => $lons->avg(),
            'span_lat' => $lats->max() - $lats->min(),
            'span_lon' => $lons->max() - $lons->min()
        ];
    }

    /**
     * Calculate density analysis
     *
     * @param Collection $villages
     * @param array $bounds
     * @return array
     */
    protected function calculateDensityAnalysis(Collection $villages, array $bounds): array
    {
        if (!$bounds) {
            return [];
        }

        $totalArea = $this->calculateArea($bounds['north'], $bounds['south'], $bounds['east'], $bounds['west']);
        $density = $villages->count() / $totalArea;  // villages per km²

        return [
            'total_area_km2' => $totalArea,
            'village_density' => $density,
            'density_category' => $this->categorizeDensity($density)
        ];
    }

    /**
     * Analyze village clustering
     *
     * @param Collection $villages
     * @return array
     */
    protected function analyzeClustering(Collection $villages): array
    {
        $clusters = [];
        $processed = [];

        foreach ($villages as $village) {
            if (in_array($village->id, $processed)) {
                continue;
            }

            $cluster = $this->findCluster($village, $villages, $processed);
            if (count($cluster) > 1) {
                $clusters[] = $cluster;
                $processed = array_merge($processed, array_column($cluster, 'id'));
            }
        }

        return [
            'total_clusters' => count($clusters),
            'largest_cluster_size' => count($clusters) > 0 ? max(array_map('count', $clusters)) : 0,
            'average_cluster_size' => count($clusters) > 0 ? array_sum(array_map('count', $clusters)) / count($clusters) : 0,
            'clusters' => $clusters
        ];
    }

    /**
     * Find villages in the same cluster
     *
     * @param Village $center
     * @param Collection $villages
     * @param array $processed
     * @return array
     */
    protected function findCluster(Village $center, Collection $villages, array $processed): array
    {
        $cluster = [$center];
        $clusterRadius = 0.1;  // 0.1 degrees ≈ 11km

        foreach ($villages as $village) {
            if (in_array($village->id, $processed) || $village->id === $center->id) {
                continue;
            }

            $distance = $this->geoService->calculateDistance(
                $center->latitude,
                $center->longitude,
                $village->latitude,
                $village->longitude
            );

            if ($distance <= $clusterRadius) {
                $cluster[] = $village;
            }
        }

        return $cluster;
    }

    /**
     * Calculate area in km²
     *
     * @param float $north
     * @param float $south
     * @param float $east
     * @param float $west
     * @return float
     */
    protected function calculateArea(float $north, float $south, float $east, float $west): float
    {
        // Simple rectangular area calculation
        $latSpan = $north - $south;
        $lonSpan = $east - $west;

        // Convert degrees to km (approximate)
        $latKm = $latSpan * 111.32;  // 1 degree latitude ≈ 111.32 km
        $lonKm = $lonSpan * 111.32 * cos(deg2rad(($north + $south) / 2));

        return $latKm * $lonKm;
    }

    /**
     * Categorize density level
     *
     * @param float $density
     * @return string
     */
    protected function categorizeDensity(float $density): string
    {
        if ($density < 0.1)
            return 'Very Low';
        if ($density < 0.5)
            return 'Low';
        if ($density < 1.0)
            return 'Medium';
        if ($density < 2.0)
            return 'High';
        return 'Very High';
    }

    /**
     * Find optimal village locations based on geographic analysis
     *
     * @param World $world
     * @param int $count
     * @return array
     */
    public function findOptimalLocations(World $world, int $count = 10): array
    {
        $existingVillages = Village::where('world_id', $world->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $bounds = $this->calculateGeographicBounds($existingVillages);
        if (!$bounds) {
            return [];
        }

        $optimalLocations = [];
        $attempts = 0;
        $maxAttempts = $count * 10;

        while (count($optimalLocations) < $count && $attempts < $maxAttempts) {
            $lat = $bounds['south'] + (rand(0, 10000) / 10000) * $bounds['span_lat'];
            $lon = $bounds['west'] + (rand(0, 10000) / 10000) * $bounds['span_lon'];

            // Check if location is far enough from existing villages
            $minDistance = 0.05;  // 0.05 degrees ≈ 5.5km
            $tooClose = false;

            foreach ($existingVillages as $village) {
                $distance = $this->geoService->calculateDistance(
                    $lat, $lon,
                    $village->latitude, $village->longitude
                );

                if ($distance < $minDistance) {
                    $tooClose = true;
                    break;
                }
            }

            if (!$tooClose) {
                $optimalLocations[] = [
                    'latitude' => $lat,
                    'longitude' => $lon,
                    'game_x' => $this->geoService->realWorldToGame($lat, $lon)['x'],
                    'game_y' => $this->geoService->realWorldToGame($lat, $lon)['y']
                ];
            }

            $attempts++;
        }

        return $optimalLocations;
    }

    /**
     * Analyze travel patterns between villages
     *
     * @param World $world
     * @return array
     */
    public function analyzeTravelPatterns(World $world): array
    {
        $villages = Village::where('world_id', $world->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $distances = [];
        $bearings = [];

        for ($i = 0; $i < count($villages); $i++) {
            for ($j = $i + 1; $j < count($villages); $j++) {
                $village1 = $villages[$i];
                $village2 = $villages[$j];

                $distance = $this->geoService->calculateDistance(
                    $village1->latitude, $village1->longitude,
                    $village2->latitude, $village2->longitude
                );

                $bearing = $this->geoService->calculateBearing(
                    $village1->latitude, $village1->longitude,
                    $village2->latitude, $village2->longitude
                );

                $distances[] = $distance;
                $bearings[] = $bearing;
            }
        }

        return [
            'average_distance' => count($distances) > 0 ? array_sum($distances) / count($distances) : 0,
            'max_distance' => count($distances) > 0 ? max($distances) : 0,
            'min_distance' => count($distances) > 0 ? min($distances) : 0,
            'distance_distribution' => $this->categorizeDistances($distances),
            'bearing_analysis' => $this->analyzeBearings($bearings)
        ];
    }

    /**
     * Categorize distances
     *
     * @param array $distances
     * @return array
     */
    protected function categorizeDistances(array $distances): array
    {
        $categories = [
            'very_short' => 0,  // < 5km
            'short' => 0,  // 5-20km
            'medium' => 0,  // 20-50km
            'long' => 0,  // 50-100km
            'very_long' => 0  // > 100km
        ];

        foreach ($distances as $distance) {
            if ($distance < 5)
                $categories['very_short']++;
            elseif ($distance < 20)
                $categories['short']++;
            elseif ($distance < 50)
                $categories['medium']++;
            elseif ($distance < 100)
                $categories['long']++;
            else
                $categories['very_long']++;
        }

        return $categories;
    }

    /**
     * Analyze bearing patterns
     *
     * @param array $bearings
     * @return array
     */
    protected function analyzeBearings(array $bearings): array
    {
        $directions = [
            'north' => 0,  // 0-22.5, 337.5-360
            'northeast' => 0,  // 22.5-67.5
            'east' => 0,  // 67.5-112.5
            'southeast' => 0,  // 112.5-157.5
            'south' => 0,  // 157.5-202.5
            'southwest' => 0,  // 202.5-247.5
            'west' => 0,  // 247.5-292.5
            'northwest' => 0  // 292.5-337.5
        ];

        foreach ($bearings as $bearing) {
            if ($bearing >= 337.5 || $bearing < 22.5)
                $directions['north']++;
            elseif ($bearing < 67.5)
                $directions['northeast']++;
            elseif ($bearing < 112.5)
                $directions['east']++;
            elseif ($bearing < 157.5)
                $directions['southeast']++;
            elseif ($bearing < 202.5)
                $directions['south']++;
            elseif ($bearing < 247.5)
                $directions['southwest']++;
            elseif ($bearing < 292.5)
                $directions['west']++;
            else
                $directions['northwest']++;
        }

        return $directions;
    }
}
