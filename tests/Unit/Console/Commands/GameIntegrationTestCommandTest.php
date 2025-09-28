<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\GameIntegrationTestCommand;
use App\Services\GameCacheService;
use App\Services\GameErrorHandler;
use App\Services\GameIntegrationService;
use App\Services\GameNotificationService;
use App\Services\GamePerformanceMonitor;
use App\Services\RealTimeGameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameIntegrationTestCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_run_integration_test_with_default_options()
    {
        $this->mock(GameIntegrationService::class, function ($mock): void {
            $mock->shouldReceive('initializeUserRealTime')->once();
            $mock->shouldReceive('getGameStatisticsWithRealTime')->andReturn([
                'total_players' => 100,
                'active_players' => 50,
                'total_villages' => 200,
            ]);
            $mock->shouldReceive('sendSystemAnnouncement')->once();
        });

        $this->mock(GameNotificationService::class, function ($mock): void {
            $mock->shouldReceive('sendNotification')->once();
            $mock->shouldReceive('sendSystemAnnouncement')->once();
            $mock->shouldReceive('getUserNotifications')->andReturn([]);
        });

        $this->mock(RealTimeGameService::class, function ($mock): void {
            $mock->shouldReceive('markUserOnline')->once();
            $mock->shouldReceive('sendUpdate')->once();
            $mock->shouldReceive('getRealTimeStats')->andReturn([
                'online_users' => 25,
                'active_connections' => 30,
            ]);
        });

        $this->mock(GameCacheService::class, function ($mock): void {
            $mock->shouldReceive('setGameData')->once();
            $mock->shouldReceive('getGameData')->andReturn(['test' => 'data']);
            $mock->shouldReceive('getCacheStats')->andReturn([
                'hits' => 100,
                'misses' => 10,
            ]);
        });

        $this->mock(GameErrorHandler::class, function ($mock): void {
            $mock->shouldReceive('logGameAction')->once();
            $mock->shouldReceive('handleGameError')->once();
        });

        $this->mock(GamePerformanceMonitor::class, function ($mock): void {
            $mock->shouldReceive('startOperation')->once();
            $mock->shouldReceive('endOperation')->once();
            $mock->shouldReceive('getPerformanceStats')->andReturn([
                'total_operations' => 1,
                'average_time' => 0.01,
            ]);
        });

        $this
            ->artisan('game:integration-test')
            ->expectsOutput('🎮 Starting Game Integration Test...')
            ->expectsOutput('🔧 Testing GameIntegrationService...')
            ->expectsOutput('🔔 Testing GameNotificationService...')
            ->expectsOutput('⚡ Testing RealTimeGameService...')
            ->expectsOutput('💾 Testing GameCacheService...')
            ->expectsOutput('🛠️ Testing GameErrorHandler...')
            ->expectsOutput('📊 Testing GamePerformanceMonitor...')
            ->expectsOutput('🔄 Testing Integration Coordination...')
            ->expectsOutput('📋 Integration Test Results:')
            ->expectsOutput('✅ Game Integration: All GameIntegrationService methods working')
            ->expectsOutput('✅ Game Notifications: All GameNotificationService methods working')
            ->expectsOutput('✅ Realtime Service: All RealTimeGameService methods working')
            ->expectsOutput('✅ Cache Service: All GameCacheService methods working')
            ->expectsOutput('✅ Error Handler: All GameErrorHandler methods working')
            ->expectsOutput('✅ Performance Monitor: All GamePerformanceMonitor methods working')
            ->expectsOutput('✅ Integration Coordination: All integration coordination working')
            ->expectsOutput('📊 Summary: 7 passed, 0 failed')
            ->expectsOutput('🎉 All integration tests passed!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_integration_test_with_detailed_output()
    {
        $this->mock(GameIntegrationService::class, function ($mock): void {
            $mock->shouldReceive('initializeUserRealTime')->once();
            $mock->shouldReceive('getGameStatisticsWithRealTime')->andReturn([
                'total_players' => 100,
                'active_players' => 50,
                'total_villages' => 200,
            ]);
            $mock->shouldReceive('sendSystemAnnouncement')->once();
        });

        $this->mock(GameNotificationService::class, function ($mock): void {
            $mock->shouldReceive('sendNotification')->once();
            $mock->shouldReceive('sendSystemAnnouncement')->once();
            $mock->shouldReceive('getUserNotifications')->andReturn([]);
        });

        $this->mock(RealTimeGameService::class, function ($mock): void {
            $mock->shouldReceive('markUserOnline')->once();
            $mock->shouldReceive('sendUpdate')->once();
            $mock->shouldReceive('getRealTimeStats')->andReturn([
                'online_users' => 25,
                'active_connections' => 30,
            ]);
        });

        $this->mock(GameCacheService::class, function ($mock): void {
            $mock->shouldReceive('setGameData')->once();
            $mock->shouldReceive('getGameData')->andReturn(['test' => 'data']);
            $mock->shouldReceive('getCacheStats')->andReturn([
                'hits' => 100,
                'misses' => 10,
            ]);
        });

        $this->mock(GameErrorHandler::class, function ($mock): void {
            $mock->shouldReceive('logGameAction')->once();
            $mock->shouldReceive('handleGameError')->once();
        });

        $this->mock(GamePerformanceMonitor::class, function ($mock): void {
            $mock->shouldReceive('startOperation')->once();
            $mock->shouldReceive('endOperation')->once();
            $mock->shouldReceive('getPerformanceStats')->andReturn([
                'total_operations' => 1,
                'average_time' => 0.01,
            ]);
        });

        $this
            ->artisan('game:integration-test', ['--detailed' => true])
            ->expectsOutput('🎮 Starting Game Integration Test...')
            ->expectsOutput('🔧 Testing GameIntegrationService...')
            ->expectsOutput('✅ User initialization: Success')
            ->expectsOutput('✅ Game statistics: 3 items')
            ->expectsOutput('✅ System announcement: Success')
            ->expectsOutput('🔔 Testing GameNotificationService...')
            ->expectsOutput('✅ User notification: Success')
            ->expectsOutput('✅ System announcement: Success')
            ->expectsOutput('✅ Notifications retrieval: 0 notifications')
            ->expectsOutput('⚡ Testing RealTimeGameService...')
            ->expectsOutput('✅ User online marking: Success')
            ->expectsOutput('✅ Update sending: Success')
            ->expectsOutput('✅ Real-time stats: 2 items')
            ->expectsOutput('💾 Testing GameCacheService...')
            ->expectsOutput('✅ Cache setting: Success')
            ->expectsOutput('✅ Cache getting: Success')
            ->expectsOutput('✅ Cache stats: 2 items')
            ->expectsOutput('🛠️ Testing GameErrorHandler...')
            ->expectsOutput('✅ Error logging: Success')
            ->expectsOutput('✅ Error handling: Success')
            ->expectsOutput('📊 Testing GamePerformanceMonitor...')
            ->expectsOutput('✅ Performance monitoring: Success')
            ->expectsOutput('✅ Performance stats: 2 items')
            ->expectsOutput('🔄 Testing Integration Coordination...')
            ->expectsOutput('✅ Coordinated initialization: Success')
            ->expectsOutput('✅ Coordinated notifications: Success')
            ->expectsOutput('✅ Coordinated real-time updates: Success')
            ->expectsOutput('📋 Integration Test Results:')
            ->expectsOutput('✅ Game Integration: All GameIntegrationService methods working')
            ->expectsOutput('✅ Game Notifications: All GameNotificationService methods working')
            ->expectsOutput('✅ Realtime Service: All RealTimeGameService methods working')
            ->expectsOutput('✅ Cache Service: All GameCacheService methods working')
            ->expectsOutput('✅ Error Handler: All GameErrorHandler methods working')
            ->expectsOutput('✅ Performance Monitor: All GamePerformanceMonitor methods working')
            ->expectsOutput('✅ Integration Coordination: All integration coordination working')
            ->expectsOutput('📊 Summary: 7 passed, 0 failed')
            ->expectsOutput('🎉 All integration tests passed!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_integration_test_with_custom_user_id()
    {
        $this->mock(GameIntegrationService::class, function ($mock): void {
            $mock->shouldReceive('initializeUserRealTime')->with(5)->once();
            $mock->shouldReceive('getGameStatisticsWithRealTime')->andReturn([
                'total_players' => 100,
                'active_players' => 50,
                'total_villages' => 200,
            ]);
            $mock->shouldReceive('sendSystemAnnouncement')->once();
        });

        $this->mock(GameNotificationService::class, function ($mock): void {
            $mock->shouldReceive('sendNotification')->with([5], \Mockery::any(), \Mockery::any(), \Mockery::any())->once();
            $mock->shouldReceive('sendSystemAnnouncement')->once();
            $mock->shouldReceive('getUserNotifications')->with(5)->andReturn([]);
        });

        $this->mock(RealTimeGameService::class, function ($mock): void {
            $mock->shouldReceive('markUserOnline')->with(5)->once();
            $mock->shouldReceive('sendUpdate')->with(5, \Mockery::any(), \Mockery::any())->once();
            $mock->shouldReceive('getRealTimeStats')->andReturn([
                'online_users' => 25,
                'active_connections' => 30,
            ]);
        });

        $this->mock(GameCacheService::class, function ($mock): void {
            $mock->shouldReceive('setGameData')->once();
            $mock->shouldReceive('getGameData')->andReturn(['test' => 'data']);
            $mock->shouldReceive('getCacheStats')->andReturn([
                'hits' => 100,
                'misses' => 10,
            ]);
        });

        $this->mock(GameErrorHandler::class, function ($mock): void {
            $mock->shouldReceive('logGameAction')->once();
            $mock->shouldReceive('handleGameError')->once();
        });

        $this->mock(GamePerformanceMonitor::class, function ($mock): void {
            $mock->shouldReceive('startOperation')->once();
            $mock->shouldReceive('endOperation')->once();
            $mock->shouldReceive('getPerformanceStats')->andReturn([
                'total_operations' => 1,
                'average_time' => 0.01,
            ]);
        });

        $this
            ->artisan('game:integration-test', ['--user-id' => 5])
            ->expectsOutput('🎮 Starting Game Integration Test...')
            ->expectsOutput('🔧 Testing GameIntegrationService...')
            ->expectsOutput('🔔 Testing GameNotificationService...')
            ->expectsOutput('⚡ Testing RealTimeGameService...')
            ->expectsOutput('💾 Testing GameCacheService...')
            ->expectsOutput('🛠️ Testing GameErrorHandler...')
            ->expectsOutput('📊 Testing GamePerformanceMonitor...')
            ->expectsOutput('🔄 Testing Integration Coordination...')
            ->expectsOutput('📋 Integration Test Results:')
            ->expectsOutput('✅ Game Integration: All GameIntegrationService methods working')
            ->expectsOutput('✅ Game Notifications: All GameNotificationService methods working')
            ->expectsOutput('✅ Realtime Service: All RealTimeGameService methods working')
            ->expectsOutput('✅ Cache Service: All GameCacheService methods working')
            ->expectsOutput('✅ Error Handler: All GameErrorHandler methods working')
            ->expectsOutput('✅ Performance Monitor: All GamePerformanceMonitor methods working')
            ->expectsOutput('✅ Integration Coordination: All integration coordination working')
            ->expectsOutput('📊 Summary: 7 passed, 0 failed')
            ->expectsOutput('🎉 All integration tests passed!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_game_integration_service_failure()
    {
        $this->mock(GameIntegrationService::class, function ($mock): void {
            $mock
                ->shouldReceive('initializeUserRealTime')
                ->andThrow(new \Exception('Game integration service failed'));
        });

        $this
            ->artisan('game:integration-test')
            ->expectsOutput('🎮 Starting Game Integration Test...')
            ->expectsOutput('🔧 Testing GameIntegrationService...')
            ->expectsOutput('❌ GameIntegrationService test failed: Game integration service failed')
            ->expectsOutput('📋 Integration Test Results:')
            ->expectsOutput('❌ Game Integration: Game integration service failed')
            ->expectsOutput('📊 Summary: 0 passed, 1 failed')
            ->expectsOutput('⚠️ Some integration tests failed. Check the logs for details.')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_game_notification_service_failure()
    {
        $this->mock(GameIntegrationService::class, function ($mock): void {
            $mock->shouldReceive('initializeUserRealTime')->once();
            $mock->shouldReceive('getGameStatisticsWithRealTime')->andReturn([]);
            $mock->shouldReceive('sendSystemAnnouncement')->once();
        });

        $this->mock(GameNotificationService::class, function ($mock): void {
            $mock
                ->shouldReceive('sendNotification')
                ->andThrow(new \Exception('Game notification service failed'));
        });

        $this
            ->artisan('game:integration-test')
            ->expectsOutput('🎮 Starting Game Integration Test...')
            ->expectsOutput('🔧 Testing GameIntegrationService...')
            ->expectsOutput('🔔 Testing GameNotificationService...')
            ->expectsOutput('❌ GameNotificationService test failed: Game notification service failed')
            ->expectsOutput('📋 Integration Test Results:')
            ->expectsOutput('✅ Game Integration: All GameIntegrationService methods working')
            ->expectsOutput('❌ Game Notifications: Game notification service failed')
            ->expectsOutput('📊 Summary: 1 passed, 1 failed')
            ->expectsOutput('⚠️ Some integration tests failed. Check the logs for details.')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_realtime_game_service_failure()
    {
        $this->mock(GameIntegrationService::class, function ($mock): void {
            $mock->shouldReceive('initializeUserRealTime')->once();
            $mock->shouldReceive('getGameStatisticsWithRealTime')->andReturn([]);
            $mock->shouldReceive('sendSystemAnnouncement')->once();
        });

        $this->mock(GameNotificationService::class, function ($mock): void {
            $mock->shouldReceive('sendNotification')->once();
            $mock->shouldReceive('sendSystemAnnouncement')->once();
            $mock->shouldReceive('getUserNotifications')->andReturn([]);
        });

        $this->mock(RealTimeGameService::class, function ($mock): void {
            $mock
                ->shouldReceive('markUserOnline')
                ->andThrow(new \Exception('Real-time game service failed'));
        });

        $this
            ->artisan('game:integration-test')
            ->expectsOutput('🎮 Starting Game Integration Test...')
            ->expectsOutput('🔧 Testing GameIntegrationService...')
            ->expectsOutput('🔔 Testing GameNotificationService...')
            ->expectsOutput('⚡ Testing RealTimeGameService...')
            ->expectsOutput('❌ RealTimeGameService test failed: Real-time game service failed')
            ->expectsOutput('📋 Integration Test Results:')
            ->expectsOutput('✅ Game Integration: All GameIntegrationService methods working')
            ->expectsOutput('✅ Game Notifications: All GameNotificationService methods working')
            ->expectsOutput('❌ Realtime Service: Real-time game service failed')
            ->expectsOutput('📊 Summary: 2 passed, 1 failed')
            ->expectsOutput('⚠️ Some integration tests failed. Check the logs for details.')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_game_cache_service_failure()
    {
        $this->mock(GameIntegrationService::class, function ($mock): void {
            $mock->shouldReceive('initializeUserRealTime')->once();
            $mock->shouldReceive('getGameStatisticsWithRealTime')->andReturn([]);
            $mock->shouldReceive('sendSystemAnnouncement')->once();
        });

        $this->mock(GameNotificationService::class, function ($mock): void {
            $mock->shouldReceive('sendNotification')->once();
            $mock->shouldReceive('sendSystemAnnouncement')->once();
            $mock->shouldReceive('getUserNotifications')->andReturn([]);
        });

        $this->mock(RealTimeGameService::class, function ($mock): void {
            $mock->shouldReceive('markUserOnline')->once();
            $mock->shouldReceive('sendUpdate')->once();
            $mock->shouldReceive('getRealTimeStats')->andReturn([]);
        });

        $this->mock(GameCacheService::class, function ($mock): void {
            $mock
                ->shouldReceive('setGameData')
                ->andThrow(new \Exception('Game cache service failed'));
        });

        $this
            ->artisan('game:integration-test')
            ->expectsOutput('🎮 Starting Game Integration Test...')
            ->expectsOutput('🔧 Testing GameIntegrationService...')
            ->expectsOutput('🔔 Testing GameNotificationService...')
            ->expectsOutput('⚡ Testing RealTimeGameService...')
            ->expectsOutput('💾 Testing GameCacheService...')
            ->expectsOutput('❌ GameCacheService test failed: Game cache service failed')
            ->expectsOutput('📋 Integration Test Results:')
            ->expectsOutput('✅ Game Integration: All GameIntegrationService methods working')
            ->expectsOutput('✅ Game Notifications: All GameNotificationService methods working')
            ->expectsOutput('✅ Realtime Service: All RealTimeGameService methods working')
            ->expectsOutput('❌ Cache Service: Game cache service failed')
            ->expectsOutput('📊 Summary: 3 passed, 1 failed')
            ->expectsOutput('⚠️ Some integration tests failed. Check the logs for details.')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_game_error_handler_failure()
    {
        $this->mock(GameIntegrationService::class, function ($mock): void {
            $mock->shouldReceive('initializeUserRealTime')->once();
            $mock->shouldReceive('getGameStatisticsWithRealTime')->andReturn([]);
            $mock->shouldReceive('sendSystemAnnouncement')->once();
        });

        $this->mock(GameNotificationService::class, function ($mock): void {
            $mock->shouldReceive('sendNotification')->once();
            $mock->shouldReceive('sendSystemAnnouncement')->once();
            $mock->shouldReceive('getUserNotifications')->andReturn([]);
        });

        $this->mock(RealTimeGameService::class, function ($mock): void {
            $mock->shouldReceive('markUserOnline')->once();
            $mock->shouldReceive('sendUpdate')->once();
            $mock->shouldReceive('getRealTimeStats')->andReturn([]);
        });

        $this->mock(GameCacheService::class, function ($mock): void {
            $mock->shouldReceive('setGameData')->once();
            $mock->shouldReceive('getGameData')->andReturn(['test' => 'data']);
            $mock->shouldReceive('getCacheStats')->andReturn([]);
        });

        $this->mock(GameErrorHandler::class, function ($mock): void {
            $mock
                ->shouldReceive('logGameAction')
                ->andThrow(new \Exception('Game error handler failed'));
        });

        $this
            ->artisan('game:integration-test')
            ->expectsOutput('🎮 Starting Game Integration Test...')
            ->expectsOutput('🔧 Testing GameIntegrationService...')
            ->expectsOutput('🔔 Testing GameNotificationService...')
            ->expectsOutput('⚡ Testing RealTimeGameService...')
            ->expectsOutput('💾 Testing GameCacheService...')
            ->expectsOutput('🛠️ Testing GameErrorHandler...')
            ->expectsOutput('❌ GameErrorHandler test failed: Game error handler failed')
            ->expectsOutput('📋 Integration Test Results:')
            ->expectsOutput('✅ Game Integration: All GameIntegrationService methods working')
            ->expectsOutput('✅ Game Notifications: All GameNotificationService methods working')
            ->expectsOutput('✅ Realtime Service: All RealTimeGameService methods working')
            ->expectsOutput('✅ Cache Service: All GameCacheService methods working')
            ->expectsOutput('❌ Error Handler: Game error handler failed')
            ->expectsOutput('📊 Summary: 4 passed, 1 failed')
            ->expectsOutput('⚠️ Some integration tests failed. Check the logs for details.')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_game_performance_monitor_failure()
    {
        $this->mock(GameIntegrationService::class, function ($mock): void {
            $mock->shouldReceive('initializeUserRealTime')->once();
            $mock->shouldReceive('getGameStatisticsWithRealTime')->andReturn([]);
            $mock->shouldReceive('sendSystemAnnouncement')->once();
        });

        $this->mock(GameNotificationService::class, function ($mock): void {
            $mock->shouldReceive('sendNotification')->once();
            $mock->shouldReceive('sendSystemAnnouncement')->once();
            $mock->shouldReceive('getUserNotifications')->andReturn([]);
        });

        $this->mock(RealTimeGameService::class, function ($mock): void {
            $mock->shouldReceive('markUserOnline')->once();
            $mock->shouldReceive('sendUpdate')->once();
            $mock->shouldReceive('getRealTimeStats')->andReturn([]);
        });

        $this->mock(GameCacheService::class, function ($mock): void {
            $mock->shouldReceive('setGameData')->once();
            $mock->shouldReceive('getGameData')->andReturn(['test' => 'data']);
            $mock->shouldReceive('getCacheStats')->andReturn([]);
        });

        $this->mock(GameErrorHandler::class, function ($mock): void {
            $mock->shouldReceive('logGameAction')->once();
            $mock->shouldReceive('handleGameError')->once();
        });

        $this->mock(GamePerformanceMonitor::class, function ($mock): void {
            $mock
                ->shouldReceive('startOperation')
                ->andThrow(new \Exception('Game performance monitor failed'));
        });

        $this
            ->artisan('game:integration-test')
            ->expectsOutput('🎮 Starting Game Integration Test...')
            ->expectsOutput('🔧 Testing GameIntegrationService...')
            ->expectsOutput('🔔 Testing GameNotificationService...')
            ->expectsOutput('⚡ Testing RealTimeGameService...')
            ->expectsOutput('💾 Testing GameCacheService...')
            ->expectsOutput('🛠️ Testing GameErrorHandler...')
            ->expectsOutput('📊 Testing GamePerformanceMonitor...')
            ->expectsOutput('❌ GamePerformanceMonitor test failed: Game performance monitor failed')
            ->expectsOutput('📋 Integration Test Results:')
            ->expectsOutput('✅ Game Integration: All GameIntegrationService methods working')
            ->expectsOutput('✅ Game Notifications: All GameNotificationService methods working')
            ->expectsOutput('✅ Realtime Service: All RealTimeGameService methods working')
            ->expectsOutput('✅ Cache Service: All GameCacheService methods working')
            ->expectsOutput('✅ Error Handler: All GameErrorHandler methods working')
            ->expectsOutput('❌ Performance Monitor: Game performance monitor failed')
            ->expectsOutput('📊 Summary: 5 passed, 1 failed')
            ->expectsOutput('⚠️ Some integration tests failed. Check the logs for details.')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_integration_coordination_failure()
    {
        $this->mock(GameIntegrationService::class, function ($mock): void {
            $mock->shouldReceive('initializeUserRealTime')->twice();
            $mock->shouldReceive('getGameStatisticsWithRealTime')->andReturn([]);
            $mock->shouldReceive('sendSystemAnnouncement')->once();
        });

        $this->mock(GameNotificationService::class, function ($mock): void {
            $mock->shouldReceive('sendNotification')->twice();
            $mock->shouldReceive('sendSystemAnnouncement')->once();
            $mock->shouldReceive('getUserNotifications')->andReturn([]);
        });

        $this->mock(RealTimeGameService::class, function ($mock): void {
            $mock->shouldReceive('markUserOnline')->once();
            $mock->shouldReceive('sendUpdate')->twice();
            $mock->shouldReceive('getRealTimeStats')->andReturn([]);
        });

        $this->mock(GameCacheService::class, function ($mock): void {
            $mock->shouldReceive('setGameData')->once();
            $mock->shouldReceive('getGameData')->andReturn(['test' => 'data']);
            $mock->shouldReceive('getCacheStats')->andReturn([]);
        });

        $this->mock(GameErrorHandler::class, function ($mock): void {
            $mock->shouldReceive('logGameAction')->once();
            $mock->shouldReceive('handleGameError')->once();
        });

        $this->mock(GamePerformanceMonitor::class, function ($mock): void {
            $mock->shouldReceive('startOperation')->once();
            $mock->shouldReceive('endOperation')->once();
            $mock->shouldReceive('getPerformanceStats')->andReturn([]);
        });

        $this->mock(RealTimeGameService::class, function ($mock): void {
            $mock->shouldReceive('markUserOnline')->once();
            $mock->shouldReceive('sendUpdate')->once();
            $mock->shouldReceive('getRealTimeStats')->andReturn([]);
            $mock
                ->shouldReceive('sendUpdate')
                ->andThrow(new \Exception('Integration coordination failed'));
        });

        $this
            ->artisan('game:integration-test')
            ->expectsOutput('🎮 Starting Game Integration Test...')
            ->expectsOutput('🔧 Testing GameIntegrationService...')
            ->expectsOutput('🔔 Testing GameNotificationService...')
            ->expectsOutput('⚡ Testing RealTimeGameService...')
            ->expectsOutput('💾 Testing GameCacheService...')
            ->expectsOutput('🛠️ Testing GameErrorHandler...')
            ->expectsOutput('📊 Testing GamePerformanceMonitor...')
            ->expectsOutput('🔄 Testing Integration Coordination...')
            ->expectsOutput('❌ Integration coordination test failed: Integration coordination failed')
            ->expectsOutput('📋 Integration Test Results:')
            ->expectsOutput('✅ Game Integration: All GameIntegrationService methods working')
            ->expectsOutput('✅ Game Notifications: All GameNotificationService methods working')
            ->expectsOutput('✅ Realtime Service: All RealTimeGameService methods working')
            ->expectsOutput('✅ Cache Service: All GameCacheService methods working')
            ->expectsOutput('✅ Error Handler: All GameErrorHandler methods working')
            ->expectsOutput('✅ Performance Monitor: All GamePerformanceMonitor methods working')
            ->expectsOutput('❌ Integration Coordination: Integration coordination failed')
            ->expectsOutput('📊 Summary: 6 passed, 1 failed')
            ->expectsOutput('⚠️ Some integration tests failed. Check the logs for details.')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new GameIntegrationTestCommand();
        $this->assertEquals('game:integration-test', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new GameIntegrationTestCommand();
        $this->assertEquals('Test all game integration services and components', $command->getDescription());
    }
}
