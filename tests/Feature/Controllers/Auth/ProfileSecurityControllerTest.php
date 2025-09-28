<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileSecurityControllerTest extends TestCase
{
    use RefreshDatabase;

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
    public function it_can_update_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->actingAs($user)->put('/profile/security/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response->assertRedirect('/profile/security');

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

        $response = $this->actingAs($user)->put('/profile/security/password', [
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

        $response = $this->actingAs($user)->put('/profile/security/password', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_can_enable_two_factor_authentication()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/security/two-factor/enable');

        $response->assertRedirect('/profile/security');
    }

    /**
     * @test
     */
    public function it_can_disable_two_factor_authentication()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
        ]);

        $response = $this->actingAs($user)->post('/profile/security/two-factor/disable', [
            'password' => 'password',
        ]);

        $response->assertRedirect('/profile/security');

        $user->refresh();
        $this->assertFalse($user->two_factor_enabled);
    }

    /**
     * @test
     */
    public function it_can_show_two_factor_setup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/security/two-factor/setup');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.two-factor.setup');
    }

    /**
     * @test
     */
    public function it_can_verify_two_factor_code()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret-key',
        ]);

        $response = $this->actingAs($user)->post('/profile/security/two-factor/verify', [
            'code' => '123456',
        ]);

        $response->assertRedirect('/profile/security');
    }

    /**
     * @test
     */
    public function it_can_show_recovery_codes()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
        ]);

        $response = $this->actingAs($user)->get('/profile/security/two-factor/recovery-codes');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.two-factor.recovery-codes');
    }

    /**
     * @test
     */
    public function it_can_generate_new_recovery_codes()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
        ]);

        $response = $this->actingAs($user)->post('/profile/security/two-factor/recovery-codes');

        $response->assertRedirect('/profile/security/two-factor/recovery-codes');
    }

    /**
     * @test
     */
    public function it_can_show_active_sessions()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/security/sessions');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-sessions');
    }

    /**
     * @test
     */
    public function it_can_revoke_all_sessions()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/profile/security/sessions');

        $response->assertRedirect('/profile/security/sessions');
    }

    /**
     * @test
     */
    public function it_can_revoke_specific_session()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/profile/security/sessions/1');

        $response->assertRedirect('/profile/security/sessions');
    }

    /**
     * @test
     */
    public function it_can_show_login_history()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/security/login-history');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-login-history');
    }

    /**
     * @test
     */
    public function it_can_show_security_logs()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/security/logs');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-security-logs');
    }

    /**
     * @test
     */
    public function it_can_show_trusted_devices()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/security/devices');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-devices');
    }

    /**
     * @test
     */
    public function it_can_remove_trusted_device()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete('/profile/security/devices/1');

        $response->assertRedirect('/profile/security/devices');
    }

    /**
     * @test
     */
    public function it_can_show_security_alerts()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/security/alerts');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-security-alerts');
    }

    /**
     * @test
     */
    public function it_can_update_security_preferences()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/security/preferences', [
            'login_notifications' => true,
            'security_alerts' => true,
            'suspicious_activity_alerts' => true,
        ]);

        $response->assertRedirect('/profile/security');
    }

    /**
     * @test
     */
    public function it_can_show_security_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/security/backup');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-security-backup');
    }

    /**
     * @test
     */
    public function it_can_create_security_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/security/backup');

        $response->assertRedirect('/profile/security/backup');
    }

    /**
     * @test
     */
    public function it_can_restore_security_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/security/backup/restore', [
            'backup_file' => 'security_backup.json',
        ]);

        $response->assertRedirect('/profile/security/backup');
    }

    /**
     * @test
     */
    public function it_can_show_security_statistics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/security/statistics');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-security-statistics');
    }

    /**
     * @test
     */
    public function it_can_show_security_help()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/security/help');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-security-help');
    }

    /**
     * @test
     */
    public function it_can_show_security_support()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/security/support');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-security-support');
    }

    /**
     * @test
     */
    public function it_can_submit_security_support()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/security/support', [
            'subject' => 'Security Issue',
            'message' => 'I need help with my security settings.',
            'category' => 'security',
        ]);

        $response->assertRedirect('/profile/security/support');
    }

    /**
     * @test
     */
    public function it_handles_guest_users()
    {
        $response = $this->get('/profile/security');

        $response->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_validates_security_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile/security/preferences', [
            'login_notifications' => 'invalid',
            'security_alerts' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['login_notifications', 'security_alerts']);
    }

    /**
     * @test
     */
    public function it_can_show_security_audit()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/security/audit');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-security-audit');
    }

    /**
     * @test
     */
    public function it_can_show_security_compliance()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile/security/compliance');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.profile-security-compliance');
    }
}
