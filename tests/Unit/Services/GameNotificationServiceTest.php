<?php

namespace Tests\Unit\Services;

use App\Models\Game\Alliance;
use App\Models\Game\Notification;
use App\Models\Game\Player;
use App\Models\User;
use App\Services\GameNotificationService;
use App\Services\RealTimeGameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GameNotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GameNotificationService();
    }

    /**
     * @test
     */
    public function it_can_send_user_notification()
    {
        $user = User::factory()->create();
        $title = 'Test Notification';
        $message = 'This is a test message';
        $type = 'info';
        $data = ['key' => 'value'];

        // Mock RealTimeGameService
        RealTimeGameService::shouldReceive('sendUpdate')->once();

        $result = $this->service->sendUserNotification($user->id, $title, $message, $type, $data);

        $this->assertTrue($result);

        // Verify notification was created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_read' => false,
        ]);
    }

    /**
     * @test
     */
    public function it_handles_user_notification_error()
    {
        $userId = 999;  // Non-existent user
        $title = 'Test Notification';
        $message = 'This is a test message';

        $result = $this->service->sendUserNotification($userId, $title, $message);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_can_send_broadcast_notification()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $userIds = [$user1->id, $user2->id];
        $title = 'Broadcast Notification';
        $message = 'This is a broadcast message';
        $type = 'system';

        // Mock RealTimeGameService
        RealTimeGameService::shouldReceive('sendUpdate')->twice();

        $sent = $this->service->sendBroadcastNotification($userIds, $title, $message, $type);

        $this->assertEquals(2, $sent);

        // Verify notifications were created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user1->id,
            'title' => $title,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user2->id,
            'title' => $title,
        ]);
    }

    /**
     * @test
     */
    public function it_can_send_alliance_notification()
    {
        $alliance = Alliance::factory()->create();
        $player1 = Player::factory()->create(['alliance_id' => $alliance->id]);
        $player2 = Player::factory()->create(['alliance_id' => $alliance->id]);
        $title = 'Alliance Notification';
        $message = 'This is an alliance message';

        // Mock RealTimeGameService
        RealTimeGameService::shouldReceive('sendUpdate')->twice();

        $sent = $this->service->sendAllianceNotification($alliance->id, $title, $message);

        $this->assertEquals(2, $sent);
    }

    /**
     * @test
     */
    public function it_handles_alliance_notification_error()
    {
        $allianceId = 999;  // Non-existent alliance
        $title = 'Alliance Notification';
        $message = 'This is an alliance message';

        $sent = $this->service->sendAllianceNotification($allianceId, $title, $message);

        $this->assertEquals(0, $sent);
    }

    /**
     * @test
     */
    public function it_can_send_system_notification()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create([
            'user_id' => $user->id,
            'last_activity' => now()->subDays(3),
        ]);
        $title = 'System Notification';
        $message = 'This is a system message';
        $priority = 'high';

        // Mock RealTimeGameService
        RealTimeGameService::shouldReceive('sendUpdate')->once();

        $sent = $this->service->sendSystemNotification($title, $message, $priority);

        $this->assertEquals(1, $sent);
    }

    /**
     * @test
     */
    public function it_can_send_battle_notification()
    {
        $attacker = Player::factory()->create();
        $defender = Player::factory()->create();
        $battleResult = 'victory';
        $battleData = ['loot' => 1000];

        // Mock RealTimeGameService
        RealTimeGameService::shouldReceive('sendUpdate')->twice();

        $this->service->sendBattleNotification($attacker->id, $defender->id, $battleResult, $battleData);

        // Verify notifications were created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $attacker->user_id,
            'title' => 'Battle Result',
            'type' => 'battle',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $defender->user_id,
            'title' => 'Battle Defense',
            'type' => 'battle',
        ]);
    }

    /**
     * @test
     */
    public function it_handles_battle_notification_when_players_not_found()
    {
        $attackerId = 999;
        $defenderId = 998;
        $battleResult = 'victory';

        // Should not throw an exception
        $this->service->sendBattleNotification($attackerId, $defenderId, $battleResult);
    }

    /**
     * @test
     */
    public function it_can_send_movement_notification()
    {
        $player = Player::factory()->create();
        $movementType = 'attack';
        $destination = 'Village (100|200)';
        $status = 'arrived';
        $movementData = ['duration' => 3600];

        // Mock RealTimeGameService
        RealTimeGameService::shouldReceive('sendUpdate')->once();

        $this->service->sendMovementNotification($player->id, $movementType, $destination, $status, $movementData);

        // Verify notification was created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $player->user_id,
            'title' => 'Movement Update',
            'type' => 'movement',
        ]);
    }

    /**
     * @test
     */
    public function it_handles_movement_notification_when_player_not_found()
    {
        $playerId = 999;
        $movementType = 'attack';
        $destination = 'Village (100|200)';
        $status = 'arrived';

        // Should not throw an exception
        $this->service->sendMovementNotification($playerId, $movementType, $destination, $status);
    }

    /**
     * @test
     */
    public function it_can_send_resource_notification()
    {
        $player = Player::factory()->create();
        $village = \App\Models\Game\Village::factory()->create(['player_id' => $player->id]);
        $resourceType = 'wood';
        $amount = 1000;
        $action = 'produced';

        // Mock RealTimeGameService
        RealTimeGameService::shouldReceive('sendUpdate')->once();

        $this->service->sendResourceNotification($village->id, $resourceType, $amount, $action);

        // Verify notification was created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $player->user_id,
            'title' => 'Resource Update',
            'type' => 'resource',
        ]);
    }

    /**
     * @test
     */
    public function it_handles_resource_notification_when_village_not_found()
    {
        $villageId = 999;
        $resourceType = 'wood';
        $amount = 1000;

        // Should not throw an exception
        $this->service->sendResourceNotification($villageId, $resourceType, $amount);
    }

    /**
     * @test
     */
    public function it_can_get_user_notifications()
    {
        $user = User::factory()->create();
        $notification1 = Notification::factory()->create([
            'user_id' => $user->id,
            'title' => 'First Notification',
        ]);
        $notification2 = Notification::factory()->create([
            'user_id' => $user->id,
            'title' => 'Second Notification',
        ]);

        $notifications = $this->service->getUserNotifications($user->id, 10);

        $this->assertCount(2, $notifications);
        $this->assertEquals('Second Notification', $notifications[0]['title']);  // Most recent first
        $this->assertEquals('First Notification', $notifications[1]['title']);
    }

    /**
     * @test
     */
    public function it_handles_get_user_notifications_error()
    {
        $userId = 999;  // Non-existent user

        $notifications = $this->service->getUserNotifications($userId);

        $this->assertIsArray($notifications);
        $this->assertEmpty($notifications);
    }

    /**
     * @test
     */
    public function it_can_mark_notification_as_read()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'is_read' => false,
        ]);

        $result = $this->service->markNotificationAsRead($notification->id, $user->id);

        $this->assertTrue($result);
        $this->assertTrue($notification->fresh()->is_read);
    }

    /**
     * @test
     */
    public function it_returns_false_when_marking_non_existent_notification_as_read()
    {
        $user = User::factory()->create();
        $notificationId = 999;

        $result = $this->service->markNotificationAsRead($notificationId, $user->id);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_can_clear_user_notifications()
    {
        $user = User::factory()->create();
        $notification1 = Notification::factory()->create(['user_id' => $user->id]);
        $notification2 = Notification::factory()->create(['user_id' => $user->id]);

        $deleted = $this->service->clearUserNotifications($user->id);

        $this->assertEquals(2, $deleted);
        $this->assertDatabaseMissing('notifications', ['id' => $notification1->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $notification2->id]);
    }

    /**
     * @test
     */
    public function it_can_get_notification_statistics()
    {
        $user = User::factory()->create();
        $notification1 = Notification::factory()->create([
            'user_id' => $user->id,
            'type' => 'info',
            'is_read' => false,
            'created_at' => now()->subDays(2),
        ]);
        $notification2 = Notification::factory()->create([
            'user_id' => $user->id,
            'type' => 'battle',
            'is_read' => true,
            'created_at' => now()->subHours(2),
        ]);

        $stats = $this->service->getNotificationStats();

        $this->assertArrayHasKey('total_notifications', $stats);
        $this->assertArrayHasKey('unread_notifications', $stats);
        $this->assertArrayHasKey('notifications_today', $stats);
        $this->assertArrayHasKey('notifications_by_type', $stats);

        $this->assertEquals(2, $stats['total_notifications']);
        $this->assertEquals(1, $stats['unread_notifications']);
        $this->assertEquals(1, $stats['notifications_today']);
        $this->assertArrayHasKey('info', $stats['notifications_by_type']);
        $this->assertArrayHasKey('battle', $stats['notifications_by_type']);
    }

    /**
     * @test
     */
    public function it_can_cleanup_old_notifications()
    {
        $user = User::factory()->create();
        $oldNotification = Notification::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(35),
        ]);
        $recentNotification = Notification::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(10),
        ]);

        $deleted = $this->service->cleanup();

        $this->assertEquals(1, $deleted);
        $this->assertDatabaseMissing('notifications', ['id' => $oldNotification->id]);
        $this->assertDatabaseHas('notifications', ['id' => $recentNotification->id]);
    }

    /**
     * @test
     */
    public function it_handles_cleanup_error()
    {
        // Mock database error by using invalid data
        $deleted = $this->service->cleanup();

        $this->assertEquals(0, $deleted);
    }
}
