<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Tests\TestCase;

class UserSimpleTest extends TestCase
{
    /** @test */
    public function it_has_auditing_disabled_method()
    {
        $user = new User();

        $this->assertTrue(method_exists($user, 'auditingDisabled'));
        $this->assertIsBool($user->auditingDisabled());
    }

    /** @test */
    public function it_disables_auditing_for_admin_users()
    {
        $adminUser = new User();
        $adminUser->setAttribute('email', 'admin@example.com');
        
        $regularUser = new User();
        $regularUser->setAttribute('email', 'user@example.com');

        $this->assertTrue($adminUser->auditingDisabled());
        $this->assertFalse($regularUser->auditingDisabled());
    }

    /** @test */
    public function it_disables_auditing_for_system_users()
    {
        $systemUser = new User();
        $systemUser->setAttribute('email', 'system@example.com');
        
        $regularUser = new User();
        $regularUser->setAttribute('email', 'user@example.com');

        $this->assertTrue($systemUser->auditingDisabled());
        $this->assertFalse($regularUser->auditingDisabled());
    }

    /** @test */
    public function it_disables_auditing_for_old_users()
    {
        $oldUser = new User();
        $oldUser->setAttribute('created_at', now()->subYears(2));
        
        $newUser = new User();
        $newUser->setAttribute('created_at', now()->subMonths(6));

        $this->assertTrue($oldUser->auditingDisabled());
        $this->assertFalse($newUser->auditingDisabled());
    }

    /** @test */
    public function it_enables_auditing_by_default()
    {
        $regularUser = new User();
        $regularUser->setAttribute('email', 'regular@example.com');
        $regularUser->setAttribute('created_at', now()->subMonths(6));

        $this->assertFalse($regularUser->auditingDisabled());
    }

    /** @test */
    public function it_excludes_sensitive_attributes_from_audit()
    {
        $user = new User();

        $this->assertContains('password', $user->getAuditExclude());
        $this->assertContains('remember_token', $user->getAuditExclude());
    }

    /** @test */
    public function it_implements_auditable_contract()
    {
        $user = new User();

        $this->assertInstanceOf(\OwenIt\Auditing\Contracts\Auditable::class, $user);
    }

    /** @test */
    public function it_has_fillable_attributes()
    {
        $user = new User();

        $fillable = $user->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
        $this->assertContains('phone', $fillable);
        $this->assertContains('phone_country', $fillable);
        $this->assertContains('reference_number', $fillable);
    }

    /** @test */
    public function it_hides_sensitive_attributes_in_serialization()
    {
        $user = new User();

        $hidden = $user->getHidden();

        $this->assertContains('password', $hidden);
        $this->assertContains('remember_token', $hidden);
    }

    /** @test */
    public function it_has_correct_casts()
    {
        $user = new User();

        $casts = $user->getCasts();

        $this->assertArrayHasKey('email_verified_at', $casts);
        $this->assertArrayHasKey('password', $casts);
        $this->assertArrayHasKey('phone', $casts);
        $this->assertArrayHasKey('reference_number', $casts);
    }

    /** @test */
    public function it_can_use_allowed_filters()
    {
        $user = new User();

        $allowedFilters = $user->allowedFilters();

        $this->assertInstanceOf(\IndexZer0\EloquentFiltering\Filter\Contracts\AllowedFilterList::class, $allowedFilters);
    }
}
