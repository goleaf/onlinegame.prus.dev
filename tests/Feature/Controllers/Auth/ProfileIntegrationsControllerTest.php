<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileIntegrationsControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_show_integrations_dashboard()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/integrations');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-integrations');
    }

    /**
     * @test
     */
    public function it_can_show_available_integrations()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/integrations/available');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-integrations-available');
    }

    /**
     * @test
     */
    public function it_can_show_connected_integrations()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/integrations/connected');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-integrations-connected');
    }

    /**
     * @test
     */
    public function it_can_show_integration_details()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/integrations/1');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-integrations-details');
    }

    /**
     * @test
     */
    public function it_can_connect_integration()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/integrations/connect', [
            'integration_type' => 'discord',
            'permissions' => ['read_profile', 'send_messages'],
        ]);

        $response->assertRedirect('/profile/integrations');
    }

    /**
     * @test
     */
    public function it_can_disconnect_integration()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/profile/integrations/1/disconnect');

        $response->assertRedirect('/profile/integrations');
    }

    /**
     * @test
     */
    public function it_can_configure_integration()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/integrations/1/configure', [
            'settings' => ['notifications' => true, 'sync_data' => false],
            'permissions' => ['read_profile' => true, 'send_messages' => false],
        ]);

        $response->assertRedirect('/profile/integrations/1');
    }

    /**
     * @test
     */
    public function it_can_show_integration_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/integrations/1/settings');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-integrations-settings');
    }

    /**
     * @test
     */
    public function it_can_update_integration_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/integrations/1/settings', [
            'notifications' => true,
            'sync_data' => true,
            'privacy_level' => 'public',
            'auto_sync' => false,
        ]);

        $response->assertRedirect('/profile/integrations/1/settings');
    }

    /**
     * @test
     */
    public function it_can_show_integration_permissions()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/integrations/1/permissions');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-integrations-permissions');
    }

    /**
     * @test
     */
    public function it_can_update_integration_permissions()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/integrations/1/permissions', [
            'read_profile' => true,
            'send_messages' => false,
            'access_friends' => true,
            'manage_guild' => false,
        ]);

        $response->assertRedirect('/profile/integrations/1/permissions');
    }

    /**
     * @test
     */
    public function it_can_show_integration_activity()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/integrations/1/activity');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-integrations-activity');
    }

    /**
     * @test
     */
    public function it_can_show_integration_logs()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/integrations/1/logs');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-integrations-logs');
    }

    /**
     * @test
     */
    public function it_can_show_integration_statistics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/integrations/1/statistics');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-integrations-statistics');
    }

    /**
     * @test
     */
    public function it_can_show_integration_help()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/integrations/1/help');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-integrations-help');
    }

    /**
     * @test
     */
    public function it_can_show_integration_support()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/integrations/1/support');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-integrations-support');
    }

    /**
     * @test
     */
    public function it_can_submit_integration_support()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/integrations/1/support', [
            'subject' => 'Integration Issue',
            'message' => 'I need help with my integration.',
            'category' => 'integration',
        ]);

        $response->assertRedirect('/profile/integrations/1/support');
    }

    /**
     * @test
     */
    public function it_can_show_integration_privacy()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/integrations/1/privacy');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-integrations-privacy');
    }

    /**
     * @test
     */
    public function it_can_update_integration_privacy()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/integrations/1/privacy', [
            'share_data' => false,
            'public_profile' => false,
            'data_retention' => '30_days',
        ]);

        $response->assertRedirect('/profile/integrations/1/privacy');
    }

    /**
     * @test
     */
    public function it_can_show_integration_security()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/integrations/1/security');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-integrations-security');
    }

    /**
     * @test
     */
    public function it_can_update_integration_security()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/integrations/1/security', [
            'two_factor_required' => true,
            'session_timeout' => 30,
            'ip_whitelist' => ['192.168.1.1'],
        ]);

        $response->assertRedirect('/profile/integrations/1/security');
    }

    /**
     * @test
     */
    public function it_can_show_integration_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/integrations/1/backup');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-integrations-backup');
    }

    /**
     * @test
     */
    public function it_can_create_integration_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/integrations/1/backup');

        $response->assertRedirect('/profile/integrations/1/backup');
    }

    /**
     * @test
     */
    public function it_can_restore_integration_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/integrations/1/backup/restore', [
            'backup_file' => 'integration_backup.json',
        ]);

        $response->assertRedirect('/profile/integrations/1/backup');
    }

    /**
     * @test
     */
    public function it_can_show_integration_export()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/integrations/1/export');

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_export_integration_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/integrations/1/export', [
            'format' => 'json',
            'include_data' => true,
            'include_settings' => true,
        ]);

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_can_show_integration_import()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/integrations/1/import');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-integrations-import');
    }

    /**
     * @test
     */
    public function it_can_import_integration_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/integrations/1/import', [
            'import_file' => 'integration_data.json',
            'overwrite_existing' => false,
        ]);

        $response->assertRedirect('/profile/integrations/1');
    }

    /**
     * @test
     */
    public function it_can_show_integration_sync()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/integrations/1/sync');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-integrations-sync');
    }

    /**
     * @test
     */
    public function it_can_sync_integration_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/integrations/1/sync', [
            'sync_type' => 'full',
            'include_metadata' => true,
        ]);

        $response->assertRedirect('/profile/integrations/1/sync');
    }

    /**
     * @test
     */
    public function it_can_show_integration_test()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/integrations/1/test');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-integrations-test');
    }

    /**
     * @test
     */
    public function it_can_test_integration_connection()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/integrations/1/test', [
            'test_type' => 'connection',
            'test_data' => 'sample_data',
        ]);

        $response->assertRedirect('/profile/integrations/1/test');
    }

    /**
     * @test
     */
    public function it_can_show_integration_advanced()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/integrations/1/advanced');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-integrations-advanced');
    }

    /**
     * @test
     */
    public function it_can_update_advanced_integration_settings()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/integrations/1/advanced', [
            'debug_mode' => false,
            'developer_mode' => false,
            'beta_features' => true,
        ]);

        $response->assertRedirect('/profile/integrations/1/advanced');
    }

    /**
     * @test
     */
    public function it_handles_guest_users()
    {
        $response = $this->get('/profile/integrations');

        $response->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_validates_integration_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/integrations/connect', [
            'integration_type' => 'invalid',
            'permissions' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['integration_type', 'permissions']);
    }
}
