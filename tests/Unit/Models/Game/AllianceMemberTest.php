<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Alliance;
use App\Models\Game\AllianceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllianceMemberTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_alliance_member()
    {
        $alliance = Alliance::factory()->create();

        $member = AllianceMember::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'role' => 'member',
            'rank' => 'soldier',
            'permissions' => ['view_alliance', 'send_messages'],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 100,
            'is_active' => true,
            'is_online' => false,
            'status' => 'active',
            'notes' => 'Active member',
            'metadata' => ['source' => 'invitation', 'version' => '1.0'],
        ]);

        $this->assertDatabaseHas('alliance_members', [
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'role' => 'member',
            'rank' => 'soldier',
        ]);
    }

    /**
     * @test
     */
    public function it_can_fill_fillable_attributes()
    {
        $alliance = Alliance::factory()->create();

        $member = new AllianceMember([
            'alliance_id' => $alliance->id,
            'user_id' => 2,
            'role' => 'officer',
            'rank' => 'captain',
            'permissions' => ['view_alliance', 'send_messages', 'manage_members'],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 500,
            'is_active' => true,
            'is_online' => true,
            'status' => 'active',
            'notes' => 'Senior officer',
            'metadata' => ['source' => 'promotion', 'version' => '1.1'],
        ]);

        $this->assertEquals($alliance->id, $member->alliance_id);
        $this->assertEquals('officer', $member->role);
        $this->assertEquals('captain', $member->rank);
    }

    /**
     * @test
     */
    public function it_casts_booleans()
    {
        $alliance = Alliance::factory()->create();

        $member = AllianceMember::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'role' => 'member',
            'rank' => 'soldier',
            'permissions' => [],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 0,
            'is_active' => true,
            'is_online' => false,
            'status' => 'active',
            'notes' => '',
            'metadata' => [],
        ]);

        $this->assertTrue($member->is_active);
        $this->assertFalse($member->is_online);
    }

    /**
     * @test
     */
    public function it_casts_dates()
    {
        $alliance = Alliance::factory()->create();

        $member = AllianceMember::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'role' => 'member',
            'rank' => 'soldier',
            'permissions' => [],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 0,
            'is_active' => false,
            'is_online' => false,
            'status' => 'active',
            'notes' => '',
            'metadata' => [],
        ]);

        $this->assertInstanceOf('Carbon\Carbon', $member->joined_at);
        $this->assertInstanceOf('Carbon\Carbon', $member->last_active);
    }

    /**
     * @test
     */
    public function it_casts_json_fields()
    {
        $alliance = Alliance::factory()->create();

        $member = AllianceMember::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'role' => 'member',
            'rank' => 'soldier',
            'permissions' => ['view_alliance', 'send_messages', 'manage_members'],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 0,
            'is_active' => false,
            'is_online' => false,
            'status' => 'active',
            'notes' => '',
            'metadata' => ['source' => 'test', 'version' => '1.0', 'author' => 'system'],
        ]);

        $this->assertIsArray($member->permissions);
        $this->assertIsArray($member->metadata);
    }

    /**
     * @test
     */
    public function it_can_scope_members_by_alliance()
    {
        $alliance1 = Alliance::factory()->create();
        $alliance2 = Alliance::factory()->create();

        AllianceMember::create([
            'alliance_id' => $alliance1->id,
            'user_id' => 1,
            'role' => 'member',
            'rank' => 'soldier',
            'permissions' => [],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 0,
            'is_active' => false,
            'is_online' => false,
            'status' => 'active',
            'notes' => '',
            'metadata' => [],
        ]);

        AllianceMember::create([
            'alliance_id' => $alliance2->id,
            'user_id' => 2,
            'role' => 'member',
            'rank' => 'soldier',
            'permissions' => [],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 0,
            'is_active' => false,
            'is_online' => false,
            'status' => 'active',
            'notes' => '',
            'metadata' => [],
        ]);

        $alliance1Members = AllianceMember::byAlliance($alliance1->id)->get();
        $this->assertCount(1, $alliance1Members);
        $this->assertEquals($alliance1->id, $alliance1Members->first()->alliance_id);
    }

    /**
     * @test
     */
    public function it_can_scope_members_by_role()
    {
        $alliance = Alliance::factory()->create();

        AllianceMember::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'role' => 'member',
            'rank' => 'soldier',
            'permissions' => [],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 0,
            'is_active' => false,
            'is_online' => false,
            'status' => 'active',
            'notes' => '',
            'metadata' => [],
        ]);

        AllianceMember::create([
            'alliance_id' => $alliance->id,
            'user_id' => 2,
            'role' => 'officer',
            'rank' => 'captain',
            'permissions' => [],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 0,
            'is_active' => false,
            'is_online' => false,
            'status' => 'active',
            'notes' => '',
            'metadata' => [],
        ]);

        $officers = AllianceMember::byRole('officer')->get();
        $this->assertCount(1, $officers);
        $this->assertEquals('officer', $officers->first()->role);
    }

    /**
     * @test
     */
    public function it_can_scope_members_by_rank()
    {
        $alliance = Alliance::factory()->create();

        AllianceMember::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'role' => 'member',
            'rank' => 'soldier',
            'permissions' => [],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 0,
            'is_active' => false,
            'is_online' => false,
            'status' => 'active',
            'notes' => '',
            'metadata' => [],
        ]);

        AllianceMember::create([
            'alliance_id' => $alliance->id,
            'user_id' => 2,
            'role' => 'member',
            'rank' => 'captain',
            'permissions' => [],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 0,
            'is_active' => false,
            'is_online' => false,
            'status' => 'active',
            'notes' => '',
            'metadata' => [],
        ]);

        $captains = AllianceMember::byRank('captain')->get();
        $this->assertCount(1, $captains);
        $this->assertEquals('captain', $captains->first()->rank);
    }

    /**
     * @test
     */
    public function it_can_scope_active_members()
    {
        $alliance = Alliance::factory()->create();

        AllianceMember::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'role' => 'member',
            'rank' => 'soldier',
            'permissions' => [],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 0,
            'is_active' => true,
            'is_online' => false,
            'status' => 'active',
            'notes' => '',
            'metadata' => [],
        ]);

        AllianceMember::create([
            'alliance_id' => $alliance->id,
            'user_id' => 2,
            'role' => 'member',
            'rank' => 'soldier',
            'permissions' => [],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 0,
            'is_active' => false,
            'is_online' => false,
            'status' => 'inactive',
            'notes' => '',
            'metadata' => [],
        ]);

        $activeMembers = AllianceMember::active()->get();
        $this->assertCount(1, $activeMembers);
        $this->assertTrue($activeMembers->first()->is_active);
    }

    /**
     * @test
     */
    public function it_can_scope_online_members()
    {
        $alliance = Alliance::factory()->create();

        AllianceMember::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'role' => 'member',
            'rank' => 'soldier',
            'permissions' => [],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 0,
            'is_active' => false,
            'is_online' => true,
            'status' => 'active',
            'notes' => '',
            'metadata' => [],
        ]);

        AllianceMember::create([
            'alliance_id' => $alliance->id,
            'user_id' => 2,
            'role' => 'member',
            'rank' => 'soldier',
            'permissions' => [],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 0,
            'is_active' => false,
            'is_online' => false,
            'status' => 'active',
            'notes' => '',
            'metadata' => [],
        ]);

        $onlineMembers = AllianceMember::online()->get();
        $this->assertCount(1, $onlineMembers);
        $this->assertTrue($onlineMembers->first()->is_online);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_relationship()
    {
        $alliance = Alliance::factory()->create();

        $member = AllianceMember::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'role' => 'member',
            'rank' => 'soldier',
            'permissions' => [],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 0,
            'is_active' => false,
            'is_online' => false,
            'status' => 'active',
            'notes' => '',
            'metadata' => [],
        ]);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\BelongsTo', $member->alliance());
        $this->assertEquals($alliance->id, $member->alliance->id);
    }

    /**
     * @test
     */
    public function it_can_get_member_summary()
    {
        $alliance = Alliance::factory()->create();

        $member = AllianceMember::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'role' => 'member',
            'rank' => 'soldier',
            'permissions' => [],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 100,
            'is_active' => false,
            'is_online' => false,
            'status' => 'active',
            'notes' => 'Active member',
            'metadata' => [],
        ]);

        $summary = $member->getSummary();
        $this->assertIsString($summary);
        $this->assertStringContainsString('member', $summary);
    }

    /**
     * @test
     */
    public function it_can_get_member_details()
    {
        $alliance = Alliance::factory()->create();

        $member = AllianceMember::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'role' => 'member',
            'rank' => 'soldier',
            'permissions' => [],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 100,
            'is_active' => false,
            'is_online' => false,
            'status' => 'active',
            'notes' => 'Active member',
            'metadata' => [],
        ]);

        $details = $member->getDetails();
        $this->assertIsArray($details);
        $this->assertArrayHasKey('role', $details);
        $this->assertArrayHasKey('rank', $details);
    }

    /**
     * @test
     */
    public function it_can_get_member_statistics()
    {
        $alliance = Alliance::factory()->create();

        $member = AllianceMember::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'role' => 'member',
            'rank' => 'soldier',
            'permissions' => [],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 100,
            'is_active' => false,
            'is_online' => false,
            'status' => 'active',
            'notes' => 'Active member',
            'metadata' => [],
        ]);

        $stats = $member->getStatistics();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('contribution_points', $stats);
        $this->assertArrayHasKey('is_active', $stats);
    }

    /**
     * @test
     */
    public function it_can_get_member_timeline()
    {
        $alliance = Alliance::factory()->create();

        $member = AllianceMember::create([
            'alliance_id' => $alliance->id,
            'user_id' => 1,
            'role' => 'member',
            'rank' => 'soldier',
            'permissions' => [],
            'joined_at' => now(),
            'last_active' => now(),
            'contribution_points' => 100,
            'is_active' => false,
            'is_online' => false,
            'status' => 'active',
            'notes' => 'Active member',
            'metadata' => [],
        ]);

        $timeline = $member->getTimeline();
        $this->assertIsArray($timeline);
        $this->assertArrayHasKey('joined_at', $timeline);
        $this->assertArrayHasKey('last_active', $timeline);
    }
}
