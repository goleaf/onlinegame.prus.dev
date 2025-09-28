<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileNotificationsControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_show_notification_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/notifications');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-notifications');
    }

    /**
     * @test
     */
    public function it_can_update_email_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/notifications/email', [
            'battle_notifications' => true,
            'alliance_notifications' => true,
            'resource_notifications' => false,
            'system_notifications' => true,
        ]);

        $response->assertRedirect('/profile/notifications');
    }

    /**
     * @test
     */
    public function it_can_update_push_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/notifications/push', [
            'battle_alerts' => true,
            'alliance_messages' => true,
            'resource_warnings' => false,
            'system_updates' => true,
        ]);

        $response->assertRedirect('/profile/notifications');
    }

    /**
     * @test
     */
    public function it_can_update_sms_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/notifications/sms', [
            'critical_alerts' => true,
            'alliance_emergencies' => true,
            'battle_results' => false,
            'system_maintenance' => true,
        ]);

        $response->assertRedirect('/profile/notifications');
    }

    /**
     * @test
     */
    public function it_can_update_in_game_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/notifications/in-game', [
            'show_popups' => true,
            'sound_effects' => true,
            'visual_effects' => false,
            'auto_close' => true,
        ]);

        $response->assertRedirect('/profile/notifications');
    }

    /**
     * @test
     */
    public function it_can_update_battle_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/notifications/battle', [
            'battle_started' => true,
            'battle_ended' => true,
            'battle_results' => true,
            'battle_reports' => false,
        ]);

        $response->assertRedirect('/profile/notifications');
    }

    /**
     * @test
     */
    public function it_can_update_alliance_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/notifications/alliance', [
            'alliance_messages' => true,
            'alliance_events' => true,
            'alliance_wars' => true,
            'alliance_diplomacy' => false,
        ]);

        $response->assertRedirect('/profile/notifications');
    }

    /**
     * @test
     */
    public function it_can_update_resource_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/notifications/resources', [
            'resource_full' => true,
            'resource_low' => true,
            'resource_production' => false,
            'resource_consumption' => true,
        ]);

        $response->assertRedirect('/profile/notifications');
    }

    /**
     * @test
     */
    public function it_can_update_system_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/notifications/system', [
            'system_updates' => true,
            'maintenance_alerts' => true,
            'security_alerts' => true,
            'feature_announcements' => false,
        ]);

        $response->assertRedirect('/profile/notifications');
    }

    /**
     * @test
     */
    public function it_can_update_marketing_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/notifications/marketing', [
            'promotional_emails' => false,
            'newsletter' => false,
            'special_offers' => false,
            'game_updates' => true,
        ]);

        $response->assertRedirect('/profile/notifications');
    }

    /**
     * @test
     */
    public function it_can_update_social_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/notifications/social', [
            'friend_requests' => true,
            'friend_online' => true,
            'friend_achievements' => false,
            'social_events' => true,
        ]);

        $response->assertRedirect('/profile/notifications');
    }

    /**
     * @test
     */
    public function it_can_update_security_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/notifications/security', [
            'login_alerts' => true,
            'password_changes' => true,
            'security_breaches' => true,
            'suspicious_activity' => true,
        ]);

        $response->assertRedirect('/profile/notifications');
    }

    /**
     * @test
     */
    public function it_can_update_achievement_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/notifications/achievements', [
            'achievement_unlocked' => true,
            'achievement_progress' => false,
            'achievement_milestones' => true,
            'achievement_rewards' => true,
        ]);

        $response->assertRedirect('/profile/notifications');
    }

    /**
     * @test
     */
    public function it_can_update_quest_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/notifications/quests', [
            'quest_available' => true,
            'quest_completed' => true,
            'quest_failed' => true,
            'quest_rewards' => false,
        ]);

        $response->assertRedirect('/profile/notifications');
    }

    /**
     * @test
     */
    public function it_can_update_trade_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/notifications/trade', [
            'trade_offers' => true,
            'trade_completed' => true,
            'trade_cancelled' => false,
            'trade_messages' => true,
        ]);

        $response->assertRedirect('/profile/notifications');
    }

    /**
     * @test
     */
    public function it_can_update_market_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/notifications/market', [
            'market_updates' => true,
            'price_changes' => false,
            'new_items' => true,
            'market_events' => true,
        ]);

        $response->assertRedirect('/profile/notifications');
    }

    /**
     * @test
     */
    public function it_can_update_event_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/notifications/events', [
            'game_events' => true,
            'special_events' => true,
            'tournament_events' => false,
            'seasonal_events' => true,
        ]);

        $response->assertRedirect('/profile/notifications');
    }

    /**
     * @test
     */
    public function it_can_test_notification_delivery()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/notifications/test', [
            'notification_type' => 'email',
            'test_message' => 'Test notification',
        ]);

        $response->assertRedirect('/profile/notifications');
    }

    /**
     * @test
     */
    public function it_can_show_notification_history()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/notifications/history');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-notifications-history');
    }

    /**
     * @test
     */
    public function it_can_clear_notification_history()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/profile/notifications/history');

        $response->assertRedirect('/profile/notifications/history');
    }

    /**
     * @test
     */
    public function it_can_show_notification_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/notifications/settings');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-notifications-settings');
    }

    /**
     * @test
     */
    public function it_can_update_notification_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/notifications/settings', [
            'notification_frequency' => 'immediate',
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
            'timezone' => 'UTC',
        ]);

        $response->assertRedirect('/profile/notifications/settings');
    }

    /**
     * @test
     */
    public function it_can_show_notification_help()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/notifications/help');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-notifications-help');
    }

    /**
     * @test
     */
    public function it_can_show_notification_support()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/notifications/support');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-notifications-support');
    }

    /**
     * @test
     */
    public function it_can_submit_notification_support()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/notifications/support', [
            'subject' => 'Notification Issue',
            'message' => 'I need help with my notifications.',
            'category' => 'notifications',
        ]);

        $response->assertRedirect('/profile/notifications/support');
    }

    /**
     * @test
     */
    public function it_can_show_notification_statistics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/notifications/statistics');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-notifications-statistics');
    }

    /**
     * @test
     */
    public function it_can_export_notification_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/notifications/export');

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_import_notification_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/notifications/import', [
            'preferences_file' => 'notifications.json',
        ]);

        $response->assertRedirect('/profile/notifications');
    }

    /**
     * @test
     */
    public function it_can_reset_notification_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/notifications/reset');

        $response->assertRedirect('/profile/notifications');
    }

    /**
     * @test
     */
    public function it_handles_guest_users()
    {
        $response = $this->get('/profile/notifications');

        $response->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_validates_notification_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/notifications/email', [
            'battle_notifications' => 'invalid',
            'alliance_notifications' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['battle_notifications', 'alliance_notifications']);
    }

    /**
     * @test
     */
    public function it_can_show_notification_advanced()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/notifications/advanced');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-notifications-advanced');
    }

    /**
     * @test
     */
    public function it_can_update_advanced_notification_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/notifications/advanced', [
            'debug_notifications' => false,
            'developer_notifications' => false,
            'beta_notifications' => true,
        ]);

        $response->assertRedirect('/profile/notifications/advanced');
    }
}
