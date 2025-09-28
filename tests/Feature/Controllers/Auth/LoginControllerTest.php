<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_show_login_form()
    {
        $response = $this->get('/login');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.login');
    }

    /**
     * @test
     */
    public function it_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * @test
     */
    public function it_cannot_login_with_invalid_email()
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * @test
     */
    public function it_cannot_login_with_invalid_password()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * @test
     */
    public function it_validates_required_fields()
    {
        $response = $this->post('/login', []);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    /**
     * @test
     */
    public function it_validates_email_format()
    {
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * @test
     */
    public function it_can_login_with_remember_me()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    /**
     * @test
     */
    public function it_redirects_authenticated_users()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/dashboard');
    }

    /**
     * @test
     */
    public function it_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /**
     * @test
     */
    public function it_handles_logout_for_guests()
    {
        $response = $this->post('/logout');

        $response->assertRedirect('/');
    }

    /**
     * @test
     */
    public function it_can_show_login_form_with_redirect()
    {
        $response = $this->get('/login?redirect=/profile');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.login')
            ->assertViewHas('redirect', '/profile');
    }

    /**
     * @test
     */
    public function it_redirects_to_intended_after_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Visit a protected page first
        $this->get('/profile');
        $this->assertGuest();

        // Now login
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/profile');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * @test
     */
    public function it_handles_account_locked_out()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'locked_until' => now()->addMinutes(15),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * @test
     */
    public function it_handles_inactive_account()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => null,
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/email/verify');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * @test
     */
    public function it_handles_banned_account()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'banned_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * @test
     */
    public function it_tracks_login_attempts()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        // After 3 failed attempts, account should be locked
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /**
     * @test
     */
    public function it_can_reset_login_attempts_on_success()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        // Make some failed attempts
        $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        // Successful login should reset attempts
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * @test
     */
    public function it_can_handle_two_factor_authentication()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'two_factor_enabled' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/two-factor-challenge');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * @test
     */
    public function it_can_handle_social_login_redirect()
    {
        $response = $this->get('/login/google');

        $response->assertRedirect();
    }

    /**
     * @test
     */
    public function it_can_handle_social_login_callback()
    {
        $response = $this->get('/login/google/callback');

        $response->assertStatus(200);
    }
}
