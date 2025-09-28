<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\Game\Notification;
use App\Models\Game\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_notifications()
    {
        $user = User::factory()->create();
        Notification::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/notifications');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'title',
                        'message',
                        'is_read',
                        'priority',
                        'player_id',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_specific_notification()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/notifications/{$notification->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'type',
                'title',
                'message',
                'data',
                'is_read',
                'priority',
                'player',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * @test
     */
    public function it_can_create_notification()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $notificationData = [
            'player_id' => $player->id,
            'type' => 'battle',
            'title' => 'Battle Notification',
            'message' => 'Your village has been attacked!',
            'data' => ['attacker' => 'Player1', 'village' => 'Village1'],
            'priority' => 'high',
            'is_read' => false,
        ];

        $response = $this->actingAs($user)->post('/api/game/notifications', $notificationData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'notification' => [
                    'id',
                    'type',
                    'title',
                    'message',
                    'priority',
                    'is_read',
                    'player_id',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('notifications', [
            'title' => 'Battle Notification',
            'type' => 'battle',
            'player_id' => $player->id,
        ]);
    }

    /**
     * @test
     */
    public function it_can_update_notification()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create();

        $updateData = [
            'title' => 'Updated Notification',
            'message' => 'Updated message content',
            'priority' => 'normal',
            'is_read' => true,
        ];

        $response = $this->actingAs($user)->put("/api/game/notifications/{$notification->id}", $updateData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'notification' => [
                    'id',
                    'title',
                    'message',
                    'priority',
                    'is_read',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'title' => 'Updated Notification',
            'is_read' => true,
        ]);
    }

    /**
     * @test
     */
    public function it_can_delete_notification()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create();

        $response = $this->actingAs($user)->delete("/api/game/notifications/{$notification->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    /**
     * @test
     */
    public function it_can_get_unread_notifications()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        Notification::factory()->count(2)->create(['player_id' => $player->id, 'is_read' => false]);
        Notification::factory()->count(1)->create(['player_id' => $player->id, 'is_read' => true]);

        $response = $this->actingAs($user)->get('/api/game/notifications/unread');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'title',
                        'message',
                        'is_read',
                        'priority',
                        'created_at',
                    ],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_notifications_by_type()
    {
        $user = User::factory()->create();
        Notification::factory()->count(2)->create(['type' => 'battle']);
        Notification::factory()->count(1)->create(['type' => 'resource']);

        $response = $this->actingAs($user)->get('/api/game/notifications?type=battle');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_notifications_by_priority()
    {
        $user = User::factory()->create();
        Notification::factory()->count(2)->create(['priority' => 'high']);
        Notification::factory()->count(1)->create(['priority' => 'normal']);

        $response = $this->actingAs($user)->get('/api/game/notifications?priority=high');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_mark_notification_as_read()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['is_read' => false]);

        $response = $this->actingAs($user)->post("/api/game/notifications/{$notification->id}/read");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'notification' => [
                    'id',
                    'is_read',
                    'read_at',
                ],
            ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);
    }

    /**
     * @test
     */
    public function it_can_mark_notification_as_unread()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['is_read' => true]);

        $response = $this->actingAs($user)->post("/api/game/notifications/{$notification->id}/unread");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'notification' => [
                    'id',
                    'is_read',
                    'read_at',
                ],
            ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => false,
        ]);
    }

    /**
     * @test
     */
    public function it_can_mark_all_notifications_as_read()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        Notification::factory()->count(3)->create(['player_id' => $player->id, 'is_read' => false]);

        $response = $this->actingAs($user)->post('/api/game/notifications/mark-all-read');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'updated_count',
            ]);

        $this->assertDatabaseMissing('notifications', [
            'player_id' => $player->id,
            'is_read' => false,
        ]);
    }

    /**
     * @test
     */
    public function it_can_clear_all_notifications()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        Notification::factory()->count(3)->create(['player_id' => $player->id]);

        $response = $this->actingAs($user)->post('/api/game/notifications/clear-all');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'deleted_count',
            ]);

        $this->assertDatabaseMissing('notifications', [
            'player_id' => $player->id,
        ]);
    }

    /**
     * @test
     */
    public function it_can_get_notification_statistics()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        Notification::factory()->count(5)->create(['player_id' => $player->id, 'is_read' => false]);
        Notification::factory()->count(3)->create(['player_id' => $player->id, 'is_read' => true]);

        $response = $this->actingAs($user)->get('/api/game/notifications/statistics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'total_notifications',
                'unread_count',
                'read_count',
                'by_type',
                'by_priority',
                'recent_activity',
            ]);
    }

    /**
     * @test
     */
    public function it_can_send_bulk_notifications()
    {
        $user = User::factory()->create();
        $player1 = Player::factory()->create();
        $player2 = Player::factory()->create();

        $bulkData = [
            'player_ids' => [$player1->id, $player2->id],
            'type' => 'system',
            'title' => 'System Maintenance',
            'message' => 'The game will be under maintenance in 1 hour.',
            'priority' => 'normal',
        ];

        $response = $this->actingAs($user)->post('/api/game/notifications/bulk', $bulkData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'notifications_created',
            ]);

        $this->assertDatabaseHas('notifications', [
            'player_id' => $player1->id,
            'title' => 'System Maintenance',
        ]);
        $this->assertDatabaseHas('notifications', [
            'player_id' => $player2->id,
            'title' => 'System Maintenance',
        ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/notifications');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_validates_notification_creation_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/notifications', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['player_id', 'type', 'title', 'message', 'priority']);
    }

    /**
     * @test
     */
    public function it_validates_notification_type()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create();

        $notificationData = [
            'player_id' => $player->id,
            'type' => 'invalid_type',
            'title' => 'Test Notification',
            'message' => 'Test message',
            'priority' => 'normal',
        ];

        $response = $this->actingAs($user)->post('/api/game/notifications', $notificationData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /**
     * @test
     */
    public function it_validates_priority_enum()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create();

        $notificationData = [
            'player_id' => $player->id,
            'type' => 'battle',
            'title' => 'Test Notification',
            'message' => 'Test message',
            'priority' => 'invalid_priority',
        ];

        $response = $this->actingAs($user)->post('/api/game/notifications', $notificationData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    }

    /**
     * @test
     */
    public function it_validates_title_length()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create();

        $notificationData = [
            'player_id' => $player->id,
            'type' => 'battle',
            'title' => str_repeat('a', 256),  // Exceeds max 255
            'message' => 'Test message',
            'priority' => 'normal',
        ];

        $response = $this->actingAs($user)->post('/api/game/notifications', $notificationData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /**
     * @test
     */
    public function it_returns_404_for_nonexistent_notification()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/notifications/999');

        $response->assertStatus(404);
    }
}
