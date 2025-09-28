<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileActivityControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_show_activity_log()
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
    public function it_can_show_recent_activity()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/recent');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-recent');
    }

    /**
     * @test
     */
    public function it_can_show_activity_by_type()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/type/login');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-type');
    }

    /**
     * @test
     */
    public function it_can_show_activity_by_date()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/date/2024-01-01');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-date');
    }

    /**
     * @test
     */
    public function it_can_show_activity_statistics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/statistics');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-statistics');
    }

    /**
     * @test
     */
    public function it_can_show_activity_export()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/export');

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_export_activity_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/activity/export', [
            'format' => 'csv',
            'date_from' => '2024-01-01',
            'date_to' => '2024-01-31',
        ]);

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_show_activity_filters()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/filters');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-filters');
    }

    /**
     * @test
     */
    public function it_can_apply_activity_filters()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/activity/filters', [
            'activity_type' => 'login',
            'date_from' => '2024-01-01',
            'date_to' => '2024-01-31',
            'ip_address' => '192.168.1.1',
        ]);

        $response->assertRedirect('/profile/activity');
    }

    /**
     * @test
     */
    public function it_can_show_activity_search()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/search');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-search');
    }

    /**
     * @test
     */
    public function it_can_search_activity()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/activity/search', [
            'query' => 'login',
            'search_type' => 'all',
        ]);

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-search-results');
    }

    /**
     * @test
     */
    public function it_can_show_activity_details()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/1');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-details');
    }

    /**
     * @test
     */
    public function it_can_show_activity_timeline()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/timeline');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-timeline');
    }

    /**
     * @test
     */
    public function it_can_show_activity_heatmap()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/heatmap');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-heatmap');
    }

    /**
     * @test
     */
    public function it_can_show_activity_analytics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/analytics');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-analytics');
    }

    /**
     * @test
     */
    public function it_can_show_activity_insights()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/insights');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-insights');
    }

    /**
     * @test
     */
    public function it_can_show_activity_patterns()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/patterns');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-patterns');
    }

    /**
     * @test
     */
    public function it_can_show_activity_recommendations()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/recommendations');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-recommendations');
    }

    /**
     * @test
     */
    public function it_can_show_activity_alerts()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/alerts');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-alerts');
    }

    /**
     * @test
     */
    public function it_can_show_activity_notifications()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/notifications');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-notifications');
    }

    /**
     * @test
     */
    public function it_can_show_activity_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/settings');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-settings');
    }

    /**
     * @test
     */
    public function it_can_update_activity_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/activity/settings', [
            'track_login_activity' => true,
            'track_page_views' => true,
            'track_api_calls' => false,
            'track_file_downloads' => true,
        ]);

        $response->assertRedirect('/profile/activity/settings');
    }

    /**
     * @test
     */
    public function it_can_show_activity_help()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/help');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-help');
    }

    /**
     * @test
     */
    public function it_can_show_activity_support()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/support');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-support');
    }

    /**
     * @test
     */
    public function it_can_submit_activity_support()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/activity/support', [
            'subject' => 'Activity Issue',
            'message' => 'I need help with my activity log.',
            'category' => 'activity',
        ]);

        $response->assertRedirect('/profile/activity/support');
    }

    /**
     * @test
     */
    public function it_can_show_activity_privacy()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/privacy');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-privacy');
    }

    /**
     * @test
     */
    public function it_can_update_activity_privacy()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/activity/privacy', [
            'share_activity' => false,
            'public_profile' => false,
            'activity_visibility' => 'private',
        ]);

        $response->assertRedirect('/profile/activity/privacy');
    }

    /**
     * @test
     */
    public function it_can_show_activity_security()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/security');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-security');
    }

    /**
     * @test
     */
    public function it_can_update_activity_security()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/activity/security', [
            'two_factor_required' => true,
            'session_timeout' => 30,
            'ip_whitelist' => ['192.168.1.1'],
        ]);

        $response->assertRedirect('/profile/activity/security');
    }

    /**
     * @test
     */
    public function it_can_show_activity_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/backup');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-backup');
    }

    /**
     * @test
     */
    public function it_can_create_activity_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/activity/backup');

        $response->assertRedirect('/profile/activity/backup');
    }

    /**
     * @test
     */
    public function it_can_restore_activity_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/activity/backup/restore', [
            'backup_file' => 'activity_backup.json',
        ]);

        $response->assertRedirect('/profile/activity/backup');
    }

    /**
     * @test
     */
    public function it_can_clear_activity_history()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/profile/activity/clear');

        $response->assertRedirect('/profile/activity');
    }

    /**
     * @test
     */
    public function it_handles_guest_users()
    {
        $response = $this->get('/profile/activity');

        $response->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_validates_activity_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/activity/filters', [
            'activity_type' => 'invalid',
            'date_from' => 'invalid-date',
        ]);

        $response->assertSessionHasErrors(['activity_type', 'date_from']);
    }

    /**
     * @test
     */
    public function it_can_show_activity_advanced()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/activity/advanced');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-activity-advanced');
    }

    /**
     * @test
     */
    public function it_can_update_advanced_activity_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/activity/advanced', [
            'debug_activity' => false,
            'developer_activity' => false,
            'beta_activity' => true,
        ]);

        $response->assertRedirect('/profile/activity/advanced');
    }
}
