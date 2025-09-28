<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileAnalyticsControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_show_analytics_dashboard()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics');
    }

    /**
     * @test
     */
    public function it_can_show_analytics_overview()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/overview');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics-overview');
    }

    /**
     * @test
     */
    public function it_can_show_analytics_metrics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/metrics');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics-metrics');
    }

    /**
     * @test
     */
    public function it_can_show_analytics_charts()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/charts');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics-charts');
    }

    /**
     * @test
     */
    public function it_can_show_analytics_reports()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/reports');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics-reports');
    }

    /**
     * @test
     */
    public function it_can_show_analytics_insights()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/insights');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics-insights');
    }

    /**
     * @test
     */
    public function it_can_show_analytics_trends()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/trends');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics-trends');
    }

    /**
     * @test
     */
    public function it_can_show_analytics_comparisons()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/comparisons');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics-comparisons');
    }

    /**
     * @test
     */
    public function it_can_show_analytics_benchmarks()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/benchmarks');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics-benchmarks');
    }

    /**
     * @test
     */
    public function it_can_show_analytics_goals()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/goals');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics-goals');
    }

    /**
     * @test
     */
    public function it_can_create_analytics_goal()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/analytics/goals', [
            'name' => 'Increase Activity',
            'description' => 'Increase daily activity by 20%',
            'target_value' => 100,
            'current_value' => 80,
            'deadline' => '2024-12-31',
            'category' => 'activity',
        ]);

        $response->assertRedirect('/profile/analytics/goals');
    }

    /**
     * @test
     */
    public function it_can_show_analytics_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/settings');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics-settings');
    }

    /**
     * @test
     */
    public function it_can_update_analytics_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/analytics/settings', [
            'track_activity' => true,
            'track_performance' => true,
            'track_engagement' => false,
            'data_retention' => '90_days',
        ]);

        $response->assertRedirect('/profile/analytics/settings');
    }

    /**
     * @test
     */
    public function it_can_show_analytics_export()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/export');

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_export_analytics_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/analytics/export', [
            'format' => 'csv',
            'date_range' => '30_days',
            'include_metadata' => true,
        ]);

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_show_analytics_import()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/import');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics-import');
    }

    /**
     * @test
     */
    public function it_can_import_analytics_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/analytics/import', [
            'import_file' => 'analytics_data.csv',
            'overwrite_existing' => false,
        ]);

        $response->assertRedirect('/profile/analytics');
    }

    /**
     * @test
     */
    public function it_can_show_analytics_help()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/help');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics-help');
    }

    /**
     * @test
     */
    public function it_can_show_analytics_support()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/support');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics-support');
    }

    /**
     * @test
     */
    public function it_can_submit_analytics_support()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/analytics/support', [
            'subject' => 'Analytics Issue',
            'message' => 'I need help with my analytics.',
            'category' => 'analytics',
        ]);

        $response->assertRedirect('/profile/analytics/support');
    }

    /**
     * @test
     */
    public function it_can_show_analytics_privacy()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/privacy');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics-privacy');
    }

    /**
     * @test
     */
    public function it_can_update_analytics_privacy()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/analytics/privacy', [
            'share_data' => false,
            'public_analytics' => false,
            'data_retention' => '30_days',
        ]);

        $response->assertRedirect('/profile/analytics/privacy');
    }

    /**
     * @test
     */
    public function it_can_show_analytics_security()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/security');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics-security');
    }

    /**
     * @test
     */
    public function it_can_update_analytics_security()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/analytics/security', [
            'encrypt_data' => true,
            'access_logging' => true,
            'ip_whitelist' => ['192.168.1.1'],
        ]);

        $response->assertRedirect('/profile/analytics/security');
    }

    /**
     * @test
     */
    public function it_can_show_analytics_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/backup');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics-backup');
    }

    /**
     * @test
     */
    public function it_can_create_analytics_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/analytics/backup');

        $response->assertRedirect('/profile/analytics/backup');
    }

    /**
     * @test
     */
    public function it_can_restore_analytics_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/analytics/backup/restore', [
            'backup_file' => 'analytics_backup.json',
        ]);

        $response->assertRedirect('/profile/analytics/backup');
    }

    /**
     * @test
     */
    public function it_can_show_analytics_sync()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/sync');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics-sync');
    }

    /**
     * @test
     */
    public function it_can_sync_analytics_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/analytics/sync', [
            'sync_type' => 'full',
            'include_metadata' => true,
        ]);

        $response->assertRedirect('/profile/analytics/sync');
    }

    /**
     * @test
     */
    public function it_can_show_analytics_test()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/test');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics-test');
    }

    /**
     * @test
     */
    public function it_can_test_analytics_generation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/analytics/test', [
            'test_type' => 'generation',
            'test_data' => 'sample_data',
        ]);

        $response->assertRedirect('/profile/analytics/test');
    }

    /**
     * @test
     */
    public function it_can_show_analytics_advanced()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/analytics/advanced');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-analytics-advanced');
    }

    /**
     * @test
     */
    public function it_can_update_advanced_analytics_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/analytics/advanced', [
            'debug_mode' => false,
            'developer_mode' => false,
            'beta_features' => true,
        ]);

        $response->assertRedirect('/profile/analytics/advanced');
    }

    /**
     * @test
     */
    public function it_handles_guest_users()
    {
        $response = $this->get('/profile/analytics');

        $response->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_validates_analytics_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/analytics/goals', [
            'name' => '',
            'target_value' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['name', 'target_value']);
    }
}
