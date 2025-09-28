<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ResetPasswordControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * @test
     */
    public function it_can_show_reset_password_form()
    {
        $response = $this->get('/password/reset');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.passwords.reset');
    }

    /**
     * @test
     */
    public function it_can_show_reset_password_form_with_token()
    {
        $token = 'test-token';
        $email = $this->user->email;

        $response = $this->get("/password/reset/{$token}?email={$email}");

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.passwords.reset')
            ->assertViewHas('token', $token)
            ->assertViewHas('email', $email);
    }

    /**
     * @test
     */
    public function it_can_reset_password_with_valid_token()
    {
        Event::fake();

        $token = Password::createToken($this->user);
        $newPassword = 'new-password-123';

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => $this->user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');

        $this->assertTrue(Hash::check($newPassword, $this->user->fresh()->password));

        Event::assertDispatched(PasswordReset::class);
    }

    /**
     * @test
     */
    public function it_validates_reset_password_request()
    {
        $response = $this->post('/password/reset', [
            'token' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '456',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['token', 'email', 'password']);
    }

    /**
     * @test
     */
    public function it_rejects_invalid_token()
    {
        $response = $this->post('/password/reset', [
            'token' => 'invalid-token',
            'email' => $this->user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['email']);
    }

    /**
     * @test
     */
    public function it_rejects_expired_token()
    {
        $token = Password::createToken($this->user);

        // Simulate token expiration by creating a new token (invalidates the old one)
        Password::createToken($this->user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['email']);
    }

    /**
     * @test
     */
    public function it_rejects_non_existent_email()
    {
        $token = Password::createToken($this->user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => 'nonexistent@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['email']);
    }

    /**
     * @test
     */
    public function it_requires_password_confirmation()
    {
        $token = Password::createToken($this->user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'different-password',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_requires_minimum_password_length()
    {
        $token = Password::createToken($this->user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => $this->user->email,
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_handles_reset_password_api_request()
    {
        Event::fake();

        $token = Password::createToken($this->user);
        $newPassword = 'new-password-123';

        $response = $this->postJson('/api/password/reset', [
            'token' => $token,
            'email' => $this->user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertTrue(Hash::check($newPassword, $this->user->fresh()->password));

        Event::assertDispatched(PasswordReset::class);
    }

    /**
     * @test
     */
    public function it_validates_api_reset_password_request()
    {
        $response = $this->postJson('/api/password/reset', [
            'token' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '456',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);

        $this->assertFalse($response->json('success'));
    }

    /**
     * @test
     */
    public function it_rejects_invalid_token_in_api()
    {
        $response = $this->postJson('/api/password/reset', [
            'token' => 'invalid-token',
            'email' => $this->user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);

        $this->assertFalse($response->json('success'));
    }
}
