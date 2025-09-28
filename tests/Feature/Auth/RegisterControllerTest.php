<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

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
        Event::fake();

        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post('/register', $userData);

        $response
            ->assertStatus(302)
            ->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);

        $user = User::where('email', $userData['email'])->first();
        $this->assertTrue(Hash::check($userData['password'], $user->password));

        Event::assertDispatched(Registered::class);
    }

    /**
     * @test
     */
    public function it_validates_registration_data()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
            'password_confirmation' => '456',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    /**
     * @test
     */
    public function it_rejects_duplicate_email()
    {
        $existingUser = User::factory()->create();

        $response = $this->post('/register', [
            'name' => $this->faker->name,
            'email' => $existingUser->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
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
        $response = $this->post('/register', [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
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
        $response = $this->post('/register', [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
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
    public function it_requires_name_field()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['name']);
    }

    /**
     * @test
     */
    public function it_requires_valid_email()
    {
        $response = $this->post('/register', [
            'name' => $this->faker->name,
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['email']);
    }

    /**
     * @test
     */
    public function it_handles_api_registration_request()
    {
        Event::fake();

        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/register', $userData);

        $response
            ->assertStatus(201)
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
                ],
            ]);

        $this->assertTrue($response->json('success'));

        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);

        Event::assertDispatched(Registered::class);
    }

    /**
     * @test
     */
    public function it_validates_api_registration_data()
    {
        $response = $this->postJson('/api/register', [
            'name' => '',
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
    public function it_rejects_duplicate_email_in_api()
    {
        $existingUser = User::factory()->create();

        $response = $this->postJson('/api/register', [
            'name' => $this->faker->name,
            'email' => $existingUser->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
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
    public function it_requires_password_confirmation_in_api()
    {
        $response = $this->postJson('/api/register', [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'different-password',
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
    public function it_requires_minimum_password_length_in_api()
    {
        $response = $this->postJson('/api/register', [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => '123',
            'password_confirmation' => '123',
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
