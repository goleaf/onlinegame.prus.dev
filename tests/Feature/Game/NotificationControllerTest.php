<?php

namespace Tests\Feature\Game;

use Tests\TestCase;
use App\Models\Game\Player;
use App\Models\Game\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $player;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id]);
        
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_get_notifications()
    {
        Notification::factory()->count(5)->create(['player_id' => $this->player->id]);

        $response = $this->getJson('/game/api/notifications');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'player_id',
                            'type',
                            'title',
                            'message',
                            'priority',
                            'is_read',
                            'created_at'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_can_filter_notifications_by_type()
    {
        Notification::factory()->create([
            'player_id' => $this->player->id,
            'type' => 'battle'
        ]);
        Notification::factory()->create([
            'player_id' => $this->player->id,
            'type' => 'system'
        ]);

        $response = $this->getJson('/game/api/notifications?type=battle');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_can_filter_notifications_by_read_status()
    {
        Notification::factory()->create([
            'player_id' => $this->player->id,
            'is_read' => true
        ]);
        Notification::factory()->create([
            'player_id' => $this->player->id,
            'is_read' => false
        ]);

        $response = $this->getJson('/game/api/notifications?is_read=false');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    /** @test */
    public function it_can_get_specific_notification()
    {
        $notification = Notification::factory()->create([
            'player_id' => $this->player->id,
            'is_read' => false
        ]);

        $response = $this->getJson("/game/api/notifications/{$notification->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'id',
                    'player_id',
                    'type',
                    'title',
                    'message',
                    'priority',
                    'is_read'
                ]);

        // Should mark as read when viewed
        $notification->refresh();
        $this->assertTrue($notification->is_read);
    }

    /** @test */
    public function it_can_mark_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'player_id' => $this->player->id,
            'is_read' => false
        ]);

        $response = $this->patchJson("/game/api/notifications/{$notification->id}/read");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);

        $notification->refresh();
        $this->assertTrue($notification->is_read);
    }

    /** @test */
    public function it_can_mark_all_notifications_as_read()
    {
        Notification::factory()->count(3)->create([
            'player_id' => $this->player->id,
            'is_read' => false
        ]);

        $response = $this->patchJson('/game/api/notifications/mark-all-read');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'updated_count'
                ]);

        $this->assertEquals(3, $response->json('updated_count'));

        $unreadCount = Notification::where('player_id', $this->player->id)
                                  ->where('is_read', false)
                                  ->count();
        $this->assertEquals(0, $unreadCount);
    }

    /** @test */
    public function it_can_delete_notification()
    {
        $notification = Notification::factory()->create([
            'player_id' => $this->player->id
        ]);

        $response = $this->deleteJson("/game/api/notifications/{$notification->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message'
                ]);

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id
        ]);
    }

    /** @test */
    public function it_can_get_unread_count()
    {
        Notification::factory()->count(3)->create([
            'player_id' => $this->player->id,
            'is_read' => false
        ]);
        Notification::factory()->count(2)->create([
            'player_id' => $this->player->id,
            'is_read' => true
        ]);

        $response = $this->getJson('/game/api/notifications/unread-count');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'unread_count'
                ]);

        $this->assertEquals(3, $response->json('unread_count'));
    }

    /** @test */
    public function it_can_get_notification_statistics()
    {
        Notification::factory()->count(5)->create([
            'player_id' => $this->player->id,
            'type' => 'battle',
            'priority' => 'high'
        ]);
        Notification::factory()->count(3)->create([
            'player_id' => $this->player->id,
            'type' => 'system',
            'priority' => 'normal'
        ]);

        $response = $this->getJson('/game/api/notifications/statistics');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'total_notifications',
                    'unread_notifications',
                    'read_notifications',
                    'by_type',
                    'by_priority'
                ]);
    }

    /** @test */
    public function it_can_create_notification()
    {
        $notificationData = [
            'player_id' => $this->player->id,
            'type' => 'battle',
            'title' => 'Village Under Attack!',
            'message' => 'Your village is being attacked',
            'priority' => 'urgent',
            'data' => ['attacker_id' => 2, 'village_id' => 5]
        ];

        $response = $this->postJson('/game/api/notifications', $notificationData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'notification' => [
                        'id',
                        'player_id',
                        'type',
                        'title',
                        'message',
                        'priority',
                        'is_read'
                    ]
                ]);

        $this->assertDatabaseHas('notifications', [
            'player_id' => $this->player->id,
            'type' => 'battle',
            'title' => 'Village Under Attack!',
            'priority' => 'urgent'
        ]);
    }

    /** @test */
    public function it_validates_notification_creation()
    {
        $response = $this->postJson('/game/api/notifications', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'player_id',
                    'type',
                    'title',
                    'message'
                ]);
    }

    /** @test */
    public function it_cannot_access_other_player_notifications()
    {
        $otherPlayer = Player::factory()->create();
        $notification = Notification::factory()->create([
            'player_id' => $otherPlayer->id
        ]);

        $response = $this->getJson("/game/api/notifications/{$notification->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_cannot_delete_other_player_notifications()
    {
        $otherPlayer = Player::factory()->create();
        $notification = Notification::factory()->create([
            'player_id' => $otherPlayer->id
        ]);

        $response = $this->deleteJson("/game/api/notifications/{$notification->id}");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_cannot_mark_other_player_notifications_as_read()
    {
        $otherPlayer = Player::factory()->create();
        $notification = Notification::factory()->create([
            'player_id' => $otherPlayer->id
        ]);

        $response = $this->patchJson("/game/api/notifications/{$notification->id}/read");

        $response->assertStatus(404);
    }
}
