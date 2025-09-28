<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ForgotPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_show_forgot_password_form()
    {
        $response = $this->get('/password/reset');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.passwords.email');
    }

    /**
     * @test
     */
    public function it_can_send_password_reset_email()
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->post('/password/email', [
            'email' => 'test@example.com',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('status', 'We have emailed your password reset link.');

        Mail::assertSent(\Illuminate\Auth\Notifications\ResetPassword::class);
    }

    /**
     * @test
     */
    public function it_validates_email_required()
    {
        $response = $this->post('/password/email', []);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * @test
     */
    public function it_validates_email_format()
    {
        $response = $this->post('/password/email', [
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * @test
     */
    public function it_handles_nonexistent_email()
    {
        $response = $this->post('/password/email', [
            'email' => 'nonexistent@example.com',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('status', 'We have emailed your password reset link.');
    }

    /**
     * @test
     */
    public function it_can_show_reset_password_form()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->get('/password/reset/'.$token);

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.passwords.reset')
            ->assertViewHas('token', $token);
    }

    /**
     * @test
     */
    public function it_can_reset_password()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertRedirect('/home');

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword', $user->password));
    }

    /**
     * @test
     */
    public function it_validates_reset_form_required_fields()
    {
        $response = $this->post('/password/reset', []);

        $response->assertSessionHasErrors(['token', 'email', 'password']);
    }

    /**
     * @test
     */
    public function it_validates_password_confirmation()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_validates_password_length()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_handles_invalid_token()
    {
        $user = User::factory()->create();

        $response = $this->post('/password/reset', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * @test
     */
    public function it_handles_expired_token()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        // Mock expired token
        Password::shouldReceive('reset')->andReturn(Password::INVALID_TOKEN);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * @test
     */
    public function it_handles_invalid_email()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => 'wrong@example.com',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * @test
     */
    public function it_can_handle_rate_limiting()
    {
        $user = User::factory()->create();

        // Make multiple requests
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/password/email', [
                'email' => $user->email,
            ]);
        }

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * @test
     */
    public function it_can_handle_captcha_validation()
    {
        $user = User::factory()->create();

        $response = $this->post('/password/email', [
            'email' => $user->email,
            'captcha' => 'invalid-captcha',
        ]);

        $response->assertSessionHasErrors(['captcha']);
    }

    /**
     * @test
     */
    public function it_can_send_reset_email_with_custom_message()
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->post('/password/email', [
            'email' => 'test@example.com',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertSent(\Illuminate\Auth\Notifications\ResetPassword::class, function ($mail) use ($user) {
            return $mail->user->id === $user->id;
        });
    }

    /**
     * @test
     */
    public function it_can_handle_user_without_email_verification()
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $response = $this->post('/password/email', [
            'email' => 'test@example.com',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('status', 'We have emailed your password reset link.');
    }

    /**
     * @test
     */
    public function it_can_handle_banned_user()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'banned_at' => now(),
        ]);

        $response = $this->post('/password/email', [
            'email' => 'test@example.com',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('status', 'We have emailed your password reset link.');
    }

    /**
     * @test
     */
    public function it_can_show_reset_form_with_correct_email()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $token = Password::createToken($user);

        $response = $this->get('/password/reset/'.$token.'?email='.urlencode($user->email));

        $response
            ->assertStatus(200)
            ->assertViewHas('email', $user->email);
    }

    /**
     * @test
     */
    public function it_can_handle_reset_with_strong_password()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
        ]);

        $response->assertRedirect('/home');

        $user->refresh();
        $this->assertTrue(Hash::check('StrongPassword123!', $user->password));
    }

    /**
     * @test
     */
    public function it_can_handle_reset_with_special_characters_in_email()
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'test+tag@example.com',
        ]);

        $response = $this->post('/password/email', [
            'email' => 'test+tag@example.com',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHas('status', 'We have emailed your password reset link.');
    }
}
