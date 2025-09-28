<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Events\Audited;
use OwenIt\Auditing\Models\Audit;
use Tests\TestCase;

class UserAuditingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure we have a clean database state
        $this->refreshDatabase();

        // Enable auditing for tests
        config(['auditing.enabled' => true]);
    }

    /**
     * @test
     */
    public function it_creates_audit_record_when_user_is_created()
    {
        Event::fake([Audited::class]);

        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        Event::assertDispatched(Audited::class);

        $this->assertDatabaseHas('audits', [
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'event' => 'created',
        ]);

        $audit = Audit::where('auditable_id', $user->id)->where('event', 'created')->first();
        $this->assertNotNull($audit);
        $this->assertEquals('created', $audit->event);
    }

    /**
     * @test
     */
    public function it_creates_audit_record_when_user_is_updated()
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        Event::fake([Audited::class]);

        $user->update([
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        Event::assertDispatched(Audited::class);

        $this->assertDatabaseHas('audits', [
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'event' => 'updated',
        ]);

        $audit = Audit::where('auditable_id', $user->id)->where('event', 'updated')->first();
        $this->assertNotNull($audit);
        $this->assertEquals('updated', $audit->event);

        // Check that old values are stored
        $oldValues = json_decode($audit->old_values, true);
        $newValues = json_decode($audit->new_values, true);

        $this->assertEquals('Original Name', $oldValues['name']);
        $this->assertEquals('Updated Name', $newValues['name']);
    }

    /**
     * @test
     */
    public function it_creates_audit_record_when_user_is_deleted()
    {
        $user = User::factory()->create();

        Event::fake([Audited::class]);

        $user->delete();

        Event::assertDispatched(Audited::class);

        $this->assertDatabaseHas('audits', [
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'event' => 'deleted',
        ]);

        $audit = Audit::where('auditable_id', $user->id)->where('event', 'deleted')->first();
        $this->assertNotNull($audit);
        $this->assertEquals('deleted', $audit->event);
    }

    /**
     * @test
     */
    public function it_excludes_password_from_audit_records()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret123',
        ]);

        $audit = Audit::where('auditable_id', $user->id)->where('event', 'created')->first();

        $this->assertNotNull($audit);

        $newValues = json_decode($audit->new_values, true);
        $this->assertArrayNotHasKey('password', $newValues);
    }

    /**
     * @test
     */
    public function it_excludes_remember_token_from_audit_records()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'remember_token' => 'token123',
        ]);

        $audit = Audit::where('auditable_id', $user->id)->where('event', 'created')->first();

        $this->assertNotNull($audit);

        $newValues = json_decode($audit->new_values, true);
        $this->assertArrayNotHasKey('remember_token', $newValues);
    }

    /**
     * @test
     */
    public function it_skips_auditing_when_auditing_disabled_returns_true()
    {
        // Create an admin user (should have auditing disabled)
        $adminUser = User::factory()->create(['email' => 'admin@example.com']);

        // Clear any existing audit records
        Audit::where('auditable_id', $adminUser->id)->delete();

        Event::fake([Audited::class]);

        // Update the admin user
        $adminUser->update(['name' => 'Updated Admin Name']);

        // Should not create audit record for admin users
        Event::assertNotDispatched(Audited::class);

        $this->assertDatabaseMissing('audits', [
            'auditable_type' => User::class,
            'auditable_id' => $adminUser->id,
            'event' => 'updated',
        ]);
    }

    /**
     * @test
     */
    public function it_skips_auditing_for_system_users()
    {
        // Create a system user (should have auditing disabled)
        $systemUser = User::factory()->create(['email' => 'system@example.com']);

        // Clear any existing audit records
        Audit::where('auditable_id', $systemUser->id)->delete();

        Event::fake([Audited::class]);

        // Update the system user
        $systemUser->update(['name' => 'Updated System Name']);

        // Should not create audit record for system users
        Event::assertNotDispatched(Audited::class);

        $this->assertDatabaseMissing('audits', [
            'auditable_type' => User::class,
            'auditable_id' => $systemUser->id,
            'event' => 'updated',
        ]);
    }

    /**
     * @test
     */
    public function it_skips_auditing_for_old_users()
    {
        // Create an old user (should have auditing disabled)
        $oldUser = User::factory()->create([
            'email' => 'old@example.com',
            'created_at' => now()->subYears(2),
        ]);

        // Clear any existing audit records
        Audit::where('auditable_id', $oldUser->id)->delete();

        Event::fake([Audited::class]);

        // Update the old user
        $oldUser->update(['name' => 'Updated Old Name']);

        // Should not create audit record for old users
        Event::assertNotDispatched(Audited::class);

        $this->assertDatabaseMissing('audits', [
            'auditable_type' => User::class,
            'auditable_id' => $oldUser->id,
            'event' => 'updated',
        ]);
    }

    /**
     * @test
     */
    public function it_audits_regular_users_by_default()
    {
        // Create a regular user (should have auditing enabled)
        $regularUser = User::factory()->create([
            'email' => 'regular@example.com',
            'created_at' => now()->subMonths(6),
        ]);

        // Clear any existing audit records
        Audit::where('auditable_id', $regularUser->id)->delete();

        Event::fake([Audited::class]);

        // Update the regular user
        $regularUser->update(['name' => 'Updated Regular Name']);

        // Should create audit record for regular users
        Event::assertDispatched(Audited::class);

        $this->assertDatabaseHas('audits', [
            'auditable_type' => User::class,
            'auditable_id' => $regularUser->id,
            'event' => 'updated',
        ]);
    }

    /**
     * @test
     */
    public function it_stores_audit_metadata_correctly()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $audit = Audit::where('auditable_id', $user->id)->where('event', 'created')->first();

        $this->assertNotNull($audit);
        $this->assertEquals(User::class, $audit->auditable_type);
        $this->assertEquals($user->id, $audit->auditable_id);
        $this->assertEquals('created', $audit->event);
        $this->assertNotNull($audit->created_at);
        $this->assertNotNull($audit->updated_at);
    }

    /**
     * @test
     */
    public function it_tracks_phone_number_changes_in_audit()
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'phone_country' => 'US',
        ]);

        $user->update([
            'phone' => '+0987654321',
            'phone_country' => 'CA',
        ]);

        $audit = Audit::where('auditable_id', $user->id)->where('event', 'updated')->first();

        $this->assertNotNull($audit);

        $oldValues = json_decode($audit->old_values, true);
        $newValues = json_decode($audit->new_values, true);

        $this->assertEquals('+1234567890', $oldValues['phone']);
        $this->assertEquals('+0987654321', $newValues['phone']);
        $this->assertEquals('US', $oldValues['phone_country']);
        $this->assertEquals('CA', $newValues['phone_country']);
    }

    /**
     * @test
     */
    public function it_handles_multiple_audit_events_correctly()
    {
        $user = User::factory()->create(['name' => 'Original Name']);

        // Update 1
        $user->update(['name' => 'First Update']);

        // Update 2
        $user->update(['name' => 'Second Update']);

        // Delete
        $user->delete();

        $audits = Audit::where('auditable_id', $user->id)->orderBy('created_at')->get();

        $this->assertCount(3, $audits);
        $this->assertEquals('created', $audits[0]->event);
        $this->assertEquals('updated', $audits[1]->event);
        $this->assertEquals('updated', $audits[2]->event);

        // Check that we have the correct number of audit records
        $this->assertEquals(1, Audit::where('auditable_id', $user->id)->where('event', 'created')->count());
        $this->assertEquals(2, Audit::where('auditable_id', $user->id)->where('event', 'updated')->count());
    }

    /**
     * @test
     */
    public function it_preserves_audit_records_after_user_deletion()
    {
        $user = User::factory()->create(['name' => 'Test User']);

        $user->update(['name' => 'Updated User']);

        $auditCountBefore = Audit::where('auditable_id', $user->id)->count();

        $user->delete();

        // Audit records should still exist after user deletion
        $auditCountAfter = Audit::where('auditable_id', $user->id)->count();
        $this->assertEquals($auditCountBefore + 1, $auditCountAfter);  // +1 for the delete event

        // The user should be soft deleted (if using soft deletes) or hard deleted
        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        // But audit records should remain
        $this->assertGreaterThan(0, Audit::where('auditable_id', $user->id)->count());
    }

    /**
     * @test
     */
    public function it_handles_auditing_disabled_during_creation()
    {
        Event::fake([Audited::class]);

        // Create an admin user (auditing should be disabled)
        $adminUser = User::factory()->create(['email' => 'admin@example.com']);

        // Should not create audit record for admin users during creation
        Event::assertNotDispatched(Audited::class);

        $this->assertDatabaseMissing('audits', [
            'auditable_type' => User::class,
            'auditable_id' => $adminUser->id,
            'event' => 'created',
        ]);
    }

    /**
     * @test
     */
    public function it_can_retrieve_audit_history_for_user()
    {
        $user = User::factory()->create(['name' => 'Test User']);

        $user->update(['name' => 'Updated User']);
        $user->update(['email' => 'updated@example.com']);

        // Get audit history
        $audits = $user->audits;

        $this->assertCount(3, $audits);  // created + 2 updates
        $this->assertEquals('created', $audits[0]->event);
        $this->assertEquals('updated', $audits[1]->event);
        $this->assertEquals('updated', $audits[2]->event);
    }
}
