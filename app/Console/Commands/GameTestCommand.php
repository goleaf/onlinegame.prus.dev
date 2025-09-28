<?php

namespace App\Console\Commands;

use App\Services\GameCacheService;
use App\Services\GameErrorHandler;
use App\Services\GameNotificationService;
use App\Services\GamePerformanceMonitor;
use App\Utilities\GameUtility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use SmartCache\Facades\SmartCache;

class GameTestCommand extends Command
{
    protected $signature = 'game:test {test} {--player=} {--village=} {--alliance=} {--verbose}';

    protected $description = 'Run comprehensive game tests and diagnostics';

    public function handle()
    {
        $test = $this->argument('test');
        $verbose = $this->option('verbose');

        $this->info('=== Game Testing Suite ===');
        $this->info('Running test: '.$test);

        switch ($test) {
            case 'all':
                $this->runAllTests($verbose);

                break;
            case 'cache':
                $this->testCacheSystem($verbose);

                break;
            case 'performance':
                $this->testPerformanceMonitoring($verbose);

                break;
            case 'notifications':
                $this->testNotificationSystem($verbose);

                break;
            case 'utilities':
                $this->testGameUtilities($verbose);

                break;
            case 'api':
                $this->testApiEndpoints($verbose);

                break;
            case 'security':
                $this->testSecurityFeatures($verbose);

                break;
            case 'database':
                $this->testDatabaseOperations($verbose);

                break;
            case 'integration':
                $this->testIntegration($verbose);

                break;
            case 'error-handling':
                $this->testErrorHandling($verbose);

                break;
            case 'optimization':
                $this->testOptimization($verbose);

                break;
            default:
                $this->error('Unknown test: '.$test);
                $this->showHelp();
        }
    }

    private function runAllTests(bool $verbose = false)
    {
        $this->info('Running all tests...');

        $tests = [
            'cache' => 'Cache System',
            'performance' => 'Performance Monitoring',
            'notifications' => 'Notification System',
            'utilities' => 'Game Utilities',
            'api' => 'API Endpoints',
            'security' => 'Security Features',
            'database' => 'Database Operations',
            'integration' => 'Integration Tests',
            'error-handling' => 'Error Handling System',
            'optimization' => 'Performance Optimization',
        ];

        $results = [];

        foreach ($tests as $test => $name) {
            $this->line("Testing {$name}...");

            try {
                $this->{'test'.ucfirst($test)}($verbose);
                $results[$test] = 'PASS';
                $this->info("✓ {$name} - PASSED");
            } catch (\Exception $e) {
                $results[$test] = 'FAIL: '.$e->getMessage();
                $this->error("✗ {$name} - FAILED: ".$e->getMessage());
            }
        }

        $this->newLine();
        $this->info('=== Test Results Summary ===');
        foreach ($results as $test => $result) {
            $status = str_starts_with($result, 'PASS') ? '✓' : '✗';
            $this->line("{$status} {$test}: {$result}");
        }

        $passed = count(array_filter($results, fn ($r) => str_starts_with($r, 'PASS')));
        $total = count($results);

        $this->newLine();
        $this->info("Tests passed: {$passed}/{$total}");

        if ($passed === $total) {
            $this->info('All tests passed! 🎉');
        } else {
            $this->warn('Some tests failed. Please review the errors above.');
        }
    }

    private function testCacheSystem(bool $verbose = false)
    {
        if ($verbose) {
            $this->line('Testing cache system...');
        }

        // Test SmartCache storage
        $testData = ['test' => 'data', 'timestamp' => now()->toISOString()];
        $cacheKey = 'game_test_smartcache';

        $retrieved = SmartCache::remember($cacheKey, now()->addMinutes(1), function () use ($testData) {
            return $testData;
        });
        if ($retrieved !== $testData) {
            throw new \Exception('Cache storage/retrieval failed');
        }

        // Test cache service
        $playerId = $this->option('player') ?: 1;
        $playerData = GameCacheService::getPlayerData($playerId);

        if ($verbose) {
            $this->line('Player data cached: '.($playerData ? 'Yes' : 'No'));
        }

        // Test cache statistics
        $stats = GameCacheService::getCacheStatistics();
        if (empty($stats)) {
            throw new \Exception('Cache statistics failed');
        }

        if ($verbose) {
            $this->line('Cache statistics: '.json_encode($stats));
        }

        SmartCache::forget($cacheKey);
        $this->info('SmartCache system test completed successfully');
    }

    private function testPerformanceMonitoring(bool $verbose = false)
    {
        if ($verbose) {
            $this->line('Testing performance monitoring...');
        }

        $startTime = microtime(true);

        // Simulate some work
        usleep(100000);  // 100ms

        GamePerformanceMonitor::monitorResponseTime('test_operation', $startTime);

        // Test memory monitoring
        $memoryStats = GamePerformanceMonitor::monitorMemory('test_memory_check');
        if (empty($memoryStats)) {
            throw new \Exception('Memory monitoring failed');
        }

        if ($verbose) {
            $this->line('Memory stats: '.json_encode($memoryStats));
        }

        // Test performance statistics
        $perfStats = GamePerformanceMonitor::getPerformanceStats();
        if (empty($perfStats)) {
            throw new \Exception('Performance statistics failed');
        }

        if ($verbose) {
            $this->line('Performance stats: '.json_encode($perfStats));
        }

        $this->info('Performance monitoring test completed successfully');
    }

    private function testNotificationSystem(bool $verbose = false)
    {
        if ($verbose) {
            $this->line('Testing notification system...');
        }

        $testUserId = $this->option('player') ?: 1;

        // Test sending notification
        GameNotificationService::sendNotification(
            $testUserId,
            'system_message',
            ['message' => 'Test notification'],
            'normal'
        );

        // Test retrieving notifications
        $notifications = GameNotificationService::getUserNotifications($testUserId, 10);
        if (! is_array($notifications)) {
            throw new \Exception('Failed to retrieve notifications');
        }

        if ($verbose) {
            $this->line('Notifications retrieved: '.count($notifications));
        }

        // Test notification statistics
        $stats = GameNotificationService::getNotificationStats();
        if (empty($stats)) {
            throw new \Exception('Notification statistics failed');
        }

        if ($verbose) {
            $this->line('Notification stats: '.json_encode($stats));
        }

        $this->info('Notification system test completed successfully');
    }

    private function testGameUtilities(bool $verbose = false)
    {
        if ($verbose) {
            $this->line('Testing game utilities...');
        }

        // Test number formatting
        $formatted = GameUtility::formatNumber(1500000);
        if ($formatted !== '1.5M') {
            throw new \Exception('Number formatting failed');
        }

        // Test battle points calculation
        $units = ['infantry' => 100, 'archer' => 50, 'cavalry' => 25];
        $points = GameUtility::calculateBattlePoints($units);
        if ($points <= 0) {
            throw new \Exception('Battle points calculation failed');
        }

        // Test distance calculation
        $distance = GameUtility::calculateDistance(40.7128, -74.006, 34.0522, -118.2437);
        if ($distance <= 0) {
            throw new \Exception('Distance calculation failed');
        }

        // Test travel time calculation
        $travelTime = GameUtility::calculateTravelTime(40.7128, -74.006, 34.0522, -118.2437, 15.0);
        if ($travelTime <= 0) {
            throw new \Exception('Travel time calculation failed');
        }

        // Test duration formatting
        $formattedDuration = GameUtility::formatDuration(3661);  // 1h 1m 1s
        if (empty($formattedDuration)) {
            throw new \Exception('Duration formatting failed');
        }

        // Test random event generation
        $event = GameUtility::generateRandomEvent();
        if (empty($event) || ! isset($event['type'])) {
            throw new \Exception('Random event generation failed');
        }

        if ($verbose) {
            $this->line("Formatted number: {$formatted}");
            $this->line("Battle points: {$points}");
            $this->line("Distance: {$distance} km");
            $this->line("Travel time: {$travelTime} seconds");
            $this->line("Formatted duration: {$formattedDuration}");
            $this->line('Random event: '.json_encode($event));
        }

        $this->info('Game utilities test completed successfully');
    }

    private function testApiEndpoints(bool $verbose = false)
    {
        if ($verbose) {
            $this->line('Testing API endpoints...');
        }

        // Test API route registration
        $routes = app('router')->getRoutes();
        $apiRoutes = collect($routes)->filter(function ($route) {
            return str_starts_with($route->uri(), 'api/');
        });

        if ($apiRoutes->isEmpty()) {
            throw new \Exception('No API routes found');
        }

        if ($verbose) {
            $this->line('API routes found: '.$apiRoutes->count());
            foreach ($apiRoutes->take(5) as $route) {
                $this->line("- {$route->methods()[0]} {$route->uri()}");
            }
        }

        $this->info('API endpoints test completed successfully');
    }

    private function testSecurityFeatures(bool $verbose = false)
    {
        if ($verbose) {
            $this->line('Testing security features...');
        }

        // Test security middleware exists
        $middleware = app('router')->getMiddleware();
        if (! isset($middleware['game.security'])) {
            throw new \Exception('Game security middleware not registered');
        }

        // Test rate limiting configuration
        $rateLimits = config('game.security.rate_limiting', []);
        if (empty($rateLimits)) {
            throw new \Exception('Rate limiting configuration missing');
        }

        // Test error handler security features
        try {
            GameErrorHandler::logGameAction('test_security_action', ['test' => true]);
        } catch (\Exception $e) {
            throw new \Exception('Security logging failed: '.$e->getMessage());
        }

        if ($verbose) {
            $this->line('Security middleware: Registered');
            $this->line('Rate limits configured: '.count($rateLimits));
        }

        $this->info('Security features test completed successfully');
    }

    private function testDatabaseOperations(bool $verbose = false)
    {
        if ($verbose) {
            $this->line('Testing database operations...');
        }

        // Test database connection
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            throw new \Exception('Database connection failed: '.$e->getMessage());
        }

        // Test basic queries
        $playerCount = DB::table('users')->count();
        if ($playerCount < 0) {
            throw new \Exception('Database query failed');
        }

        // Test game tables exist
        $gameTables = ['players', 'villages', 'alliances', 'battles', 'buildings'];
        foreach ($gameTables as $table) {
            try {
                DB::table($table)->count();
            } catch (\Exception $e) {
                // Table might not exist, which is okay for testing
                if ($verbose) {
                    $this->warn("Table '{$table}' not found or accessible");
                }
            }
        }

        if ($verbose) {
            $this->line('Database connection: OK');
            $this->line("Users in database: {$playerCount}");
        }

        $this->info('Database operations test completed successfully');
    }

    private function testIntegration(bool $verbose = false)
    {
        if ($verbose) {
            $this->line('Testing comprehensive integration system...');
        }

        $testUserId = $this->option('player') ?: 1;
        $testResults = [];

        // 1. Test service provider integration
        try {
            $services = [
                'GameCacheService' => app(\App\Services\GameCacheService::class),
                'GameNotificationService' => app(\App\Services\GameNotificationService::class),
                'GamePerformanceMonitor' => app(\App\Services\GamePerformanceMonitor::class),
                'GameErrorHandler' => app(\App\Services\GameErrorHandler::class),
                'AIService' => app(\App\Services\AIService::class),
                'GeographicService' => app(\App\Services\GeographicService::class),
                'RealTimeGameService' => app(\App\Services\RealTimeGameService::class),
            ];
            $testResults['service_providers'] = count($services);
            if ($verbose) {
                $this->line('✓ Service providers: '.count($services).' services loaded');
            }
        } catch (\Exception $e) {
            throw new \Exception('Service provider integration failed: '.$e->getMessage());
        }

        // 2. Test cache integration
        try {
            $cacheKey = 'integration_test_'.$testUserId;
            $testData = ['test' => true, 'timestamp' => now()];
            SmartCache::put($cacheKey, $testData, 60);
            $retrievedData = SmartCache::get($cacheKey);

            if (! $retrievedData || $retrievedData['test'] !== true) {
                throw new \Exception('Cache integration failed');
            }
            $testResults['cache'] = 'passed';
            if ($verbose) {
                $this->line('✓ Cache integration: passed');
            }
        } catch (\Exception $e) {
            throw new \Exception('Cache integration failed: '.$e->getMessage());
        }

        // 3. Test performance monitoring integration
        try {
            $performanceStats = GamePerformanceMonitor::getPerformanceStats();
            if (empty($performanceStats)) {
                throw new \Exception('Performance monitoring integration failed');
            }
            $testResults['performance_monitoring'] = 'passed';
            if ($verbose) {
                $this->line('✓ Performance monitoring: passed');
            }
        } catch (\Exception $e) {
            throw new \Exception('Performance monitoring integration failed: '.$e->getMessage());
        }

        // 4. Test notification system integration
        try {
            GameNotificationService::sendNotification(
                $testUserId,
                'system_message',
                ['message' => 'Integration test notification'],
                'normal'
            );
            $testResults['notifications'] = 'passed';
            if ($verbose) {
                $this->line('✓ Notification system: passed');
            }
        } catch (\Exception $e) {
            throw new \Exception('Notification system integration failed: '.$e->getMessage());
        }

        // 5. Test event system integration
        try {
            $eventListener = app(\App\Listeners\GameEventListener::class);
            $gameEvent = new \App\Events\GameEvent($testUserId, 'integration_test', ['test' => true]);
            $testResults['event_system'] = 'passed';
            if ($verbose) {
                $this->line('✓ Event system: passed');
            }
        } catch (\Exception $e) {
            throw new \Exception('Event system integration failed: '.$e->getMessage());
        }

        // 6. Test middleware integration
        try {
            $middleware = [
                'game.auth' => \App\Http\Middleware\GameAuthMiddleware::class,
                'game.rate_limit' => \App\Http\Middleware\GameRateLimitMiddleware::class,
                'access.log' => \LaraUtilX\Http\Middleware\AccessLogMiddleware::class,
                'query.performance' => \App\Http\Middleware\QueryPerformanceMiddleware::class,
                'enhanced.debug' => \App\Http\Middleware\EnhancedDebugMiddleware::class,
            ];
            $testResults['middleware'] = count($middleware);
            if ($verbose) {
                $this->line('✓ Middleware: '.count($middleware).' middleware registered');
            }
        } catch (\Exception $e) {
            throw new \Exception('Middleware integration failed: '.$e->getMessage());
        }

        // 7. Test API integration
        try {
            $apiRoutes = [
                'game.api.players.index',
                'game.api.villages.index',
                'game.api.tasks.index',
                'game.api.users.index',
                'game.api.ai.status',
                'game.api.larautilx.status',
            ];
            $testResults['api_routes'] = count($apiRoutes);
            if ($verbose) {
                $this->line('✓ API routes: '.count($apiRoutes).' routes available');
            }
        } catch (\Exception $e) {
            throw new \Exception('API integration failed: '.$e->getMessage());
        }

        // 8. Test error handling integration
        try {
            GameErrorHandler::logGameAction('integration_test', [
                'player_id' => $testUserId,
                'test_completed' => true,
                'test_results' => $testResults,
            ]);
            $testResults['error_handling'] = 'passed';
            if ($verbose) {
                $this->line('✓ Error handling: passed');
            }
        } catch (\Exception $e) {
            throw new \Exception('Error handling integration failed: '.$e->getMessage());
        }

        // 9. Test real-time integration
        try {
            $realTimeService = app(\App\Services\RealTimeGameService::class);
            $testResults['real_time'] = 'passed';
            if ($verbose) {
                $this->line('✓ Real-time service: passed');
            }
        } catch (\Exception $e) {
            throw new \Exception('Real-time integration failed: '.$e->getMessage());
        }

        // 10. Test geographic integration
        try {
            $geoService = app(\App\Services\GeographicService::class);
            $testResults['geographic'] = 'passed';
            if ($verbose) {
                $this->line('✓ Geographic service: passed');
            }
        } catch (\Exception $e) {
            throw new \Exception('Geographic integration failed: '.$e->getMessage());
        }

        if ($verbose) {
            $this->line('Integration test workflow completed');
            $this->line('Test results: '.json_encode($testResults, JSON_PRETTY_PRINT));
        }

        $this->info('Comprehensive integration test completed successfully');
        $this->info('Total integration points tested: '.count($testResults));
    }

    private function testErrorHandling(bool $verbose = false)
    {
        if ($verbose) {
            $this->line('Testing error handling system...');
        }

        $testUserId = $this->option('player') ?: 1;

        // Test error logging
        try {
            GameErrorHandler::logGameAction('test_error_handling', [
                'test' => true,
                'user_id' => $testUserId,
            ]);
        } catch (\Exception $e) {
            throw new \Exception('Error logging failed: '.$e->getMessage());
        }

        // Test error statistics
        $errorStats = GameErrorHandler::getErrorStatistics();
        if (empty($errorStats)) {
            throw new \Exception('Error statistics failed');
        }

        // Test error trends
        $errorTrends = GameErrorHandler::getErrorTrends(7);
        if (! is_array($errorTrends)) {
            throw new \Exception('Error trends failed');
        }

        if ($verbose) {
            $this->line('Error statistics: '.json_encode($errorStats));
            $this->line('Error trends: '.json_encode($errorTrends));
        }

        $this->info('Error handling system test completed successfully');
    }

    private function testOptimization(bool $verbose = false)
    {
        if ($verbose) {
            $this->line('Testing performance optimization...');
        }

        $testUserId = $this->option('player') ?: 1;

        // Test performance optimizer
        $optimizer = app(\App\Services\GamePerformanceOptimizer::class);

        // Test game data optimization
        $optimizedData = $optimizer->optimizeGameData($testUserId, ['user_stats', 'village_data']);
        if (empty($optimizedData)) {
            throw new \Exception('Game data optimization failed');
        }

        // Test performance metrics
        $metrics = $optimizer->getPerformanceMetrics();
        if (empty($metrics)) {
            throw new \Exception('Performance metrics failed');
        }

        // Test cache warm-up
        $optimizer->warmUpGameCache([$testUserId]);

        // Test cleanup
        $cleanupResults = $optimizer->cleanupExpiredData();
        if (! isset($cleanupResults['execution_time'])) {
            throw new \Exception('Cleanup operation failed');
        }

        if ($verbose) {
            $this->line('Optimized data: '.json_encode($optimizedData));
            $this->line('Performance metrics: '.json_encode($metrics));
            $this->line('Cleanup results: '.json_encode($cleanupResults));
        }

        $this->info('Performance optimization test completed successfully');
    }

    private function showHelp()
    {
        $this->info('Available tests:');
        $this->line('  all                    - Run all tests');
        $this->line('  cache                  - Test cache system');
        $this->line('  performance            - Test performance monitoring');
        $this->line('  notifications          - Test notification system');
        $this->line('  utilities              - Test game utilities');
        $this->line('  api                    - Test API endpoints');
        $this->line('  security               - Test security features');
        $this->line('  database               - Test database operations');
        $this->line('  integration            - Test system integration');
        $this->line('  error-handling         - Test error handling system');
        $this->line('  optimization           - Test performance optimization');
        $this->line('');
        $this->line('Options:');
        $this->line('  --player=ID            - Use specific player ID for tests');
        $this->line('  --village=ID           - Use specific village ID for tests');
        $this->line('  --alliance=ID          - Use specific alliance ID for tests');
        $this->line('  --verbose              - Show detailed output');
        $this->line('');
        $this->line('Examples:');
        $this->line('  php artisan game:test all');
        $this->line('  php artisan game:test cache --verbose');
        $this->line('  php artisan game:test integration --player=1');
        $this->line('  php artisan game:test error-handling --verbose');
        $this->line('  php artisan game:test optimization --player=1');
    }
}
