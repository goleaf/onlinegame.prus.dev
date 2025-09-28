<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ResetPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

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
    public function it_can_reset_password_with_valid_token()
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
    public function it_validates_required_fields()
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
    public function it_validates_email_format()
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => 'invalid-email',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);

        $response->assertSessionHasErrors(['email']);
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
    public function it_can_handle_strong_password_requirements()
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
    public function it_can_handle_password_with_special_characters()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'P@ssw0rd!@#$%^&*()',
            'password_confirmation' => 'P@ssw0rd!@#$%^&*()',
        ]);

        $response->assertRedirect('/home');

        $user->refresh();
        $this->assertTrue(Hash::check('P@ssw0rd!@#$%^&*()', $user->password));
    }

    /**
     * @test
     */
    public function it_can_handle_unicode_characters_in_password()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Pássw0rd123',
            'password_confirmation' => 'Pássw0rd123',
        ]);

        $response->assertRedirect('/home');

        $user->refresh();
        $this->assertTrue(Hash::check('Pássw0rd123', $user->password));
    }

    /**
     * @test
     */
    public function it_can_handle_long_passwords()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $token = Password::createToken($user);
        $longPassword = str_repeat('A', 100).'123!';

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => $longPassword,
            'password_confirmation' => $longPassword,
        ]);

        $response->assertRedirect('/home');

        $user->refresh();
        $this->assertTrue(Hash::check($longPassword, $user->password));
    }

    /**
     * @test
     */
    public function it_can_handle_case_sensitive_email()
    {
        $user = User::factory()->create([
            'email' => 'Test@Example.com',
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
    public function it_can_handle_email_with_plus_sign()
    {
        $user = User::factory()->create([
            'email' => 'test+tag@example.com',
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => 'test+tag@example.com',
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
    public function it_can_handle_email_with_dots()
    {
        $user = User::factory()->create([
            'email' => 'test.user@example.com',
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => 'test.user@example.com',
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
    public function it_can_handle_international_email()
    {
        $user = User::factory()->create([
            'email' => 'tëst@ëxämplë.com',
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => 'tëst@ëxämplë.com',
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
    public function it_can_handle_very_long_email()
    {
        $longEmail = str_repeat('a', 50).'@'.str_repeat('b', 50).'.com';
        $user = User::factory()->create([
            'email' => $longEmail,
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => $longEmail,
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
    public function it_can_handle_whitespace_in_email()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => ' test@example.com ',
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
    public function it_can_handle_whitespace_in_password()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => ' newpassword ',
            'password_confirmation' => ' newpassword ',
        ]);

        $response->assertRedirect('/home');

        $user->refresh();
        $this->assertTrue(Hash::check(' newpassword ', $user->password));
    }
}
