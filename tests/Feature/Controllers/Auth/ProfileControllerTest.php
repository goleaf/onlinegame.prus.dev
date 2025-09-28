<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_show_user_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile')
            ->assertSee($user->name)
            ->assertSee($user->email);
    }

    /**
     * @test
     */
    public function it_can_update_user_profile()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $response->assertRedirect('/profile');

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('updated@example.com', $user->email);
    }

    /**
     * @test
     */
    public function it_can_update_user_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect('/profile');

        $user->refresh();
        $this->assertTrue(Hash::check('new-password', $user->password));
    }

    /**
     * @test
     */
    public function it_validates_current_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertSessionHasErrors(['current_password']);
    }

    /**
     * @test
     */
    public function it_validates_password_confirmation()
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_can_delete_user_account()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/profile', [
            'password' => 'password',
        ]);

        $response->assertRedirect('/');

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    /**
     * @test
     */
    public function it_validates_password_for_account_deletion()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($user)->delete('/profile', [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['password']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);
    }

    /**
     * @test
     */
    public function it_handles_guest_users()
    {
        $response = $this->get('/profile');

        $response->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_can_upload_profile_avatar()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/avatar', [
            'avatar' => 'test-avatar.jpg',
        ]);

        $response->assertRedirect('/profile');
    }

    /**
     * @test
     */
    public function it_can_remove_profile_avatar()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/profile/avatar');

        $response->assertRedirect('/profile');
    }

    /**
     * @test
     */
    public function it_can_export_user_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/export');

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_import_user_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/import', [
            'data' => 'user-data.json',
        ]);

        $response->assertRedirect('/profile');
    }

    /**
     * @test
     */
    public function it_can_show_profile_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/settings');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-settings');
    }

    /**
     * @test
     */
    public function it_can_update_profile_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/settings', [
            'notifications' => true,
            'privacy' => 'private',
            'language' => 'en',
        ]);

        $response->assertRedirect('/profile/settings');
    }

    /**
     * @test
     */
    public function it_can_show_profile_activity()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity');
    }

    /**
     * @test
     */
    public function it_can_show_profile_security()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/security');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-security');
    }

    /**
     * @test
     */
    public function it_can_update_security_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/security', [
            'two_factor_enabled' => true,
            'login_notifications' => true,
        ]);

        $response->assertRedirect('/profile/security');
    }

    /**
     * @test
     */
    public function it_can_show_profile_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/preferences');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-preferences');
    }

    /**
     * @test
     */
    public function it_can_update_profile_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/preferences', [
            'theme' => 'dark',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
        ]);

        $response->assertRedirect('/profile/preferences');
    }

    /**
     * @test
     */
    public function it_can_show_profile_statistics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/statistics');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-statistics');
    }

    /**
     * @test
     */
    public function it_can_show_profile_achievements()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/achievements');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-achievements');
    }

    /**
     * @test
     */
    public function it_can_show_profile_history()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/history');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-history');
    }

    /**
     * @test
     */
    public function it_can_show_profile_sessions()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/sessions');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-sessions');
    }

    /**
     * @test
     */
    public function it_can_revoke_user_sessions()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/profile/sessions');

        $response->assertRedirect('/profile/sessions');
    }

    /**
     * @test
     */
    public function it_can_show_profile_devices()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/devices');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-devices');
    }

    /**
     * @test
     */
    public function it_can_remove_user_device()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/profile/devices/1');

        $response->assertRedirect('/profile/devices');
    }

    /**
     * @test
     */
    public function it_can_show_profile_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/backup');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-backup');
    }

    /**
     * @test
     */
    public function it_can_create_profile_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/backup');

        $response->assertRedirect('/profile/backup');
    }

    /**
     * @test
     */
    public function it_can_restore_profile_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/backup/restore', [
            'backup_file' => 'backup.json',
        ]);

        $response->assertRedirect('/profile/backup');
    }

    /**
     * @test
     */
    public function it_can_show_profile_support()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/support');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-support');
    }

    /**
     * @test
     */
    public function it_can_submit_support_request()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/support', [
            'subject' => 'Test Support Request',
            'message' => 'This is a test support request.',
            'priority' => 'medium',
        ]);

        $response->assertRedirect('/profile/support');
    }

    /**
     * @test
     */
    public function it_can_show_profile_feedback()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/feedback');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-feedback');
    }

    /**
     * @test
     */
    public function it_can_submit_profile_feedback()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/feedback', [
            'rating' => 5,
            'comment' => 'Great experience!',
            'category' => 'general',
        ]);

        $response->assertRedirect('/profile/feedback');
    }
}
