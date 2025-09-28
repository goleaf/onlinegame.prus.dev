<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class VerificationControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_show_verification_notice()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.verify');
    }

    /**
     * @test
     */
    public function it_can_send_verification_email()
    {
        Mail::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->post('/email/verification-notification');

        $response
            ->assertRedirect()
            ->assertSessionHas('status', 'Verification link sent!');

        Mail::assertSent(\Illuminate\Auth\Notifications\VerifyEmail::class);
    }

    /**
     * @test
     */
    public function it_can_verify_email_with_valid_token()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($verificationUrl);

        $response->assertRedirect('/home');

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    /**
     * @test
     */
    public function it_handles_invalid_verification_token()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->get('/email/verify/invalid-token');

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function it_handles_expired_verification_token()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $expiredUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->get($expiredUrl);

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function it_handles_already_verified_user()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertRedirect('/home');
    }

    /**
     * @test
     */
    public function it_handles_guest_users()
    {
        $response = $this->get('/email/verify');

        $response->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_can_resend_verification_email()
    {
        Mail::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->post('/email/verification-notification');

        $response
            ->assertRedirect()
            ->assertSessionHas('status', 'Verification link sent!');

        Mail::assertSent(\Illuminate\Auth\Notifications\VerifyEmail::class);
    }

    /**
     * @test
     */
    public function it_can_handle_verification_with_different_email()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('different@example.com')]
        );

        $response = $this->get($verificationUrl);

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function it_can_handle_verification_with_different_user_id()
    {
        $user1 = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $user2 = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user2->id, 'hash' => sha1($user1->email)]
        );

        $response = $this->get($verificationUrl);

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function it_can_handle_verification_with_malformed_url()
    {
        $response = $this->get('/email/verify/malformed-url');

        $response->assertStatus(404);
    }

    /**
     * @test
     */
    public function it_can_handle_verification_with_missing_parameters()
    {
        $response = $this->get('/email/verify/');

        $response->assertStatus(404);
    }

    /**
     * @test
     */
    public function it_can_handle_verification_with_invalid_hash()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => 'invalid-hash']
        );

        $response = $this->get($verificationUrl);

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function it_can_handle_verification_with_nonexistent_user()
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => 99999, 'hash' => sha1('test@example.com')]
        );

        $response = $this->get($verificationUrl);

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function it_can_handle_verification_with_sql_injection_attempt()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => "'; DROP TABLE users; --"]
        );

        $response = $this->get($verificationUrl);

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function it_can_handle_verification_with_xss_attempt()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => '<script>alert("xss")</script>']
        );

        $response = $this->get($verificationUrl);

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function it_can_handle_verification_with_ldap_injection_attempt()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => 'admin)(&(email=*))']
        );

        $response = $this->get($verificationUrl);

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function it_can_handle_verification_with_no_sql_injection_attempt()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => '{"$ne": null}']
        );

        $response = $this->get($verificationUrl);

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function it_can_handle_verification_with_command_injection_attempt()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => '; rm -rf /']
        );

        $response = $this->get($verificationUrl);

        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function it_can_handle_verification_with_path_traversal_attempt()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => '../../../etc/passwd']
        );

        $response = $this->get($verificationUrl);

        $response->assertStatus(403);
    }
}
