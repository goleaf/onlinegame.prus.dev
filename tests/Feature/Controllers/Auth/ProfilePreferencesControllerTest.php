<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfilePreferencesControllerTest extends TestCase
{
    use RefreshDatabase;

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
    public function it_can_update_general_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/preferences/general', [
            'language' => 'en',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'time_format' => '24h',
        ]);

        $response->assertRedirect('/profile/preferences');
    }

    /**
     * @test
     */
    public function it_can_update_display_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/preferences/display', [
            'theme' => 'dark',
            'accent_color' => 'blue',
            'font_size' => 'medium',
            'show_avatars' => true,
        ]);

        $response->assertRedirect('/profile/preferences');
    }

    /**
     * @test
     */
    public function it_can_update_notification_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/preferences/notifications', [
            'email_notifications' => true,
            'push_notifications' => false,
            'sms_notifications' => true,
            'marketing_emails' => false,
        ]);

        $response->assertRedirect('/profile/preferences');
    }

    /**
     * @test
     */
    public function it_can_update_privacy_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/preferences/privacy', [
            'profile_visibility' => 'private',
            'show_online_status' => false,
            'allow_friend_requests' => true,
            'allow_messages' => true,
        ]);

        $response->assertRedirect('/profile/preferences');
    }

    /**
     * @test
     */
    public function it_can_update_game_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/preferences/game', [
            'auto_save' => true,
            'show_tutorials' => false,
            'difficulty' => 'normal',
            'sound_effects' => true,
        ]);

        $response->assertRedirect('/profile/preferences');
    }

    /**
     * @test
     */
    public function it_can_update_accessibility_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/preferences/accessibility', [
            'high_contrast' => false,
            'large_text' => true,
            'screen_reader' => false,
            'keyboard_navigation' => true,
        ]);

        $response->assertRedirect('/profile/preferences');
    }

    /**
     * @test
     */
    public function it_can_update_communication_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/preferences/communication', [
            'allow_messages' => true,
            'allow_friend_requests' => true,
            'allow_group_invites' => false,
            'allow_party_invites' => true,
        ]);

        $response->assertRedirect('/profile/preferences');
    }

    /**
     * @test
     */
    public function it_can_update_social_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/preferences/social', [
            'show_friends_online' => true,
            'show_activity_feed' => false,
            'allow_profile_views' => true,
            'show_last_seen' => false,
        ]);

        $response->assertRedirect('/profile/preferences');
    }

    /**
     * @test
     */
    public function it_can_update_security_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/preferences/security', [
            'two_factor_enabled' => true,
            'login_notifications' => true,
            'security_alerts' => true,
            'suspicious_activity_alerts' => true,
        ]);

        $response->assertRedirect('/profile/preferences');
    }

    /**
     * @test
     */
    public function it_can_update_data_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/preferences/data', [
            'data_collection' => false,
            'analytics_tracking' => true,
            'personalization' => true,
            'data_sharing' => false,
        ]);

        $response->assertRedirect('/profile/preferences');
    }

    /**
     * @test
     */
    public function it_can_reset_preferences_to_default()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/preferences/reset');

        $response->assertRedirect('/profile/preferences');
    }

    /**
     * @test
     */
    public function it_can_export_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/preferences/export');

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_import_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/preferences/import', [
            'preferences_file' => 'preferences.json',
        ]);

        $response->assertRedirect('/profile/preferences');
    }

    /**
     * @test
     */
    public function it_can_show_preferences_history()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/preferences/history');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-preferences-history');
    }

    /**
     * @test
     */
    public function it_can_restore_preferences_from_history()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/preferences/restore', [
            'history_id' => 1,
        ]);

        $response->assertRedirect('/profile/preferences');
    }

    /**
     * @test
     */
    public function it_can_show_preferences_help()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/preferences/help');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-preferences-help');
    }

    /**
     * @test
     */
    public function it_can_show_preferences_support()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/preferences/support');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-preferences-support');
    }

    /**
     * @test
     */
    public function it_can_submit_preferences_support()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/preferences/support', [
            'subject' => 'Preferences Issue',
            'message' => 'I need help with my preferences.',
            'category' => 'preferences',
        ]);

        $response->assertRedirect('/profile/preferences/support');
    }

    /**
     * @test
     */
    public function it_can_show_preferences_statistics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/preferences/statistics');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-preferences-statistics');
    }

    /**
     * @test
     */
    public function it_can_show_preferences_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/preferences/backup');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-preferences-backup');
    }

    /**
     * @test
     */
    public function it_can_create_preferences_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/preferences/backup');

        $response->assertRedirect('/profile/preferences/backup');
    }

    /**
     * @test
     */
    public function it_can_restore_preferences_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/preferences/backup/restore', [
            'backup_file' => 'preferences_backup.json',
        ]);

        $response->assertRedirect('/profile/preferences/backup');
    }

    /**
     * @test
     */
    public function it_handles_guest_users()
    {
        $response = $this->get('/profile/preferences');

        $response->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_validates_preferences_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/preferences/general', [
            'language' => 'invalid',
            'timezone' => 'invalid',
            'date_format' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['language', 'timezone', 'date_format']);
    }

    /**
     * @test
     */
    public function it_can_show_preferences_advanced()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/preferences/advanced');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-preferences-advanced');
    }

    /**
     * @test
     */
    public function it_can_update_advanced_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/preferences/advanced', [
            'debug_mode' => false,
            'developer_mode' => false,
            'beta_features' => true,
        ]);

        $response->assertRedirect('/profile/preferences/advanced');
    }
}
