<?php

namespace Tests\Feature\Controllers\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConfirmPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_show_confirm_password_form()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/password/confirm');

        $response
            ->assertStatus(200)
            ->assertViewIs('auth.passwords.confirm');
    }

    /**
     * @test
     */
    public function it_can_confirm_password_with_valid_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => 'password123',
        ]);

        $response->assertRedirect('/home');
    }

    /**
     * @test
     */
    public function it_validates_password_required()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/password/confirm', []);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_validates_incorrect_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_handles_guest_users()
    {
        $response = $this->get('/password/confirm');

        $response->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_remember_me()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertRedirect('/home');
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_without_remember_me()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => 'password123',
            'remember' => false,
        ]);

        $response->assertRedirect('/home');
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_whitespace()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => ' password123 ',
        ]);

        $response->assertRedirect('/home');
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_special_characters()
    {
        $user = User::factory()->create([
            'password' => Hash::make('P@ssw0rd!@#$%^&*()'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => 'P@ssw0rd!@#$%^&*()',
        ]);

        $response->assertRedirect('/home');
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_unicode_characters()
    {
        $user = User::factory()->create([
            'password' => Hash::make('Pássw0rd123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => 'Pássw0rd123',
        ]);

        $response->assertRedirect('/home');
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_very_long_password()
    {
        $longPassword = str_repeat('A', 100).'123!';
        $user = User::factory()->create([
            'password' => Hash::make($longPassword),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => $longPassword,
        ]);

        $response->assertRedirect('/home');
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_empty_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_null_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => null,
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_array_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => ['password123'],
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_object_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => (object) ['value' => 'password123'],
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_boolean_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => true,
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_integer_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => 123456,
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_float_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => 123.456,
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_json_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => json_encode(['password' => 'password123']),
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_xml_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => '<password>password123</password>',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_sql_injection_attempt()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => "'; DROP TABLE users; --",
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_xss_attempt()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => '<script>alert("xss")</script>',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_ldap_injection_attempt()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => 'admin)(&(password=*))',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_no_sql_injection_attempt()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => '{"$ne": null}',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_command_injection_attempt()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => '; rm -rf /',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * @test
     */
    public function it_can_handle_password_confirmation_with_path_traversal_attempt()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->post('/password/confirm', [
            'password' => '../../../etc/passwd',
        ]);

        $response->assertSessionHasErrors(['password']);
    }
}
