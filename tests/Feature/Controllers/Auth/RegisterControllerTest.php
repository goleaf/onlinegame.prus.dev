<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_show_registration_form()
    {
        $response = $this->get('/register');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.register');
    }

    /**
     * @test
     */
    public function it_can_register_new_user()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /**
     * @test
     */
    public function it_validates_required_fields()
    {
        $response = $this->post('/register', []);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }

    /**
     * @test
     */
    public function it_validates_email_format()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * @test
     */
    public function it_validates_password_confirmation()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ];

        $response = $this->post('/register', $userData);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_validates_password_length()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ];

        $response = $this->post('/register', $userData);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_validates_unique_email()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * @test
     */
    public function it_validates_name_length()
    {
        $userData = [
            'name' => str_repeat('a', 256),
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        $response->assertSessionHasErrors(['name']);
    }

    /**
     * @test
     */
    public function it_can_register_with_optional_fields()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'username' => 'johndoe',
            'phone' => '+1234567890',
        ];

        $response = $this->post('/register', $userData);

        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'username' => 'johndoe',
            'phone' => '+1234567890',
        ]);
    }

    /**
     * @test
     */
    public function it_can_register_with_terms_acceptance()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => true,
        ];

        $response = $this->post('/register', $userData);

        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'terms_accepted_at' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @test
     */
    public function it_requires_terms_acceptance()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => false,
        ];

        $response = $this->post('/register', $userData);

        $response->assertSessionHasErrors(['terms']);
    }

    /**
     * @test
     */
    public function it_can_register_with_newsletter_subscription()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'newsletter' => true,
        ];

        $response = $this->post('/register', $userData);

        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'newsletter_subscribed' => true,
        ]);
    }

    /**
     * @test
     */
    public function it_can_register_with_referral_code()
    {
        $referrer = User::factory()->create(['referral_code' => 'REF123']);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'referral_code' => 'REF123',
        ];

        $response = $this->post('/register', $userData);

        $response->assertRedirect('/dashboard');

        $user = User::where('email', 'john@example.com')->first();
        $this->assertEquals($referrer->id, $user->referred_by);
    }

    /**
     * @test
     */
    public function it_handles_invalid_referral_code()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'referral_code' => 'INVALID123',
        ];

        $response = $this->post('/register', $userData);

        $response->assertSessionHasErrors(['referral_code']);
    }

    /**
     * @test
     */
    public function it_can_register_with_captcha()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'captcha' => 'valid-captcha-response',
        ];

        $response = $this->post('/register', $userData);

        $response->assertRedirect('/dashboard');
    }

    /**
     * @test
     */
    public function it_handles_captcha_validation()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'captcha' => 'invalid-captcha-response',
        ];

        $response = $this->post('/register', $userData);

        $response->assertSessionHasErrors(['captcha']);
    }

    /**
     * @test
     */
    public function it_can_register_with_social_provider()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'social_provider' => 'google',
            'social_id' => 'google-123',
        ];

        $response = $this->post('/register', $userData);

        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'social_provider' => 'google',
            'social_id' => 'google-123',
        ]);
    }

    /**
     * @test
     */
    public function it_can_register_with_timezone()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'timezone' => 'America/New_York',
        ];

        $response = $this->post('/register', $userData);

        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'timezone' => 'America/New_York',
        ]);
    }

    /**
     * @test
     */
    public function it_can_register_with_language_preference()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'language' => 'en',
        ];

        $response = $this->post('/register', $userData);

        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'language' => 'en',
        ]);
    }

    /**
     * @test
     */
    public function it_dispatches_registered_event()
    {
        Event::fake();

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->post('/register', $userData);

        Event::assertDispatched(\Illuminate\Auth\Events\Registered::class);
    }

    /**
     * @test
     */
    public function it_redirects_authenticated_users()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/register');

        $response->assertRedirect('/dashboard');
    }

    /**
     * @test
     */
    public function it_can_register_with_verification_email()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        $response->assertRedirect('/email/verify');

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNull($user->email_verified_at);
    }

    /**
     * @test
     */
    public function it_can_register_without_verification()
    {
        config(['auth.verification.enabled' => false]);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        $response->assertRedirect('/dashboard');

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user->email_verified_at);
    }
}
