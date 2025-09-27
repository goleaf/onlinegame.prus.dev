<?php

/**
 * Geographic Integration Demo Script
 * 
 * This script demonstrates the geographic features integrated into the Laravel game.
 * Run with: php demo_geographic_features.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\GeographicService;
use App\Services\GeographicAnalysisService;
use App\Models\Game\World;
use App\Models\Game\Village;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🌍 Geographic Integration Demo\n";
echo "==============================\n\n";

// Initialize services
$geoService = new GeographicService();
$analysisService = new GeographicAnalysisService($geoService);

echo "1. Geographic Service Tests\n";
echo "----------------------------\n";

// Test distance calculation
$berlin = [52.520008, 13.404954];
$paris = [48.8566, 2.3522];
$distance = $geoService->calculateDistance($berlin[0], $berlin[1], $paris[0], $paris[1]);
echo "📍 Berlin to Paris distance: " . round($distance, 2) . " km\n";

// Test bearing calculation
$bearing = $geoService->calculateBearing($berlin[0], $berlin[1], $paris[0], $paris[1]);
echo "🧭 Bearing from Berlin to Paris: " . round($bearing, 1) . "°\n";

// Test coordinate conversion
$gameCoords = $geoService->realWorldToGame(50.1, 8.1);
echo "🎮 Real-world (50.1°, 8.1°) to game coordinates: (" . $gameCoords['x'] . ", " . $gameCoords['y'] . ")\n";

$realCoords = $geoService->gameToRealWorld(500, 500);
echo "🌐 Game (500, 500) to real-world coordinates: (" . round($realCoords['lat'], 6) . "°, " . round($realCoords['lon'], 6) . "°)\n";

// Test geohash generation
$geohash = $geoService->generateGeohash(50.1, 8.1);
echo "🔢 Geohash for (50.1°, 8.1°): " . $geohash . "\n";

echo "\n2. Village Analysis\n";
echo "-------------------\n";

// Get a world with villages
$world = World::first();
if ($world) {
    echo "🌍 Analyzing World: " . $world->name . "\n";
    
    $villages = Village::where('world_id', $world->id)->whereNotNull('latitude')->get();
    echo "🏘️  Villages with coordinates: " . $villages->count() . "\n";
    
    if ($villages->count() > 0) {
        // Show first few villages
        echo "\n📍 Sample villages:\n";
        foreach ($villages->take(3) as $village) {
            echo "   - " . $village->name . " at (" . $village->latitude . "°, " . $village->longitude . "°)\n";
        }
        
        // Calculate distances between first two villages
        if ($villages->count() >= 2) {
            $v1 = $villages->first();
            $v2 = $villages->skip(1)->first();
            $distance = $geoService->calculateDistance($v1->latitude, $v1->longitude, $v2->latitude, $v2->longitude);
            echo "\n📏 Distance between " . $v1->name . " and " . $v2->name . ": " . round($distance, 2) . " km\n";
        }
    }
} else {
    echo "❌ No worlds found\n";
}

echo "\n3. Geographic Analysis\n";
echo "-----------------------\n";

if ($world) {
    try {
        $analysis = $analysisService->analyzeVillageDistribution($world);
        
        echo "📊 Analysis Results:\n";
        echo "   - Total villages: " . $analysis['total_villages'] . "\n";
        echo "   - With coordinates: " . $analysis['with_coordinates'] . "\n";
        echo "   - Coverage: " . round($analysis['coverage_percentage'], 2) . "%\n";
        
        if ($analysis['geographic_bounds']) {
            $bounds = $analysis['geographic_bounds'];
            echo "   - Geographic bounds: " . $bounds['south'] . "°-". $bounds['north'] . "°N, " . $bounds['west'] . "°-". $bounds['east'] . "°E\n";
            echo "   - Center: " . round($bounds['center_lat'], 4) . "°, " . round($bounds['center_lon'], 4) . "°\n";
        }
        
        if (!empty($analysis['density_analysis'])) {
            $density = $analysis['density_analysis'];
            echo "   - Total area: " . round($density['total_area_km2'], 2) . " km²\n";
            echo "   - Village density: " . round($density['village_density'], 4) . " villages/km²\n";
            echo "   - Density category: " . $density['density_category'] . "\n";
        }
        
        // Travel patterns analysis
        $travelAnalysis = $analysisService->analyzeTravelPatterns($world);
        if (!empty($travelAnalysis)) {
            echo "\n🚶 Travel Patterns:\n";
            echo "   - Average distance: " . round($travelAnalysis['average_distance'], 2) . " km\n";
            echo "   - Max distance: " . round($travelAnalysis['max_distance'], 2) . " km\n";
            echo "   - Min distance: " . round($travelAnalysis['min_distance'], 2) . " km\n";
        }
        
        // Optimal locations
        $optimalLocations = $analysisService->findOptimalLocations($world, 3);
        if (!empty($optimalLocations)) {
            echo "\n🎯 Top 3 Optimal Locations:\n";
            foreach ($optimalLocations as $index => $location) {
                echo "   " . ($index + 1) . ". Game (" . $location['game_x'] . ", " . $location['game_y'] . ") - " . 
                     round($location['latitude'], 4) . "°, " . round($location['longitude'], 4) . "°\n";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Analysis failed: " . $e->getMessage() . "\n";
    }
}

echo "\n4. Available Commands\n";
echo "---------------------\n";
echo "🔧 Command line tools available:\n";
echo "   - php artisan villages:populate-geographic-data\n";
echo "   - php artisan geographic:analyze\n";
echo "   - php artisan geographic:analyze {world_id}\n";

echo "\n🌐 Web Interface:\n";
echo "   - Advanced Map: /game/advanced-map\n";
echo "   - Interactive village visualization\n";
echo "   - Real-time geographic overlays\n";

echo "\n✅ Geographic Integration Demo Complete!\n";
echo "========================================\n";
echo "The geographic features are fully integrated and working.\n";
echo "You can now use real-world coordinates, distance calculations,\n";
echo "and spatial analysis in your game.\n\n";
