<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileReportsControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_show_reports_dashboard()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-reports');
    }

    /**
     * @test
     */
    public function it_can_show_available_reports()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/available');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-reports-available');
    }

    /**
     * @test
     */
    public function it_can_show_generated_reports()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/generated');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-reports-generated');
    }

    /**
     * @test
     */
    public function it_can_show_report_details()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/1');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-reports-details');
    }

    /**
     * @test
     */
    public function it_can_generate_report()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/reports/generate', [
            'report_type' => 'activity',
            'date_range' => '30_days',
            'format' => 'pdf',
        ]);

        $response->assertRedirect('/profile/reports');
    }

    /**
     * @test
     */
    public function it_can_download_report()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/1/download');

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_show_report_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/settings');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-reports-settings');
    }

    /**
     * @test
     */
    public function it_can_update_report_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/reports/settings', [
            'default_format' => 'pdf',
            'auto_generate' => true,
            'email_reports' => false,
            'retention_days' => 90,
        ]);

        $response->assertRedirect('/profile/reports/settings');
    }

    /**
     * @test
     */
    public function it_can_show_report_templates()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/templates');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-reports-templates');
    }

    /**
     * @test
     */
    public function it_can_create_report_template()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/reports/templates', [
            'name' => 'Monthly Activity Report',
            'description' => 'Monthly activity summary',
            'template_type' => 'activity',
            'sections' => ['summary', 'details', 'charts'],
            'format' => 'pdf',
        ]);

        $response->assertRedirect('/profile/reports/templates');
    }

    /**
     * @test
     */
    public function it_can_show_report_schedules()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/schedules');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-reports-schedules');
    }

    /**
     * @test
     */
    public function it_can_create_report_schedule()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/reports/schedules', [
            'report_type' => 'activity',
            'frequency' => 'weekly',
            'day_of_week' => 'monday',
            'time' => '09:00',
            'email_recipients' => ['user@example.com'],
        ]);

        $response->assertRedirect('/profile/reports/schedules');
    }

    /**
     * @test
     */
    public function it_can_show_report_analytics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/analytics');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-reports-analytics');
    }

    /**
     * @test
     */
    public function it_can_show_report_statistics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/statistics');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-reports-statistics');
    }

    /**
     * @test
     */
    public function it_can_show_report_history()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/history');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-reports-history');
    }

    /**
     * @test
     */
    public function it_can_show_report_export()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/export');

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_export_report_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/reports/export', [
            'format' => 'csv',
            'date_range' => '30_days',
            'include_metadata' => true,
        ]);

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_show_report_import()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/import');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-reports-import');
    }

    /**
     * @test
     */
    public function it_can_import_report_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/reports/import', [
            'import_file' => 'reports_data.csv',
            'overwrite_existing' => false,
        ]);

        $response->assertRedirect('/profile/reports');
    }

    /**
     * @test
     */
    public function it_can_show_report_help()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/help');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-reports-help');
    }

    /**
     * @test
     */
    public function it_can_show_report_support()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/support');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-reports-support');
    }

    /**
     * @test
     */
    public function it_can_submit_report_support()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/reports/support', [
            'subject' => 'Report Issue',
            'message' => 'I need help with my reports.',
            'category' => 'reports',
        ]);

        $response->assertRedirect('/profile/reports/support');
    }

    /**
     * @test
     */
    public function it_can_show_report_privacy()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/privacy');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-reports-privacy');
    }

    /**
     * @test
     */
    public function it_can_update_report_privacy()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/reports/privacy', [
            'share_data' => false,
            'public_reports' => false,
            'data_retention' => '30_days',
        ]);

        $response->assertRedirect('/profile/reports/privacy');
    }

    /**
     * @test
     */
    public function it_can_show_report_security()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/security');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-reports-security');
    }

    /**
     * @test
     */
    public function it_can_update_report_security()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/reports/security', [
            'password_protect' => true,
            'encrypt_reports' => true,
            'access_logging' => true,
        ]);

        $response->assertRedirect('/profile/reports/security');
    }

    /**
     * @test
     */
    public function it_can_show_report_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/backup');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-reports-backup');
    }

    /**
     * @test
     */
    public function it_can_create_report_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/reports/backup');

        $response->assertRedirect('/profile/reports/backup');
    }

    /**
     * @test
     */
    public function it_can_restore_report_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/reports/backup/restore', [
            'backup_file' => 'reports_backup.json',
        ]);

        $response->assertRedirect('/profile/reports/backup');
    }

    /**
     * @test
     */
    public function it_can_show_report_sync()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/sync');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-reports-sync');
    }

    /**
     * @test
     */
    public function it_can_sync_report_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/reports/sync', [
            'sync_type' => 'full',
            'include_metadata' => true,
        ]);

        $response->assertRedirect('/profile/reports/sync');
    }

    /**
     * @test
     */
    public function it_can_show_report_test()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/test');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-reports-test');
    }

    /**
     * @test
     */
    public function it_can_test_report_generation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/reports/test', [
            'test_type' => 'generation',
            'test_data' => 'sample_data',
        ]);

        $response->assertRedirect('/profile/reports/test');
    }

    /**
     * @test
     */
    public function it_can_show_report_advanced()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/reports/advanced');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-reports-advanced');
    }

    /**
     * @test
     */
    public function it_can_update_advanced_report_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/reports/advanced', [
            'debug_mode' => false,
            'developer_mode' => false,
            'beta_features' => true,
        ]);

        $response->assertRedirect('/profile/reports/advanced');
    }

    /**
     * @test
     */
    public function it_handles_guest_users()
    {
        $response = $this->get('/profile/reports');

        $response->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_validates_report_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/reports/generate', [
            'report_type' => 'invalid',
            'date_range' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['report_type', 'date_range']);
    }
}
