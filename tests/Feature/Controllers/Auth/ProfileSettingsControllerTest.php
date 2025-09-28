<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

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
            'timezone' => 'UTC',
        ]);

        $response->assertRedirect('/profile/settings');
    }

    /**
     * @test
     */
    public function it_can_update_notification_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/settings/notifications', [
            'email_notifications' => true,
            'push_notifications' => false,
            'sms_notifications' => true,
        ]);

        $response->assertRedirect('/profile/settings');
    }

    /**
     * @test
     */
    public function it_can_update_privacy_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/settings/privacy', [
            'profile_visibility' => 'private',
            'show_online_status' => false,
            'allow_friend_requests' => true,
        ]);

        $response->assertRedirect('/profile/settings');
    }

    /**
     * @test
     */
    public function it_can_update_language_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/settings/language', [
            'language' => 'es',
            'date_format' => 'd/m/Y',
            'time_format' => '24h',
        ]);

        $response->assertRedirect('/profile/settings');
    }

    /**
     * @test
     */
    public function it_can_update_theme_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/settings/theme', [
            'theme' => 'dark',
            'accent_color' => 'blue',
            'font_size' => 'medium',
        ]);

        $response->assertRedirect('/profile/settings');
    }

    /**
     * @test
     */
    public function it_can_update_security_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/settings/security', [
            'two_factor_enabled' => true,
            'login_notifications' => true,
            'session_timeout' => 30,
        ]);

        $response->assertRedirect('/profile/settings');
    }

    /**
     * @test
     */
    public function it_can_update_display_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/settings/display', [
            'show_avatars' => true,
            'show_signatures' => false,
            'compact_mode' => true,
        ]);

        $response->assertRedirect('/profile/settings');
    }

    /**
     * @test
     */
    public function it_can_update_communication_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/settings/communication', [
            'allow_messages' => true,
            'allow_friend_requests' => true,
            'allow_group_invites' => false,
        ]);

        $response->assertRedirect('/profile/settings');
    }

    /**
     * @test
     */
    public function it_can_update_game_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/settings/game', [
            'auto_save' => true,
            'show_tutorials' => false,
            'difficulty' => 'normal',
        ]);

        $response->assertRedirect('/profile/settings');
    }

    /**
     * @test
     */
    public function it_can_reset_settings_to_default()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/settings/reset');

        $response->assertRedirect('/profile/settings');
    }

    /**
     * @test
     */
    public function it_can_export_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/settings/export');

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_import_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/settings/import', [
            'settings_file' => 'settings.json',
        ]);

        $response->assertRedirect('/profile/settings');
    }

    /**
     * @test
     */
    public function it_can_show_settings_history()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/settings/history');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-settings-history');
    }

    /**
     * @test
     */
    public function it_can_restore_settings_from_history()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/settings/restore', [
            'history_id' => 1,
        ]);

        $response->assertRedirect('/profile/settings');
    }

    /**
     * @test
     */
    public function it_can_show_settings_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/settings/preferences');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-settings-preferences');
    }

    /**
     * @test
     */
    public function it_can_update_settings_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/settings/preferences', [
            'auto_save' => true,
            'show_help' => false,
            'enable_sounds' => true,
        ]);

        $response->assertRedirect('/profile/settings/preferences');
    }

    /**
     * @test
     */
    public function it_can_show_settings_advanced()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/settings/advanced');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-settings-advanced');
    }

    /**
     * @test
     */
    public function it_can_update_advanced_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/settings/advanced', [
            'debug_mode' => false,
            'developer_mode' => false,
            'beta_features' => true,
        ]);

        $response->assertRedirect('/profile/settings/advanced');
    }

    /**
     * @test
     */
    public function it_can_show_settings_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/settings/backup');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-settings-backup');
    }

    /**
     * @test
     */
    public function it_can_create_settings_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/settings/backup');

        $response->assertRedirect('/profile/settings/backup');
    }

    /**
     * @test
     */
    public function it_can_restore_settings_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/settings/backup/restore', [
            'backup_file' => 'settings_backup.json',
        ]);

        $response->assertRedirect('/profile/settings/backup');
    }

    /**
     * @test
     */
    public function it_can_show_settings_support()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/settings/support');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-settings-support');
    }

    /**
     * @test
     */
    public function it_can_submit_settings_support()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/settings/support', [
            'subject' => 'Settings Issue',
            'message' => 'I need help with my settings.',
            'category' => 'settings',
        ]);

        $response->assertRedirect('/profile/settings/support');
    }

    /**
     * @test
     */
    public function it_handles_guest_users()
    {
        $response = $this->get('/profile/settings');

        $response->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_validates_settings_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/settings', [
            'notifications' => 'invalid',
            'privacy' => 'invalid',
            'language' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['notifications', 'privacy', 'language']);
    }

    /**
     * @test
     */
    public function it_can_show_settings_statistics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/settings/statistics');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-settings-statistics');
    }

    /**
     * @test
     */
    public function it_can_show_settings_help()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/settings/help');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-settings-help');
    }
}
