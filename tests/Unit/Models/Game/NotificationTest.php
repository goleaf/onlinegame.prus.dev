<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Notification;
use App\Models\Game\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected Notification $notification;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notification = new Notification();
    }

    /**
     * @test
     */
    public function it_can_create_notification()
    {
        $player = Player::factory()->create();

        $notification = Notification::create([
            'player_id' => $player->id,
            'type' => 'battle',
            'title' => 'Battle Result',
            'message' => 'Your attack was successful!',
            'data' => ['battle_id' => 123, 'result' => 'victory'],
            'priority' => 'high',
            'read_at' => null,
            'sent_at' => now(),
        ]);

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($player->id, $notification->player_id);
        $this->assertEquals('battle', $notification->type);
        $this->assertEquals('Battle Result', $notification->title);
        $this->assertEquals('Your attack was successful!', $notification->message);
        $this->assertEquals('high', $notification->priority);
    }

    /**
     * @test
     */
    public function it_casts_data_to_array()
    {
        $notification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Test Notification',
            'message' => 'Test message',
            'data' => ['key' => 'value', 'number' => 123],
        ]);

        $this->assertIsArray($notification->data);
        $this->assertEquals(['key' => 'value', 'number' => 123], $notification->data);
    }

    /**
     * @test
     */
    public function it_casts_datetime_fields()
    {
        $now = now();
        $notification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Test Notification',
            'message' => 'Test message',
            'read_at' => $now,
            'sent_at' => $now->addMinutes(5),
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $notification->read_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $notification->sent_at);
    }

    /**
     * @test
     */
    public function it_belongs_to_player()
    {
        $player = Player::factory()->create();
        $notification = Notification::create([
            'player_id' => $player->id,
            'type' => 'battle',
            'title' => 'Test Notification',
            'message' => 'Test message',
        ]);

        $this->assertInstanceOf(Player::class, $notification->player);
        $this->assertEquals($player->id, $notification->player->id);
    }

    /**
     * @test
     */
    public function it_has_unread_scope()
    {
        Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Unread Notification',
            'message' => 'Test message',
            'read_at' => null,
        ]);

        Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Read Notification',
            'message' => 'Test message',
            'read_at' => now(),
        ]);

        $unreadNotifications = Notification::unread()->get();
        $this->assertCount(1, $unreadNotifications);
        $this->assertEquals('Unread Notification', $unreadNotifications->first()->title);
    }

    /**
     * @test
     */
    public function it_has_read_scope()
    {
        Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Unread Notification',
            'message' => 'Test message',
            'read_at' => null,
        ]);

        Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Read Notification',
            'message' => 'Test message',
            'read_at' => now(),
        ]);

        $readNotifications = Notification::read()->get();
        $this->assertCount(1, $readNotifications);
        $this->assertEquals('Read Notification', $readNotifications->first()->title);
    }

    /**
     * @test
     */
    public function it_has_by_type_scope()
    {
        Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Battle Notification',
            'message' => 'Test message',
        ]);

        Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'alliance',
            'title' => 'Alliance Notification',
            'message' => 'Test message',
        ]);

        $battleNotifications = Notification::byType('battle')->get();
        $this->assertCount(1, $battleNotifications);
        $this->assertEquals('Battle Notification', $battleNotifications->first()->title);
    }

    /**
     * @test
     */
    public function it_has_by_priority_scope()
    {
        Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'High Priority Notification',
            'message' => 'Test message',
            'priority' => 'high',
        ]);

        Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Low Priority Notification',
            'message' => 'Test message',
            'priority' => 'low',
        ]);

        $highPriorityNotifications = Notification::byPriority('high')->get();
        $this->assertCount(1, $highPriorityNotifications);
        $this->assertEquals('High Priority Notification', $highPriorityNotifications->first()->title);
    }

    /**
     * @test
     */
    public function it_has_high_priority_scope()
    {
        Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'High Priority Notification',
            'message' => 'Test message',
            'priority' => 'high',
        ]);

        Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Urgent Notification',
            'message' => 'Test message',
            'priority' => 'urgent',
        ]);

        Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Low Priority Notification',
            'message' => 'Test message',
            'priority' => 'low',
        ]);

        $highPriorityNotifications = Notification::highPriority()->get();
        $this->assertCount(2, $highPriorityNotifications);
    }

    /**
     * @test
     */
    public function it_has_for_player_scope()
    {
        $player1 = Player::factory()->create();
        $player2 = Player::factory()->create();

        Notification::create([
            'player_id' => $player1->id,
            'type' => 'battle',
            'title' => 'Player 1 Notification',
            'message' => 'Test message',
        ]);

        Notification::create([
            'player_id' => $player2->id,
            'type' => 'battle',
            'title' => 'Player 2 Notification',
            'message' => 'Test message',
        ]);

        $player1Notifications = Notification::forPlayer($player1->id)->get();
        $this->assertCount(1, $player1Notifications);
        $this->assertEquals('Player 1 Notification', $player1Notifications->first()->title);
    }

    /**
     * @test
     */
    public function it_has_recent_scope()
    {
        Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Recent Notification',
            'message' => 'Test message',
            'created_at' => now()->subDays(3),
        ]);

        Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Old Notification',
            'message' => 'Test message',
            'created_at' => now()->subDays(10),
        ]);

        $recentNotifications = Notification::recent(7)->get();
        $this->assertCount(1, $recentNotifications);
        $this->assertEquals('Recent Notification', $recentNotifications->first()->title);
    }

    /**
     * @test
     */
    public function it_determines_if_read()
    {
        $readNotification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Read Notification',
            'message' => 'Test message',
            'read_at' => now(),
        ]);

        $unreadNotification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Unread Notification',
            'message' => 'Test message',
            'read_at' => null,
        ]);

        $this->assertTrue($readNotification->isRead());
        $this->assertFalse($unreadNotification->isRead());
    }

    /**
     * @test
     */
    public function it_determines_if_unread()
    {
        $readNotification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Read Notification',
            'message' => 'Test message',
            'read_at' => now(),
        ]);

        $unreadNotification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Unread Notification',
            'message' => 'Test message',
            'read_at' => null,
        ]);

        $this->assertFalse($readNotification->isUnread());
        $this->assertTrue($unreadNotification->isUnread());
    }

    /**
     * @test
     */
    public function it_can_mark_as_read()
    {
        $notification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Test Notification',
            'message' => 'Test message',
            'read_at' => null,
        ]);

        $result = $notification->markAsRead();

        $this->assertTrue($result);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /**
     * @test
     */
    public function it_cannot_mark_already_read_as_read()
    {
        $notification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Test Notification',
            'message' => 'Test message',
            'read_at' => now(),
        ]);

        $result = $notification->markAsRead();

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_can_mark_as_unread()
    {
        $notification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Test Notification',
            'message' => 'Test message',
            'read_at' => now(),
        ]);

        $result = $notification->markAsUnread();

        $this->assertTrue($result);
        $this->assertNull($notification->fresh()->read_at);
    }

    /**
     * @test
     */
    public function it_cannot_mark_already_unread_as_unread()
    {
        $notification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Test Notification',
            'message' => 'Test message',
            'read_at' => null,
        ]);

        $result = $notification->markAsUnread();

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function it_returns_priority_level()
    {
        $lowNotification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Low Priority',
            'message' => 'Test message',
            'priority' => 'low',
        ]);

        $highNotification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'High Priority',
            'message' => 'Test message',
            'priority' => 'high',
        ]);

        $this->assertEquals(1, $lowNotification->getPriorityLevel());
        $this->assertEquals(3, $highNotification->getPriorityLevel());
    }

    /**
     * @test
     */
    public function it_returns_notification_icon()
    {
        $battleNotification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Battle Notification',
            'message' => 'Test message',
        ]);

        $allianceNotification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'alliance',
            'title' => 'Alliance Notification',
            'message' => 'Test message',
        ]);

        $this->assertEquals('⚔️', $battleNotification->getNotificationIcon());
        $this->assertEquals('🤝', $allianceNotification->getNotificationIcon());
    }

    /**
     * @test
     */
    public function it_returns_notification_color()
    {
        $lowNotification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Low Priority',
            'message' => 'Test message',
            'priority' => 'low',
        ]);

        $urgentNotification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Urgent Notification',
            'message' => 'Test message',
            'priority' => 'urgent',
        ]);

        $this->assertEquals('text-gray-500', $lowNotification->getNotificationColor());
        $this->assertEquals('text-red-600', $urgentNotification->getNotificationColor());
    }

    /**
     * @test
     */
    public function it_returns_notification_badge()
    {
        $normalNotification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Normal Notification',
            'message' => 'Test message',
            'priority' => 'normal',
        ]);

        $highNotification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'High Priority',
            'message' => 'Test message',
            'priority' => 'high',
        ]);

        $this->assertEquals('bg-blue-100 text-blue-800', $normalNotification->getNotificationBadge());
        $this->assertEquals('bg-orange-100 text-orange-800', $highNotification->getNotificationBadge());
    }

    /**
     * @test
     */
    public function it_returns_short_message()
    {
        $longMessage = str_repeat('This is a very long message. ', 10);
        $notification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Test Notification',
            'message' => $longMessage,
        ]);

        $shortMessage = $notification->getShortMessage(50);
        $this->assertStringEndsWith('...', $shortMessage);
        $this->assertLessThanOrEqual(53, strlen($shortMessage));  // 50 + '...'
    }

    /**
     * @test
     */
    public function it_returns_formatted_data()
    {
        $notification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Test Notification',
            'message' => 'Test message',
            'data' => [
                'attack_time' => now()->toISOString(),
                'war_duration' => 24,
            ],
        ]);

        $formattedData = $notification->getFormattedData();
        $this->assertIsString($formattedData['attack_time']);
        $this->assertStringEndsWith(' hours', $formattedData['war_duration']);
    }

    /**
     * @test
     */
    public function it_gets_unread_count_for_player()
    {
        $player = Player::factory()->create();

        Notification::create([
            'player_id' => $player->id,
            'type' => 'battle',
            'title' => 'Unread 1',
            'message' => 'Test message',
            'read_at' => null,
        ]);

        Notification::create([
            'player_id' => $player->id,
            'type' => 'battle',
            'title' => 'Unread 2',
            'message' => 'Test message',
            'read_at' => null,
        ]);

        Notification::create([
            'player_id' => $player->id,
            'type' => 'battle',
            'title' => 'Read',
            'message' => 'Test message',
            'read_at' => now(),
        ]);

        $unreadCount = Notification::getUnreadCountForPlayer($player->id);
        $this->assertEquals(2, $unreadCount);
    }

    /**
     * @test
     */
    public function it_gets_recent_notifications_for_player()
    {
        $player = Player::factory()->create();

        Notification::create([
            'player_id' => $player->id,
            'type' => 'battle',
            'title' => 'Recent 1',
            'message' => 'Test message',
            'created_at' => now()->subDays(1),
        ]);

        Notification::create([
            'player_id' => $player->id,
            'type' => 'battle',
            'title' => 'Recent 2',
            'message' => 'Test message',
            'created_at' => now()->subDays(2),
        ]);

        $recentNotifications = Notification::getRecentNotificationsForPlayer($player->id, 1);
        $this->assertCount(1, $recentNotifications);
        $this->assertEquals('Recent 1', $recentNotifications->first()->title);
    }

    /**
     * @test
     */
    public function it_gets_notification_stats()
    {
        Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Unread',
            'message' => 'Test message',
            'read_at' => null,
        ]);

        Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Read',
            'message' => 'Test message',
            'read_at' => now(),
        ]);

        $stats = Notification::getNotificationStats();
        $this->assertEquals(2, $stats['total']);
        $this->assertEquals(1, $stats['unread']);
        $this->assertEquals(1, $stats['read']);
        $this->assertArrayHasKey('by_type', $stats);
        $this->assertArrayHasKey('by_priority', $stats);
    }

    /**
     * @test
     */
    public function it_generates_reference_number()
    {
        $notification = Notification::create([
            'player_id' => Player::factory()->create()->id,
            'type' => 'battle',
            'title' => 'Test Notification',
            'message' => 'Test message',
        ]);

        $this->assertNotNull($notification->reference_number);
        $this->assertStringStartsWith('NTF-', $notification->reference_number);
    }

    /**
     * @test
     */
    public function it_can_be_filled_with_mass_assignment()
    {
        $data = [
            'player_id' => Player::factory()->create()->id,
            'type' => 'alliance',
            'title' => 'Mass Assignment Test',
            'message' => 'Test mass assignment',
            'data' => ['key' => 'value'],
            'priority' => 'urgent',
            'read_at' => now(),
            'sent_at' => now(),
        ];

        $notification = Notification::create($data);

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $notification->$key);
        }
    }

    /**
     * @test
     */
    public function it_has_reference_trait()
    {
        $this->assertTrue(method_exists($this->notification, 'generateReference'));
    }

    /**
     * @test
     */
    public function it_has_taxonomy_trait()
    {
        $this->assertTrue(method_exists($this->notification, 'taxonomies'));
    }
}
