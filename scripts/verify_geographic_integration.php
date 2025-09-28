<?php

/**
 * Geographic Integration Verification Script
 *
 * This script verifies that all geographic features are working correctly.
 * Run with: php verify_geographic_integration.php
 */

require_once __DIR__.'/vendor/autoload.php';

use App\Models\Game\Village;
use App\Models\Game\World;
use App\Services\GeographicAnalysisService;
use App\Services\GeographicService;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Geographic Integration Verification\n";
echo "=====================================\n\n";

$allTestsPassed = true;

// Test 1: Geographic Service
echo "1. Testing Geographic Service...\n";
try {
    $geoService = new GeographicService;

    // Test distance calculation
    $distance = $geoService->calculateDistance(52.520008, 13.404954, 48.8566, 2.3522);
    if ($distance > 800 && $distance < 1000) {
        echo '   ✅ Distance calculation: '.round($distance, 2)." km\n";
    } else {
        echo "   ❌ Distance calculation failed\n";
        $allTestsPassed = false;
    }

    // Test bearing calculation
    $bearing = $geoService->calculateBearing(52.520008, 13.404954, 48.8566, 2.3522);
    if ($bearing >= 0 && $bearing < 360) {
        echo '   ✅ Bearing calculation: '.round($bearing, 1)."°\n";
    } else {
        echo "   ❌ Bearing calculation failed\n";
        $allTestsPassed = false;
    }

    // Test coordinate conversion
    $gameCoords = $geoService->realWorldToGame(50.1, 8.1);
    if (isset($gameCoords['x']) && isset($gameCoords['y'])) {
        echo '   ✅ Coordinate conversion: ('.$gameCoords['x'].', '.$gameCoords['y'].")\n";
    } else {
        echo "   ❌ Coordinate conversion failed\n";
        $allTestsPassed = false;
    }

    // Test geohash generation
    $geohash = $geoService->generateGeohash(50.1, 8.1);
    if (is_string($geohash) && strlen($geohash) > 0) {
        echo '   ✅ Geohash generation: '.$geohash."\n";
    } else {
        echo "   ❌ Geohash generation failed\n";
        $allTestsPassed = false;
    }

} catch (Exception $e) {
    echo '   ❌ Geographic Service failed: '.$e->getMessage()."\n";
    $allTestsPassed = false;
}

// Test 2: Village Model
echo "\n2. Testing Village Model...\n";
try {
    $village = Village::whereNotNull('latitude')->whereNotNull('longitude')->first();
    if ($village) {
        echo '   ✅ Found village with coordinates: '.$village->name."\n";

        // Test real-world coordinates
        $coords = $village->getRealWorldCoordinates();
        if (isset($coords['lat']) && isset($coords['lon'])) {
            echo '   ✅ Real-world coordinates: ('.$coords['lat'].'°, '.$coords['lon']."°)\n";
        } else {
            echo "   ❌ Real-world coordinates failed\n";
            $allTestsPassed = false;
        }

        // Test geohash
        $geohash = $village->getGeohash();
        if (is_string($geohash) && strlen($geohash) > 0) {
            echo '   ✅ Village geohash: '.$geohash."\n";
        } else {
            echo "   ❌ Village geohash failed\n";
            $allTestsPassed = false;
        }

    } else {
        echo "   ⚠️  No villages with coordinates found\n";
    }
} catch (Exception $e) {
    echo '   ❌ Village Model failed: '.$e->getMessage()."\n";
    $allTestsPassed = false;
}

// Test 3: Geographic Analysis Service
echo "\n3. Testing Geographic Analysis Service...\n";
try {
    $world = World::first();
    if ($world) {
        $analysisService = new GeographicAnalysisService($geoService);
        $analysis = $analysisService->analyzeVillageDistribution($world);

        if (isset($analysis['total_villages']) && isset($analysis['with_coordinates'])) {
            echo '   ✅ Village distribution analysis: '.$analysis['total_villages'].' total, '.$analysis['with_coordinates']." with coordinates\n";
        } else {
            echo "   ❌ Village distribution analysis failed\n";
            $allTestsPassed = false;
        }

        if (isset($analysis['geographic_bounds']) && $analysis['geographic_bounds']) {
            $bounds = $analysis['geographic_bounds'];
            echo '   ✅ Geographic bounds: '.$bounds['south'].'°-'.$bounds['north'].'°N, '.$bounds['west'].'°-'.$bounds['east']."°E\n";
        } else {
            echo "   ❌ Geographic bounds analysis failed\n";
            $allTestsPassed = false;
        }

    } else {
        echo "   ⚠️  No worlds found for analysis\n";
    }
} catch (Exception $e) {
    echo '   ❌ Geographic Analysis Service failed: '.$e->getMessage()."\n";
    $allTestsPassed = false;
}

// Test 4: Commands
echo "\n4. Testing Commands...\n";
try {
    $commands = [
        'villages:populate-geographic-data',
        'geographic:analyze',
    ];

    foreach ($commands as $command) {
        $output = shell_exec("php artisan list | grep '$command'");
        if ($output && strpos($output, $command) !== false) {
            echo "   ✅ Command registered: $command\n";
        } else {
            echo "   ❌ Command not found: $command\n";
            $allTestsPassed = false;
        }
    }
} catch (Exception $e) {
    echo '   ❌ Command testing failed: '.$e->getMessage()."\n";
    $allTestsPassed = false;
}

// Test 5: Routes
echo "\n5. Testing Routes...\n";
try {
    $routes = [
        'game.advanced-map' => '/game/advanced-map',
    ];

    foreach ($routes as $name => $path) {
        $output = shell_exec("php artisan route:list | grep '$name'");
        if ($output && strpos($output, $name) !== false) {
            echo "   ✅ Route registered: $name ($path)\n";
        } else {
            echo "   ❌ Route not found: $name\n";
            $allTestsPassed = false;
        }
    }
} catch (Exception $e) {
    echo '   ❌ Route testing failed: '.$e->getMessage()."\n";
    $allTestsPassed = false;
}

// Final Results
echo "\n".str_repeat('=', 50)."\n";
if ($allTestsPassed) {
    echo "🎉 ALL TESTS PASSED!\n";
    echo "✅ Geographic integration is working correctly.\n";
    echo "✅ All features are functional and ready for use.\n";
} else {
    echo "❌ SOME TESTS FAILED!\n";
    echo "⚠️  Please check the failed tests above.\n";
}
echo str_repeat('=', 50)."\n\n";

echo "📋 Available Features:\n";
echo "   - Real-world coordinate mapping\n";
echo "   - Distance calculations (Haversine formula)\n";
echo "   - Bearing calculations\n";
echo "   - Geohash generation\n";
echo "   - Spatial analysis\n";
echo "   - Interactive map interface\n";
echo "   - Command line tools\n";
echo "   - Village geographic methods\n\n";

echo "🚀 Access Points:\n";
echo "   - Advanced Map: /game/advanced-map\n";
echo "   - Demo Script: php demo_geographic_features.php\n";
echo "   - Analysis: php artisan geographic:analyze\n";
echo "   - Data Population: php artisan villages:populate-geographic-data\n\n";
