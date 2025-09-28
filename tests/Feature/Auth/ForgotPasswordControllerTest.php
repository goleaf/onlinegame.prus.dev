<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ForgotPasswordControllerTest extends TestCase
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
    public function it_can_send_password_reset_link()
    {
        $response = $this->post('/password/email', [
            'email' => $this->user->email,
        ]);

        $response
            ->assertStatus(302)
            ->assertRedirect('/password/reset')
            ->assertSessionHas('status');
    }

    /**
     * @test
     */
    public function it_validates_email_for_password_reset()
    {
        $response = $this->post('/password/email', [
            'email' => '',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['email']);
    }

    /**
     * @test
     */
    public function it_validates_email_format_for_password_reset()
    {
        $response = $this->post('/password/email', [
            'email' => 'invalid-email',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['email']);
    }

    /**
     * @test
     */
    public function it_handles_non_existent_email_gracefully()
    {
        $response = $this->post('/password/email', [
            'email' => 'nonexistent@example.com',
        ]);

        // Should still redirect to prevent email enumeration
        $response
            ->assertStatus(302)
            ->assertRedirect('/password/reset')
            ->assertSessionHas('status');
    }

    /**
     * @test
     */
    public function it_handles_api_password_reset_request()
    {
        $response = $this->postJson('/api/password/email', [
            'email' => $this->user->email,
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * @test
     */
    public function it_validates_api_email_for_password_reset()
    {
        $response = $this->postJson('/api/password/email', [
            'email' => '',
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
    public function it_validates_api_email_format_for_password_reset()
    {
        $response = $this->postJson('/api/password/email', [
            'email' => 'invalid-email',
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
    public function it_handles_non_existent_email_gracefully_in_api()
    {
        $response = $this->postJson('/api/password/email', [
            'email' => 'nonexistent@example.com',
        ]);

        // Should still return success to prevent email enumeration
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * @test
     */
    public function it_can_check_password_reset_status()
    {
        $response = $this->getJson('/api/password/status');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'status',
                    'message',
                ],
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * @test
     */
    public function it_can_validate_password_reset_token()
    {
        $token = Password::createToken($this->user);

        $response = $this->postJson('/api/password/validate-token', [
            'token' => $token,
            'email' => $this->user->email,
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'valid',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertTrue($response->json('data.valid'));
    }

    /**
     * @test
     */
    public function it_rejects_invalid_password_reset_token()
    {
        $response = $this->postJson('/api/password/validate-token', [
            'token' => 'invalid-token',
            'email' => $this->user->email,
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'valid',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertFalse($response->json('data.valid'));
    }

    /**
     * @test
     */
    public function it_validates_token_validation_request()
    {
        $response = $this->postJson('/api/password/validate-token', [
            'token' => '',
            'email' => '',
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
    public function it_validates_email_format_for_token_validation()
    {
        $response = $this->postJson('/api/password/validate-token', [
            'token' => 'some-token',
            'email' => 'invalid-email',
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
