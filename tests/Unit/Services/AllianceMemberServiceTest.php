<?php

namespace Tests\Unit\Services;

use App\Models\Game\Alliance;
use App\Models\Game\AllianceMember;
use App\Models\Game\Player;
use App\Services\AllianceMemberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AllianceMemberServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_add_member()
    {
        $player = Player::factory()->create();
        $alliance = Alliance::factory()->create();
        $newMember = Player::factory()->create();
        $data = [
            'alliance_id' => $alliance->id,
            'player_id' => $newMember->id,
            'role' => 'member',
            'permissions' => ['can_invite' => false, 'can_kick' => false],
        ];

        $service = new AllianceMemberService();
        $result = $service->addMember($player, $alliance, $newMember, $data);

        $this->assertInstanceOf(AllianceMember::class, $result);
        $this->assertEquals($data['alliance_id'], $result->alliance_id);
        $this->assertEquals($data['player_id'], $result->player_id);
        $this->assertEquals($data['role'], $result->role);
    }

    /**
     * @test
     */
    public function it_can_remove_member()
    {
        $player = Player::factory()->create();
        $alliance = Alliance::factory()->create();
        $member = AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
        ]);

        $service = new AllianceMemberService();
        $result = $service->removeMember($player, $alliance, $member);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_update_member_role()
    {
        $player = Player::factory()->create();
        $alliance = Alliance::factory()->create();
        $member = AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
            'role' => 'member',
        ]);

        $service = new AllianceMemberService();
        $result = $service->updateMemberRole($player, $alliance, $member, 'officer');

        $this->assertTrue($result);
        $this->assertEquals('officer', $member->role);
    }

    /**
     * @test
     */
    public function it_can_update_member_permissions()
    {
        $player = Player::factory()->create();
        $alliance = Alliance::factory()->create();
        $member = AllianceMember::factory()->create([
            'alliance_id' => $alliance->id,
        ]);
        $permissions = ['can_invite' => true, 'can_kick' => false];

        $service = new AllianceMemberService();
        $result = $service->updateMemberPermissions($player, $alliance, $member, $permissions);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_members()
    {
        $alliance = Alliance::factory()->create();
        $members = collect([
            AllianceMember::factory()->create(['alliance_id' => $alliance->id]),
            AllianceMember::factory()->create(['alliance_id' => $alliance->id]),
        ]);

        $alliance->shouldReceive('members')->andReturn($members);

        $service = new AllianceMemberService();
        $result = $service->getAllianceMembers($alliance);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_member_by_role()
    {
        $alliance = Alliance::factory()->create();
        $members = collect([
            AllianceMember::factory()->create(['alliance_id' => $alliance->id, 'role' => 'member']),
            AllianceMember::factory()->create(['alliance_id' => $alliance->id, 'role' => 'officer']),
        ]);

        $alliance->shouldReceive('members')->andReturn($members);

        $service = new AllianceMemberService();
        $result = $service->getMemberByRole($alliance, 'member');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_member_by_status()
    {
        $alliance = Alliance::factory()->create();
        $members = collect([
            AllianceMember::factory()->create(['alliance_id' => $alliance->id, 'status' => 'active']),
            AllianceMember::factory()->create(['alliance_id' => $alliance->id, 'status' => 'inactive']),
        ]);

        $alliance->shouldReceive('members')->andReturn($members);

        $service = new AllianceMemberService();
        $result = $service->getMemberByStatus($alliance, 'active');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_member_by_join_date()
    {
        $alliance = Alliance::factory()->create();
        $members = collect([
            AllianceMember::factory()->create(['alliance_id' => $alliance->id, 'joined_at' => now()]),
            AllianceMember::factory()->create(['alliance_id' => $alliance->id, 'joined_at' => now()->subDays(1)]),
        ]);

        $alliance->shouldReceive('members')->andReturn($members);

        $service = new AllianceMemberService();
        $result = $service->getMemberByJoinDate($alliance, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_member_by_leave_date()
    {
        $alliance = Alliance::factory()->create();
        $members = collect([
            AllianceMember::factory()->create(['alliance_id' => $alliance->id, 'left_at' => now()]),
            AllianceMember::factory()->create(['alliance_id' => $alliance->id, 'left_at' => now()->subDays(1)]),
        ]);

        $alliance->shouldReceive('members')->andReturn($members);

        $service = new AllianceMemberService();
        $result = $service->getMemberByLeaveDate($alliance, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_member_by_combined_filters()
    {
        $alliance = Alliance::factory()->create();
        $members = collect([
            AllianceMember::factory()->create(['alliance_id' => $alliance->id, 'role' => 'member', 'status' => 'active']),
            AllianceMember::factory()->create(['alliance_id' => $alliance->id, 'role' => 'officer', 'status' => 'inactive']),
        ]);

        $alliance->shouldReceive('members')->andReturn($members);

        $service = new AllianceMemberService();
        $result = $service->getMemberByCombinedFilters($alliance, [
            'role' => 'member',
            'status' => 'active',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_member_by_search()
    {
        $alliance = Alliance::factory()->create();
        $members = collect([
            AllianceMember::factory()->create(['alliance_id' => $alliance->id, 'player_name' => 'Test Player']),
            AllianceMember::factory()->create(['alliance_id' => $alliance->id, 'player_name' => 'Another Player']),
        ]);

        $alliance->shouldReceive('members')->andReturn($members);

        $service = new AllianceMemberService();
        $result = $service->getMemberBySearch($alliance, 'Test');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_member_by_sort()
    {
        $alliance = Alliance::factory()->create();
        $members = collect([
            AllianceMember::factory()->create(['alliance_id' => $alliance->id, 'joined_at' => now()]),
            AllianceMember::factory()->create(['alliance_id' => $alliance->id, 'joined_at' => now()->addDays(1)]),
        ]);

        $alliance->shouldReceive('members')->andReturn($members);

        $service = new AllianceMemberService();
        $result = $service->getMemberBySort($alliance, 'joined_at', 'desc');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_member_by_pagination()
    {
        $alliance = Alliance::factory()->create();
        $members = collect([
            AllianceMember::factory()->create(['alliance_id' => $alliance->id]),
            AllianceMember::factory()->create(['alliance_id' => $alliance->id]),
        ]);

        $alliance->shouldReceive('members')->andReturn($members);

        $service = new AllianceMemberService();
        $result = $service->getMemberByPagination($alliance, 1, 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_member_statistics()
    {
        $service = new AllianceMemberService();
        $result = $service->getMemberStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_members', $result);
        $this->assertArrayHasKey('by_role', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_member_leaderboard()
    {
        $service = new AllianceMemberService();
        $result = $service->getMemberLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
