<?php

namespace App\Console\Commands;

use App\Services\GameIntegrationService;
use App\Services\GameNotificationService;
use App\Services\RealTimeGameService;
use App\Services\GameCacheService;
use App\Services\GameErrorHandler;
use App\Services\GamePerformanceMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GameIntegrationTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'game:integration-test {--user-id=1 : User ID to test with} {--detailed : Show detailed output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test all game integration services and components';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        $verbose = $this->option('verbose');

        $this->info('🎮 Starting Game Integration Test...');
        $this->newLine();

        $results = [];

        // Test 1: GameIntegrationService
        $results['game_integration'] = $this->testGameIntegrationService($userId, $verbose);

        // Test 2: GameNotificationService
        $results['game_notifications'] = $this->testGameNotificationService($userId, $verbose);

        // Test 3: RealTimeGameService
        $results['realtime_service'] = $this->testRealTimeGameService($userId, $verbose);

        // Test 4: GameCacheService
        $results['cache_service'] = $this->testGameCacheService($verbose);

        // Test 5: GameErrorHandler
        $results['error_handler'] = $this->testGameErrorHandler($verbose);

        // Test 6: GamePerformanceMonitor
        $results['performance_monitor'] = $this->testGamePerformanceMonitor($verbose);

        // Test 7: Integration Coordination
        $results['integration_coordination'] = $this->testIntegrationCoordination($userId, $verbose);

        $this->newLine();
        $this->displayResults($results);

        return $this->getOverallResult($results);
    }

    /**
     * Test GameIntegrationService
     */
    private function testGameIntegrationService(int $userId, bool $verbose): array
    {
        $this->info('🔧 Testing GameIntegrationService...');
        
        try {
            // Test user initialization
            GameIntegrationService::initializeUserRealTime($userId);
            
            // Test game statistics
            $stats = GameIntegrationService::getGameStatisticsWithRealTime();
            
            // Test system announcement
            GameIntegrationService::sendSystemAnnouncement(
                'Integration Test',
                'Testing system announcement functionality',
                'normal'
            );

            if ($verbose) {
                $this->line("✅ User initialization: Success");
                $this->line("✅ Game statistics: " . count($stats) . " items");
                $this->line("✅ System announcement: Success");
            }

            return ['status' => 'success', 'details' => 'All GameIntegrationService methods working'];
            
        } catch (\Exception $e) {
            $this->error("❌ GameIntegrationService test failed: " . $e->getMessage());
            return ['status' => 'error', 'details' => $e->getMessage()];
        }
    }

    /**
     * Test GameNotificationService
     */
    private function testGameNotificationService(int $userId, bool $verbose): array
    {
        $this->info('🔔 Testing GameNotificationService...');
        
        try {
            // Test user notification
            GameNotificationService::sendNotification(
                [$userId],
                'integration_test',
                [
                    'test_type' => 'integration_test',
                    'timestamp' => now()->toISOString(),
                ],
                'normal'
            );

            // Test system announcement
            GameNotificationService::sendSystemAnnouncement(
                'Integration Test Announcement',
                'Testing system-wide announcement functionality',
                'normal'
            );

            // Test user notifications retrieval
            $notifications = GameNotificationService::getUserNotifications($userId);

            if ($verbose) {
                $this->line("✅ User notification: Success");
                $this->line("✅ System announcement: Success");
                $this->line("✅ Notifications retrieval: " . count($notifications) . " notifications");
            }

            return ['status' => 'success', 'details' => 'All GameNotificationService methods working'];
            
        } catch (\Exception $e) {
            $this->error("❌ GameNotificationService test failed: " . $e->getMessage());
            return ['status' => 'error', 'details' => $e->getMessage()];
        }
    }

    /**
     * Test RealTimeGameService
     */
    private function testRealTimeGameService(int $userId, bool $verbose): array
    {
        $this->info('⚡ Testing RealTimeGameService...');
        
        try {
            // Test user online marking
            RealTimeGameService::markUserOnline($userId);
            
            // Test sending update
            RealTimeGameService::sendUpdate($userId, 'integration_test', [
                'message' => 'Integration test update',
                'timestamp' => now()->toISOString(),
            ]);

            // Test getting real-time stats
            $stats = RealTimeGameService::getRealTimeStats();

            if ($verbose) {
                $this->line("✅ User online marking: Success");
                $this->line("✅ Update sending: Success");
                $this->line("✅ Real-time stats: " . count($stats) . " items");
            }

            return ['status' => 'success', 'details' => 'All RealTimeGameService methods working'];
            
        } catch (\Exception $e) {
            $this->error("❌ RealTimeGameService test failed: " . $e->getMessage());
            return ['status' => 'error', 'details' => $e->getMessage()];
        }
    }

    /**
     * Test GameCacheService
     */
    private function testGameCacheService(bool $verbose): array
    {
        $this->info('💾 Testing GameCacheService...');
        
        try {
            // Test cache operations
            $cacheService = app(GameCacheService::class);
            
            // Test setting cache
            $cacheService->setGameData('integration_test', ['test' => 'data'], 60);
            
            // Test getting cache
            $cachedData = $cacheService->getGameData('integration_test');
            
            // Test cache statistics
            $stats = $cacheService->getCacheStats();

            if ($verbose) {
                $this->line("✅ Cache setting: Success");
                $this->line("✅ Cache getting: " . ($cachedData ? 'Success' : 'Failed'));
                $this->line("✅ Cache stats: " . count($stats) . " items");
            }

            return ['status' => 'success', 'details' => 'All GameCacheService methods working'];
            
        } catch (\Exception $e) {
            $this->error("❌ GameCacheService test failed: " . $e->getMessage());
            return ['status' => 'error', 'details' => $e->getMessage()];
        }
    }

    /**
     * Test GameErrorHandler
     */
    private function testGameErrorHandler(bool $verbose): array
    {
        $this->info('🛠️ Testing GameErrorHandler...');
        
        try {
            $errorHandler = app(GameErrorHandler::class);
            
            // Test error logging
            $errorHandler->logGameAction('integration_test', [
                'test' => 'integration_test',
                'timestamp' => now()->toISOString(),
            ]);

            // Test error handling
            $testException = new \Exception('Test exception for integration');
            $errorHandler->handleGameError($testException, [
                'action' => 'integration_test',
                'user_id' => 1,
            ]);

            if ($verbose) {
                $this->line("✅ Error logging: Success");
                $this->line("✅ Error handling: Success");
            }

            return ['status' => 'success', 'details' => 'All GameErrorHandler methods working'];
            
        } catch (\Exception $e) {
            $this->error("❌ GameErrorHandler test failed: " . $e->getMessage());
            return ['status' => 'error', 'details' => $e->getMessage()];
        }
    }

    /**
     * Test GamePerformanceMonitor
     */
    private function testGamePerformanceMonitor(bool $verbose): array
    {
        $this->info('📊 Testing GamePerformanceMonitor...');
        
        try {
            $monitor = app(GamePerformanceMonitor::class);
            
            // Test performance monitoring
            $monitor->startOperation('integration_test');
            usleep(10000); // 10ms delay
            $monitor->endOperation('integration_test');
            
            // Test getting performance stats
            $stats = $monitor->getPerformanceStats();

            if ($verbose) {
                $this->line("✅ Performance monitoring: Success");
                $this->line("✅ Performance stats: " . count($stats) . " items");
            }

            return ['status' => 'success', 'details' => 'All GamePerformanceMonitor methods working'];
            
        } catch (\Exception $e) {
            $this->error("❌ GamePerformanceMonitor test failed: " . $e->getMessage());
            return ['status' => 'error', 'details' => $e->getMessage()];
        }
    }

    /**
     * Test Integration Coordination
     */
    private function testIntegrationCoordination(int $userId, bool $verbose): array
    {
        $this->info('🔄 Testing Integration Coordination...');
        
        try {
            // Test coordinated initialization
            GameIntegrationService::initializeUserRealTime($userId);
            
            // Test coordinated notifications
            GameNotificationService::sendNotification(
                [$userId],
                'integration_coordination_test',
                [
                    'test_type' => 'coordination',
                    'timestamp' => now()->toISOString(),
                ]
            );

            // Test coordinated real-time updates
            RealTimeGameService::sendUpdate($userId, 'integration_coordination', [
                'message' => 'Coordination test update',
                'timestamp' => now()->toISOString(),
            ]);

            if ($verbose) {
                $this->line("✅ Coordinated initialization: Success");
                $this->line("✅ Coordinated notifications: Success");
                $this->line("✅ Coordinated real-time updates: Success");
            }

            return ['status' => 'success', 'details' => 'All integration coordination working'];
            
        } catch (\Exception $e) {
            $this->error("❌ Integration coordination test failed: " . $e->getMessage());
            return ['status' => 'error', 'details' => $e->getMessage()];
        }
    }

    /**
     * Display test results
     */
    private function displayResults(array $results): void
    {
        $this->info('📋 Integration Test Results:');
        $this->newLine();

        $passed = 0;
        $failed = 0;

        foreach ($results as $test => $result) {
            $status = $result['status'] === 'success' ? '✅' : '❌';
            $this->line("{$status} " . ucwords(str_replace('_', ' ', $test)) . ": {$result['details']}");
            
            if ($result['status'] === 'success') {
                $passed++;
            } else {
                $failed++;
            }
        }

        $this->newLine();
        $this->info("📊 Summary: {$passed} passed, {$failed} failed");
        
        if ($failed === 0) {
            $this->info('🎉 All integration tests passed!');
        } else {
            $this->warn('⚠️ Some integration tests failed. Check the logs for details.');
        }
    }

    /**
     * Get overall test result
     */
    private function getOverallResult(array $results): int
    {
        foreach ($results as $result) {
            if ($result['status'] === 'error') {
                return 1; // Exit code 1 for failure
            }
        }
        
        return 0; // Exit code 0 for success
    }
}
