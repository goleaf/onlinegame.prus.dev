<?php

namespace Tests\Unit\Services;

use App\Models\Game\Alliance;
use App\Models\Game\Player;
use App\Models\Game\Report;
use App\Models\Game\Task;
use App\Models\Game\Village;
use App\Models\User;
use App\Services\GameCacheService;
use App\Services\GameErrorHandler;
use App\Services\GameIntegrationService;
use App\Services\GameNotificationService;
use App\Services\GamePerformanceMonitor;
use App\Services\RealTimeGameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameIntegrationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GameIntegrationService $service;

    protected $realTimeService;

    protected $cacheService;

    protected $errorHandler;

    protected $notificationService;

    protected $performanceMonitor;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->realTimeService = $this->mock(RealTimeGameService::class);
        $this->cacheService = $this->mock(GameCacheService::class);
        $this->errorHandler = $this->mock(GameErrorHandler::class);
        $this->notificationService = $this->mock(GameNotificationService::class);
        $this->performanceMonitor = $this->mock(GamePerformanceMonitor::class);

        $this->service = new GameIntegrationService(
            $this->realTimeService,
            $this->cacheService,
            $this->errorHandler,
            $this->notificationService,
            $this->performanceMonitor
        );
    }

    /**
     * @test
     */
    public function it_can_initialize_user_real_time()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $village = Village::factory()->create(['player_id' => $player->id]);

        // Mock the performance monitor
        $this->performanceMonitor->shouldReceive('startTimer')->with('user_initialization')->once();
        $this->performanceMonitor->shouldReceive('endTimer')->with('user_initialization')->once();
        $this->performanceMonitor->shouldReceive('getTimer')->with('user_initialization')->andReturn(100);

        // Mock RealTimeGameService
        RealTimeGameService::shouldReceive('markUserOnline')->with($user->id)->once();
        RealTimeGameService::shouldReceive('sendUpdate')->once();

        // Mock cache service
        $this->cacheService->shouldReceive('cachePlayerData')->with($player)->once();

        // Mock notification service
        $this->notificationService->shouldReceive('getUserNotifications')->with($user->id)->andReturn([]);

        $result = $this->service->initializeUserRealTime($user->id);

        $this->assertTrue($result['success']);
        $this->assertEquals($player->id, $result['player_id']);
        $this->assertEquals(1, $result['villages_count']);
        $this->assertEquals(100, $result['initialization_time']);
    }

    /**
     * @test
     */
    public function it_handles_initialization_error_when_player_not_found()
    {
        $user = User::factory()->create();

        // Mock the performance monitor
        $this->performanceMonitor->shouldReceive('startTimer')->with('user_initialization')->once();

        // Mock error handler
        $this->errorHandler->shouldReceive('handleError')->once();

        $result = $this->service->initializeUserRealTime($user->id);

        $this->assertFalse($result['success']);
        $this->assertStringContains('Player not found', $result['error']);
    }

    /**
     * @test
     */
    public function it_can_deinitialize_user_real_time()
    {
        $user = User::factory()->create();

        // Mock RealTimeGameService
        RealTimeGameService::shouldReceive('markUserOffline')->with($user->id)->once();

        // Mock cache service
        $this->cacheService->shouldReceive('clearPlayerCache')->with($user->id)->once();

        // Mock notification service
        $this->notificationService->shouldReceive('clearUserNotifications')->with($user->id)->once();

        $result = $this->service->deinitializeUserRealTime($user->id);

        $this->assertTrue($result['success']);
        $this->assertEquals('User real-time features deinitialized', $result['message']);
    }

    /**
     * @test
     */
    public function it_can_create_village_with_integration()
    {
        $player = Player::factory()->create();
        $villageData = [
            'player_id' => $player->id,
            'world_id' => 1,
            'name' => 'Test Village',
            'x_coordinate' => 100,
            'y_coordinate' => 200,
            'population' => 1000,
        ];

        // Mock the performance monitor
        $this->performanceMonitor->shouldReceive('startTimer')->with('village_creation')->once();
        $this->performanceMonitor->shouldReceive('endTimer')->with('village_creation')->once();
        $this->performanceMonitor->shouldReceive('getTimer')->with('village_creation')->andReturn(150);

        // Mock RealTimeGameService
        RealTimeGameService::shouldReceive('sendVillageUpdate')->once();

        // Mock notification service
        $this->notificationService->shouldReceive('sendUserNotification')->once();

        // Mock cache service
        $this->cacheService->shouldReceive('cacheVillageData')->once();

        $result = $this->service->createVillageWithIntegration($villageData);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('village_id', $result);
        $this->assertEquals(150, $result['creation_time']);

        // Verify village was created
        $this->assertDatabaseHas('villages', [
            'name' => 'Test Village',
            'player_id' => $player->id,
        ]);
    }

    /**
     * @test
     */
    public function it_can_upgrade_building_with_integration()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $building = \App\Models\Game\Building::factory()->create([
            'village_id' => $village->id,
            'building_type_id' => 1,
            'level' => 5,
        ]);

        // Mock the performance monitor
        $this->performanceMonitor->shouldReceive('startTimer')->with('building_upgrade')->once();
        $this->performanceMonitor->shouldReceive('endTimer')->with('building_upgrade')->once();

        // Mock RealTimeGameService
        RealTimeGameService::shouldReceive('sendBuildingUpdate')->once();

        // Mock notification service
        $this->notificationService->shouldReceive('sendUserNotification')->once();

        $result = $this->service->upgradeBuildingWithIntegration($village->id, 1);

        $this->assertTrue($result['success']);
        $this->assertEquals($building->id, $result['building_id']);
        $this->assertEquals(6, $result['new_level']);
        $this->assertArrayHasKey('completion_time', $result);
    }

    /**
     * @test
     */
    public function it_handles_building_upgrade_error_when_building_not_found()
    {
        $village = Village::factory()->create();

        // Mock the performance monitor
        $this->performanceMonitor->shouldReceive('startTimer')->with('building_upgrade')->once();

        // Mock error handler
        $this->errorHandler->shouldReceive('handleError')->once();

        $result = $this->service->upgradeBuildingWithIntegration($village->id, 999);

        $this->assertFalse($result['success']);
        $this->assertStringContains('Building not found', $result['error']);
    }

    /**
     * @test
     */
    public function it_can_join_alliance_with_integration()
    {
        $player = Player::factory()->create();
        $alliance = Alliance::factory()->create();

        // Mock the performance monitor
        $this->performanceMonitor->shouldReceive('startTimer')->with('alliance_join')->once();
        $this->performanceMonitor->shouldReceive('endTimer')->with('alliance_join')->once();

        // Mock RealTimeGameService
        RealTimeGameService::shouldReceive('sendAllianceUpdate')->once();

        // Mock notification service
        $this->notificationService->shouldReceive('sendUserNotification')->twice();

        $result = $this->service->joinAllianceWithIntegration($player->id, $alliance->id);

        $this->assertTrue($result['success']);
        $this->assertEquals($alliance->id, $result['alliance_id']);
        $this->assertEquals($alliance->name, $result['alliance_name']);

        // Verify player was updated
        $this->assertEquals($alliance->id, $player->fresh()->alliance_id);
    }

    /**
     * @test
     */
    public function it_can_get_game_statistics()
    {
        // Create test data
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $alliance = Alliance::factory()->create();
        $task = Task::factory()->create();
        $report = Report::factory()->create();

        // Mock the performance monitor
        $this->performanceMonitor->shouldReceive('startTimer')->with('game_statistics')->once();
        $this->performanceMonitor->shouldReceive('endTimer')->with('game_statistics')->once();
        $this->performanceMonitor->shouldReceive('getTimer')->with('game_statistics')->andReturn(200);

        // Mock RealTimeGameService
        RealTimeGameService::shouldReceive('getOnlineUsers')->andReturn(5);

        // Mock cache service
        $this->cacheService->shouldReceive('getCacheStats')->andReturn(['hits' => 100, 'misses' => 50]);

        // Mock notification service
        $this->notificationService->shouldReceive('getNotificationStats')->andReturn(['total' => 25]);

        // Mock error handler
        $this->errorHandler->shouldReceive('getErrorStats')->andReturn(['errors' => 0]);

        $result = $this->service->getGameStatistics();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('statistics', $result);
        $this->assertEquals(200, $result['generation_time']);

        $stats = $result['statistics'];
        $this->assertArrayHasKey('players', $stats);
        $this->assertArrayHasKey('villages', $stats);
        $this->assertArrayHasKey('alliances', $stats);
        $this->assertArrayHasKey('tasks', $stats);
        $this->assertArrayHasKey('reports', $stats);
        $this->assertArrayHasKey('performance', $stats);
        $this->assertArrayHasKey('cache', $stats);
        $this->assertArrayHasKey('notifications', $stats);
        $this->assertArrayHasKey('errors', $stats);
    }

    /**
     * @test
     */
    public function it_can_send_system_announcement()
    {
        $title = 'System Maintenance';
        $message = 'The system will be down for maintenance.';
        $priority = 'high';

        // Mock the performance monitor
        $this->performanceMonitor->shouldReceive('startTimer')->with('system_announcement')->once();
        $this->performanceMonitor->shouldReceive('endTimer')->with('system_announcement')->once();

        // Mock RealTimeGameService
        RealTimeGameService::shouldReceive('sendSystemAnnouncement')->with($title, $message, $priority)->once();

        // Mock notification service
        $this->notificationService->shouldReceive('sendSystemNotification')->with($title, $message, $priority)->once();

        $result = $this->service->sendSystemAnnouncement($title, $message, $priority);

        $this->assertTrue($result['success']);
        $this->assertEquals('System announcement sent successfully', $result['message']);
    }

    /**
     * @test
     */
    public function it_can_perform_maintenance()
    {
        // Mock the performance monitor
        $this->performanceMonitor->shouldReceive('startTimer')->with('system_maintenance')->once();
        $this->performanceMonitor->shouldReceive('endTimer')->with('system_maintenance')->once();
        $this->performanceMonitor->shouldReceive('getTimer')->with('system_maintenance')->andReturn(300);

        // Mock cache service
        $this->cacheService->shouldReceive('cleanup')->andReturn(['cleared' => 50]);

        // Mock notification service
        $this->notificationService->shouldReceive('cleanup')->andReturn(25);

        // Mock error handler
        $this->errorHandler->shouldReceive('cleanup')->andReturn(5);

        // Mock RealTimeGameService
        RealTimeGameService::shouldReceive('cleanup')->andReturn(['connections' => 10]);

        $result = $this->service->performMaintenance();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('maintenance', $result);
        $this->assertEquals(300, $result['maintenance_time']);

        $maintenance = $result['maintenance'];
        $this->assertArrayHasKey('cache_cleanup', $maintenance);
        $this->assertArrayHasKey('notification_cleanup', $maintenance);
        $this->assertArrayHasKey('error_cleanup', $maintenance);
        $this->assertArrayHasKey('realtime_cleanup', $maintenance);
    }

    /**
     * @test
     */
    public function it_handles_errors_gracefully()
    {
        $user = User::factory()->create();

        // Mock the performance monitor to throw an exception
        $this->performanceMonitor->shouldReceive('startTimer')->andThrow(new \Exception('Test error'));

        // Mock error handler
        $this->errorHandler->shouldReceive('handleError')->once();

        $result = $this->service->initializeUserRealTime($user->id);

        $this->assertFalse($result['success']);
        $this->assertStringContains('Test error', $result['error']);
    }
}
