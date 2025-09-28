<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);
    }

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
        $response = $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(302)
            ->assertRedirect('/dashboard');

        $this->assertTrue(Auth::check());
        $this->assertEquals($this->user->id, Auth::id());
    }

    /**
     * @test
     */
    public function it_rejects_invalid_credentials()
    {
        $response = $this->post('/login', [
            'email' => $this->user->email,
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['email']);

        $this->assertFalse(Auth::check());
    }

    /**
     * @test
     */
    public function it_rejects_non_existent_email()
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['email']);

        $this->assertFalse(Auth::check());
    }

    /**
     * @test
     */
    public function it_validates_login_request()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => '',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['email', 'password']);
    }

    /**
     * @test
     */
    public function it_requires_valid_email_format()
    {
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['email']);
    }

    /**
     * @test
     */
    public function it_handles_api_login_request()
    {
        $response = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'created_at',
                        'updated_at',
                    ],
                    'token',
                ],
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertTrue(Auth::check());
    }

    /**
     * @test
     */
    public function it_validates_api_login_request()
    {
        $response = $this->postJson('/api/login', [
            'email' => '',
            'password' => '',
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
    public function it_rejects_invalid_credentials_in_api()
    {
        $response = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(401)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertFalse($response->json('success'));
        $this->assertFalse(Auth::check());
    }

    /**
     * @test
     */
    public function it_rejects_non_existent_email_in_api()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertStatus(401)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertFalse($response->json('success'));
        $this->assertFalse(Auth::check());
    }

    /**
     * @test
     */
    public function it_requires_valid_email_format_in_api()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
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
    public function it_can_logout()
    {
        $this->actingAs($this->user);

        $response = $this->post('/logout');

        $response
            ->assertStatus(302)
            ->assertRedirect('/');

        $this->assertFalse(Auth::check());
    }

    /**
     * @test
     */
    public function it_can_logout_via_api()
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/logout');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertTrue($response->json('success'));
        $this->assertFalse(Auth::check());
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_logout()
    {
        $response = $this->post('/logout');

        $response
            ->assertStatus(302)
            ->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_requires_authentication_for_api_logout()
    {
        $response = $this->postJson('/api/logout');

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized',
            ]);
    }
}
