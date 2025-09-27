<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserAuditingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_auditing_disabled_method()
    {
        $user = User::factory()->create();

        $this->assertTrue(method_exists($user, 'auditingDisabled'));
        $this->assertIsBool($user->auditingDisabled());
    }

    /** @test */
    public function it_disables_auditing_for_admin_users()
    {
        $adminUser = User::factory()->create(['email' => 'admin@example.com']);
        $regularUser = User::factory()->create(['email' => 'user@example.com']);

        $this->assertTrue($adminUser->auditingDisabled());
        $this->assertFalse($regularUser->auditingDisabled());
    }

    /** @test */
    public function it_disables_auditing_for_system_users()
    {
        $systemUser = User::factory()->create(['email' => 'system@example.com']);
        $regularUser = User::factory()->create(['email' => 'user@example.com']);

        $this->assertTrue($systemUser->auditingDisabled());
        $this->assertFalse($regularUser->auditingDisabled());
    }

    /** @test */
    public function it_disables_auditing_for_old_users()
    {
        $oldUser = User::factory()->create(['created_at' => now()->subYears(2)]);
        $newUser = User::factory()->create(['created_at' => now()->subMonths(6)]);

        $this->assertTrue($oldUser->auditingDisabled());
        $this->assertFalse($newUser->auditingDisabled());
    }

    /** @test */
    public function it_enables_auditing_by_default()
    {
        $regularUser = User::factory()->create([
            'email' => 'regular@example.com',
            'created_at' => now()->subMonths(6),
        ]);

        $this->assertFalse($regularUser->auditingDisabled());
    }

    /** @test */
    public function it_excludes_sensitive_attributes_from_audit()
    {
        $user = User::factory()->create([
            'password' => 'secret123',
            'remember_token' => 'token123',
        ]);

        $this->assertContains('password', $user->getAuditExclude());
        $this->assertContains('remember_token', $user->getAuditExclude());
    }

    /** @test */
    public function it_implements_auditable_contract()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\OwenIt\Auditing\Contracts\Auditable::class, $user);
    }

    /** @test */
    public function it_can_be_mass_assigned()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'phone' => '+1234567890',
            'phone_country' => 'US',
            'reference_number' => 'REF123',
        ];

        $user = User::create($userData);

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('+1234567890', $user->phone);
        $this->assertEquals('US', $user->phone_country);
        $this->assertEquals('REF123', $user->reference_number);
    }

    /** @test */
    public function it_hides_sensitive_attributes_in_serialization()
    {
        $user = User::factory()->create([
            'password' => 'secret123',
            'remember_token' => 'token123',
        ]);

        $hidden = $user->getHidden();

        $this->assertContains('password', $hidden);
        $this->assertContains('remember_token', $hidden);
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => 'password123',
            'phone' => '+1234567890',
            'phone_country' => 'US',
            'reference_number' => 'REF123',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $user->email_verified_at);
        $this->assertIsString($user->reference_number);
    }

    /** @test */
    public function it_can_create_a_user()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ];

        $user = User::create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertEquals('+1234567890', $user->phone);
        $this->assertEquals('US', $user->phone_country);
    }

    /** @test */
    public function it_can_update_user_information()
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $user->update([
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $this->assertEquals('Updated Name', $user->fresh()->name);
        $this->assertEquals('updated@example.com', $user->fresh()->email);
    }

    /** @test */
    public function it_can_delete_a_user()
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
