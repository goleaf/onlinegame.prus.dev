<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_users()
    {
        $user = User::factory()->create();
        User::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/users');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'phone_country',
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
    public function it_can_get_specific_user()
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/users/{$targetUser->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'phone',
                'phone_country',
                'player',
                'players',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * @test
     */
    public function it_can_create_user()
    {
        $user = User::factory()->create();

        $userData = [
            'name' => 'TestUser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ];

        $response = $this->actingAs($user)->post('/api/game/users', $userData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'phone_country',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'TestUser',
            'email' => 'test@example.com',
        ]);
    }

    /**
     * @test
     */
    public function it_can_update_user()
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $updateData = [
            'name' => 'UpdatedUser',
            'email' => 'updated@example.com',
            'phone' => '+9876543210',
        ];

        $response = $this->actingAs($user)->put("/api/game/users/{$targetUser->id}", $updateData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'UpdatedUser',
            'email' => 'updated@example.com',
        ]);
    }

    /**
     * @test
     */
    public function it_can_delete_user()
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $response = $this->actingAs($user)->delete("/api/game/users/{$targetUser->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
    }

    /**
     * @test
     */
    public function it_can_get_users_by_name()
    {
        $user = User::factory()->create();
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);

        $response = $this->actingAs($user)->get('/api/game/users?search=John');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    /**
     * @test
     */
    public function it_can_get_users_by_email()
    {
        $user = User::factory()->create();
        User::factory()->create(['email' => 'john@example.com']);
        User::factory()->create(['email' => 'jane@example.com']);

        $response = $this->actingAs($user)->get('/api/game/users?search=john@example.com');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    /**
     * @test
     */
    public function it_can_get_users_by_phone()
    {
        $user = User::factory()->create();
        User::factory()->create(['phone' => '+1234567890']);
        User::factory()->create(['phone' => '+9876543210']);

        $response = $this->actingAs($user)->get('/api/game/users?search=+1234567890');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    /**
     * @test
     */
    public function it_can_get_user_statistics()
    {
        $user = User::factory()->create();
        User::factory()->count(5)->create();

        $response = $this->actingAs($user)->get('/api/game/users/statistics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'total_users',
                'active_users',
                'new_users_today',
                'users_by_country',
                'registration_trends',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_user_activity()
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/users/{$targetUser->id}/activity");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'user',
                'activity' => [
                    'recent_logins',
                    'game_activity',
                    'last_seen',
                    'activity_summary',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_user_players()
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/users/{$targetUser->id}/players");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'user',
                'players' => [
                    '*' => [
                        'id',
                        'name',
                        'tribe',
                        'world',
                        'alliance',
                        'created_at',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_ban_user()
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $banData = [
            'reason' => 'Violation of terms of service',
            'duration' => 7,  // days
            'permanent' => false,
        ];

        $response = $this->actingAs($user)->post("/api/game/users/{$targetUser->id}/ban", $banData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'ban' => [
                    'user_id',
                    'reason',
                    'duration',
                    'banned_until',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_unban_user()
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $response = $this->actingAs($user)->post("/api/game/users/{$targetUser->id}/unban");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_user_security_info()
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/users/{$targetUser->id}/security");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'user',
                'security' => [
                    'login_history',
                    'ip_addresses',
                    'security_events',
                    'two_factor_enabled',
                    'password_strength',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_reset_user_password()
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $response = $this->actingAs($user)->post("/api/game/users/{$targetUser->id}/reset-password");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'temporary_password',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_user_preferences()
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/users/{$targetUser->id}/preferences");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'user',
                'preferences' => [
                    'notifications',
                    'privacy',
                    'game_settings',
                    'display_options',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_update_user_preferences()
    {
        $user = User::factory()->create();
        $targetUser = User::factory()->create();

        $preferencesData = [
            'notifications' => [
                'email' => true,
                'push' => false,
            ],
            'privacy' => [
                'profile_public' => true,
                'show_online_status' => false,
            ],
        ];

        $response = $this->actingAs($user)->put("/api/game/users/{$targetUser->id}/preferences", $preferencesData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'preferences',
            ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/users');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_validates_user_creation_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/users', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * @test
     */
    public function it_validates_username_format()
    {
        $user = User::factory()->create();

        $userData = [
            'name' => 'Invalid User Name!',  // Invalid: contains special characters
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = $this->actingAs($user)->post('/api/game/users', $userData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * @test
     */
    public function it_validates_email_format()
    {
        $user = User::factory()->create();

        $userData = [
            'name' => 'TestUser',
            'email' => 'invalid-email',  // Invalid email format
            'password' => 'password123',
        ];

        $response = $this->actingAs($user)->post('/api/game/users', $userData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * @test
     */
    public function it_validates_password_strength()
    {
        $user = User::factory()->create();

        $userData = [
            'name' => 'TestUser',
            'email' => 'test@example.com',
            'password' => '123',  // Invalid: too short
        ];

        $response = $this->actingAs($user)->post('/api/game/users', $userData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * @test
     */
    public function it_validates_phone_format()
    {
        $user = User::factory()->create();

        $userData = [
            'name' => 'TestUser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'phone' => 'invalid-phone',  // Invalid phone format
        ];

        $response = $this->actingAs($user)->post('/api/game/users', $userData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /**
     * @test
     */
    public function it_validates_phone_country_format()
    {
        $user = User::factory()->create();

        $userData = [
            'name' => 'TestUser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'phone' => '+1234567890',
            'phone_country' => 'USA',  // Invalid: should be 2 characters
        ];

        $response = $this->actingAs($user)->post('/api/game/users', $userData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone_country']);
    }

    /**
     * @test
     */
    public function it_validates_unique_email()
    {
        $user = User::factory()->create();
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'TestUser',
            'email' => 'existing@example.com',  // Invalid: already exists
            'password' => 'password123',
        ];

        $response = $this->actingAs($user)->post('/api/game/users', $userData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * @test
     */
    public function it_returns_404_for_nonexistent_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/users/999');

        $response->assertStatus(404);
    }
}
