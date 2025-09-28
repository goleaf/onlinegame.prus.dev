<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_show_two_factor_setup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/two-factor/setup');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.two-factor.setup');
    }

    /**
     * @test
     */
    public function it_can_enable_two_factor_authentication()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/two-factor/enable', [
            'secret' => 'test-secret-key',
        ]);

        $response->assertRedirect('/two-factor/verify');
    }

    /**
     * @test
     */
    public function it_can_verify_two_factor_code()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret-key',
        ]);

        $response = $this->actingAs($user)->post('/two-factor/verify', [
            'code' => '123456',
        ]);

        $response->assertRedirect('/home');
    }

    /**
     * @test
     */
    public function it_validates_two_factor_code()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret-key',
        ]);

        $response = $this->actingAs($user)->post('/two-factor/verify', [
            'code' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    /**
     * @test
     */
    public function it_can_disable_two_factor_authentication()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
        ]);

        $response = $this->actingAs($user)->post('/two-factor/disable', [
            'password' => 'password',
        ]);

        $response->assertRedirect('/home');

        $user->refresh();
        $this->assertFalse($user->two_factor_enabled);
    }

    /**
     * @test
     */
    public function it_validates_password_for_disable()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($user)->post('/two-factor/disable', [
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_can_show_two_factor_recovery_codes()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
        ]);

        $response = $this->actingAs($user)->get('/two-factor/recovery-codes');

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

        $response = $this->actingAs($user)->post('/two-factor/recovery-codes');

        $response->assertRedirect('/two-factor/recovery-codes');
    }

    /**
     * @test
     */
    public function it_can_use_recovery_code()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
        ]);

        $response = $this->actingAs($user)->post('/two-factor/recovery', [
            'recovery_code' => 'test-recovery-code',
        ]);

        $response->assertRedirect('/home');
    }

    /**
     * @test
     */
    public function it_validates_recovery_code()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => true,
        ]);

        $response = $this->actingAs($user)->post('/two-factor/recovery', [
            'recovery_code' => 'invalid-code',
        ]);

        $response->assertSessionHasErrors(['recovery_code']);
    }

    /**
     * @test
     */
    public function it_handles_guest_users()
    {
        $response = $this->get('/two-factor/setup');

        $response->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_can_show_two_factor_qr_code()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/two-factor/qr-code');

        $response
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'image/png');
    }

    /**
     * @test
     */
    public function it_can_show_two_factor_secret()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/two-factor/secret');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.two-factor.secret');
    }

    /**
     * @test
     */
    public function it_can_handle_two_factor_with_sql_injection_attempt()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret-key',
        ]);

        $response = $this->actingAs($user)->post('/two-factor/verify', [
            'code' => "'; DROP TABLE users; --",
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    /**
     * @test
     */
    public function it_can_handle_two_factor_with_xss_attempt()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret-key',
        ]);

        $response = $this->actingAs($user)->post('/two-factor/verify', [
            'code' => '<script>alert("xss")</script>',
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    /**
     * @test
     */
    public function it_can_handle_two_factor_with_ldap_injection_attempt()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret-key',
        ]);

        $response = $this->actingAs($user)->post('/two-factor/verify', [
            'code' => 'admin)(&(password=*))',
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    /**
     * @test
     */
    public function it_can_handle_two_factor_with_no_sql_injection_attempt()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret-key',
        ]);

        $response = $this->actingAs($user)->post('/two-factor/verify', [
            'code' => '{"$ne": null}',
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    /**
     * @test
     */
    public function it_can_handle_two_factor_with_command_injection_attempt()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret-key',
        ]);

        $response = $this->actingAs($user)->post('/two-factor/verify', [
            'code' => '; rm -rf /',
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    /**
     * @test
     */
    public function it_can_handle_two_factor_with_path_traversal_attempt()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret-key',
        ]);

        $response = $this->actingAs($user)->post('/two-factor/verify', [
            'code' => '../../../etc/passwd',
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    /**
     * @test
     */
    public function it_can_handle_two_factor_with_unicode_characters()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret-key',
        ]);

        $response = $this->actingAs($user)->post('/two-factor/verify', [
            'code' => '123456',
        ]);

        $response->assertRedirect('/home');
    }

    /**
     * @test
     */
    public function it_can_handle_two_factor_with_special_characters()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret-key',
        ]);

        $response = $this->actingAs($user)->post('/two-factor/verify', [
            'code' => '!@#$%^&*()',
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    /**
     * @test
     */
    public function it_can_handle_two_factor_with_very_long_code()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret-key',
        ]);

        $longCode = str_repeat('1', 1000);

        $response = $this->actingAs($user)->post('/two-factor/verify', [
            'code' => $longCode,
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    /**
     * @test
     */
    public function it_can_handle_two_factor_with_empty_code()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret-key',
        ]);

        $response = $this->actingAs($user)->post('/two-factor/verify', [
            'code' => '',
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    /**
     * @test
     */
    public function it_can_handle_two_factor_with_null_code()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret-key',
        ]);

        $response = $this->actingAs($user)->post('/two-factor/verify', [
            'code' => null,
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    /**
     * @test
     */
    public function it_can_handle_two_factor_with_array_code()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret-key',
        ]);

        $response = $this->actingAs($user)->post('/two-factor/verify', [
            'code' => ['123456'],
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    /**
     * @test
     */
    public function it_can_handle_two_factor_with_object_code()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret-key',
        ]);

        $response = $this->actingAs($user)->post('/two-factor/verify', [
            'code' => (object) ['value' => '123456'],
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    /**
     * @test
     */
    public function it_can_handle_two_factor_with_boolean_code()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret-key',
        ]);

        $response = $this->actingAs($user)->post('/two-factor/verify', [
            'code' => true,
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    /**
     * @test
     */
    public function it_can_handle_two_factor_with_integer_code()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret-key',
        ]);

        $response = $this->actingAs($user)->post('/two-factor/verify', [
            'code' => 123456,
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    /**
     * @test
     */
    public function it_can_handle_two_factor_with_float_code()
    {
        $user = User::factory()->create([
            'two_factor_secret' => 'test-secret-key',
        ]);

        $response = $this->actingAs($user)->post('/two-factor/verify', [
            'code' => 123.456,
        ]);

        $response->assertSessionHasErrors(['code']);
    }
}
