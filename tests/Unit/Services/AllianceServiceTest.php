<?php

namespace Tests\Unit\Services;

use App\Models\Game\Alliance;
use App\Models\Game\Player;
use App\Services\AllianceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AllianceServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_alliance()
    {
        $player = Player::factory()->create();
        $data = [
            'name' => 'Test Alliance',
            'tag' => 'TEST',
            'description' => 'Test alliance description',
        ];

        $service = new AllianceService();
        $result = $service->createAlliance($player, $data);

        $this->assertInstanceOf(Alliance::class, $result);
        $this->assertEquals($data['name'], $result->name);
        $this->assertEquals($data['tag'], $result->tag);
        $this->assertEquals($player->id, $result->leader_id);
    }

    /**
     * @test
     */
    public function it_can_join_alliance()
    {
        $player = Player::factory()->create();
        $alliance = Alliance::factory()->create();

        $service = new AllianceService();
        $result = $service->joinAlliance($player, $alliance);

        $this->assertTrue($result);
        $this->assertEquals($alliance->id, $player->alliance_id);
    }

    /**
     * @test
     */
    public function it_can_leave_alliance()
    {
        $player = Player::factory()->create(['alliance_id' => 1]);
        $alliance = Alliance::factory()->create(['id' => 1]);

        $service = new AllianceService();
        $result = $service->leaveAlliance($player, $alliance);

        $this->assertTrue($result);
        $this->assertNull($player->alliance_id);
    }

    /**
     * @test
     */
    public function it_can_kick_member()
    {
        $leader = Player::factory()->create();
        $member = Player::factory()->create(['alliance_id' => 1]);
        $alliance = Alliance::factory()->create(['leader_id' => $leader->id]);

        $service = new AllianceService();
        $result = $service->kickMember($leader, $member, $alliance);

        $this->assertTrue($result);
        $this->assertNull($member->alliance_id);
    }

    /**
     * @test
     */
    public function it_can_promote_member()
    {
        $leader = Player::factory()->create();
        $member = Player::factory()->create(['alliance_id' => 1, 'alliance_role' => 'member']);
        $alliance = Alliance::factory()->create(['leader_id' => $leader->id]);

        $service = new AllianceService();
        $result = $service->promoteMember($leader, $member, $alliance, 'officer');

        $this->assertTrue($result);
        $this->assertEquals('officer', $member->alliance_role);
    }

    /**
     * @test
     */
    public function it_can_demote_member()
    {
        $leader = Player::factory()->create();
        $member = Player::factory()->create(['alliance_id' => 1, 'alliance_role' => 'officer']);
        $alliance = Alliance::factory()->create(['leader_id' => $leader->id]);

        $service = new AllianceService();
        $result = $service->demoteMember($leader, $member, $alliance);

        $this->assertTrue($result);
        $this->assertEquals('member', $member->alliance_role);
    }

    /**
     * @test
     */
    public function it_can_transfer_leadership()
    {
        $leader = Player::factory()->create();
        $member = Player::factory()->create(['alliance_id' => 1]);
        $alliance = Alliance::factory()->create(['leader_id' => $leader->id]);

        $service = new AllianceService();
        $result = $service->transferLeadership($leader, $member, $alliance);

        $this->assertTrue($result);
        $this->assertEquals($member->id, $alliance->leader_id);
        $this->assertEquals('leader', $member->alliance_role);
    }

    /**
     * @test
     */
    public function it_can_update_alliance_settings()
    {
        $leader = Player::factory()->create();
        $alliance = Alliance::factory()->create(['leader_id' => $leader->id]);
        $settings = [
            'name' => 'Updated Alliance',
            'description' => 'Updated description',
            'recruitment_open' => true,
        ];

        $service = new AllianceService();
        $result = $service->updateAllianceSettings($leader, $alliance, $settings);

        $this->assertTrue($result);
        $this->assertEquals($settings['name'], $alliance->name);
        $this->assertEquals($settings['description'], $alliance->description);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_members()
    {
        $alliance = Alliance::factory()->create();
        $members = collect([
            Player::factory()->create(['alliance_id' => $alliance->id]),
            Player::factory()->create(['alliance_id' => $alliance->id]),
        ]);

        $alliance->shouldReceive('members')->andReturn($members);

        $service = new AllianceService();
        $result = $service->getAllianceMembers($alliance);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics()
    {
        $alliance = Alliance::factory()->create();
        $members = collect([
            Player::factory()->create(['alliance_id' => $alliance->id, 'points' => 1000]),
            Player::factory()->create(['alliance_id' => $alliance->id, 'points' => 2000]),
        ]);

        $alliance->shouldReceive('members')->andReturn($members);

        $service = new AllianceService();
        $result = $service->getAllianceStatistics($alliance);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_members', $result);
        $this->assertArrayHasKey('total_points', $result);
        $this->assertArrayHasKey('average_points', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_rankings()
    {
        $service = new AllianceService();
        $result = $service->getAllianceRankings(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_search_alliances()
    {
        $service = new AllianceService();
        $result = $service->searchAlliances('test', 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_news()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceNews($alliance, 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_post_alliance_news()
    {
        $player = Player::factory()->create(['alliance_id' => 1]);
        $alliance = Alliance::factory()->create(['id' => 1]);
        $data = [
            'title' => 'Test News',
            'content' => 'Test news content',
        ];

        $service = new AllianceService();
        $result = $service->postAllianceNews($player, $alliance, $data);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_diplomacy()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceDiplomacy($alliance);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_update_diplomacy()
    {
        $player = Player::factory()->create(['alliance_id' => 1]);
        $alliance = Alliance::factory()->create(['id' => 1]);
        $targetAlliance = Alliance::factory()->create();
        $data = [
            'status' => 'war',
            'message' => 'Declaration of war',
        ];

        $service = new AllianceService();
        $result = $service->updateDiplomacy($player, $alliance, $targetAlliance, $data);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_wars()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceWars($alliance);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_peace_treaties()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAlliancePeaceTreaties($alliance);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_nap_agreements()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceNapAgreements($alliance);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_member_activity()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceMemberActivity($alliance, 7);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_contribution_rankings()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceContributionRankings($alliance);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_achievements()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceAchievements($alliance);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_events()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceEvents($alliance, 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_announcements()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceAnnouncements($alliance, 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_post_alliance_announcement()
    {
        $player = Player::factory()->create(['alliance_id' => 1]);
        $alliance = Alliance::factory()->create(['id' => 1]);
        $data = [
            'title' => 'Test Announcement',
            'content' => 'Test announcement content',
        ];

        $service = new AllianceService();
        $result = $service->postAllianceAnnouncement($player, $alliance, $data);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_invitations()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceInvitations($alliance);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_send_alliance_invitation()
    {
        $player = Player::factory()->create(['alliance_id' => 1]);
        $alliance = Alliance::factory()->create(['id' => 1]);
        $targetPlayer = Player::factory()->create();

        $service = new AllianceService();
        $result = $service->sendAllianceInvitation($player, $alliance, $targetPlayer);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_accept_alliance_invitation()
    {
        $player = Player::factory()->create();
        $alliance = Alliance::factory()->create();

        $service = new AllianceService();
        $result = $service->acceptAllianceInvitation($player, $alliance);

        $this->assertTrue($result);
        $this->assertEquals($alliance->id, $player->alliance_id);
    }

    /**
     * @test
     */
    public function it_can_reject_alliance_invitation()
    {
        $player = Player::factory()->create();
        $alliance = Alliance::factory()->create();

        $service = new AllianceService();
        $result = $service->rejectAllianceInvitation($player, $alliance);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_join_requests()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceJoinRequests($alliance);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_send_alliance_join_request()
    {
        $player = Player::factory()->create();
        $alliance = Alliance::factory()->create();

        $service = new AllianceService();
        $result = $service->sendAllianceJoinRequest($player, $alliance);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_approve_alliance_join_request()
    {
        $leader = Player::factory()->create();
        $player = Player::factory()->create();
        $alliance = Alliance::factory()->create(['leader_id' => $leader->id]);

        $service = new AllianceService();
        $result = $service->approveAllianceJoinRequest($leader, $player, $alliance);

        $this->assertTrue($result);
        $this->assertEquals($alliance->id, $player->alliance_id);
    }

    /**
     * @test
     */
    public function it_can_reject_alliance_join_request()
    {
        $leader = Player::factory()->create();
        $player = Player::factory()->create();
        $alliance = Alliance::factory()->create(['leader_id' => $leader->id]);

        $service = new AllianceService();
        $result = $service->rejectAllianceJoinRequest($leader, $player, $alliance);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_permissions()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAlliancePermissions($alliance);

        $this->assertIsArray($result);
    }

    /**
     * @test
     */
    public function it_can_update_alliance_permissions()
    {
        $leader = Player::factory()->create();
        $alliance = Alliance::factory()->create(['leader_id' => $leader->id]);
        $permissions = [
            'can_invite' => true,
            'can_kick' => false,
            'can_promote' => true,
        ];

        $service = new AllianceService();
        $result = $service->updateAlliancePermissions($leader, $alliance, $permissions);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_roles()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceRoles($alliance);

        $this->assertIsArray($result);
    }

    /**
     * @test
     */
    public function it_can_create_alliance_role()
    {
        $leader = Player::factory()->create();
        $alliance = Alliance::factory()->create(['leader_id' => $leader->id]);
        $data = [
            'name' => 'Test Role',
            'permissions' => ['can_invite' => true],
        ];

        $service = new AllianceService();
        $result = $service->createAllianceRole($leader, $alliance, $data);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_update_alliance_role()
    {
        $leader = Player::factory()->create();
        $alliance = Alliance::factory()->create(['leader_id' => $leader->id]);
        $role = (object) ['id' => 1];
        $data = [
            'name' => 'Updated Role',
            'permissions' => ['can_invite' => false],
        ];

        $service = new AllianceService();
        $result = $service->updateAllianceRole($leader, $alliance, $role, $data);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_delete_alliance_role()
    {
        $leader = Player::factory()->create();
        $alliance = Alliance::factory()->create(['leader_id' => $leader->id]);
        $role = (object) ['id' => 1];

        $service = new AllianceService();
        $result = $service->deleteAllianceRole($leader, $alliance, $role);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_assign_alliance_role()
    {
        $leader = Player::factory()->create();
        $member = Player::factory()->create(['alliance_id' => 1]);
        $alliance = Alliance::factory()->create(['leader_id' => $leader->id, 'id' => 1]);
        $role = (object) ['id' => 1];

        $service = new AllianceService();
        $result = $service->assignAllianceRole($leader, $member, $alliance, $role);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_remove_alliance_role()
    {
        $leader = Player::factory()->create();
        $member = Player::factory()->create(['alliance_id' => 1]);
        $alliance = Alliance::factory()->create(['leader_id' => $leader->id, 'id' => 1]);

        $service = new AllianceService();
        $result = $service->removeAllianceRole($leader, $member, $alliance);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_activity_log()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceActivityLog($alliance, 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_log_alliance_activity()
    {
        $player = Player::factory()->create(['alliance_id' => 1]);
        $alliance = Alliance::factory()->create(['id' => 1]);
        $data = [
            'action' => 'member_joined',
            'details' => 'Player joined the alliance',
        ];

        $service = new AllianceService();
        $result = $service->logAllianceActivity($player, $alliance, $data);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics_summary()
    {
        $service = new AllianceService();
        $result = $service->getAllianceStatisticsSummary();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_alliances', $result);
        $this->assertArrayHasKey('total_members', $result);
        $this->assertArrayHasKey('average_members', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard()
    {
        $service = new AllianceService();
        $result = $service->getAllianceLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_war_statistics()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceWarStatistics($alliance);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('wars_won', $result);
        $this->assertArrayHasKey('wars_lost', $result);
        $this->assertArrayHasKey('wars_draw', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_peace_statistics()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAlliancePeaceStatistics($alliance);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('peace_treaties', $result);
        $this->assertArrayHasKey('nap_agreements', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_member_contributions()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceMemberContributions($alliance);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_achievement_progress()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceAchievementProgress($alliance);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_event_statistics()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceEventStatistics($alliance);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_events', $result);
        $this->assertArrayHasKey('recent_events', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_announcement_statistics()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceAnnouncementStatistics($alliance);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_announcements', $result);
        $this->assertArrayHasKey('recent_announcements', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_invitation_statistics()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceInvitationStatistics($alliance);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_invitations', $result);
        $this->assertArrayHasKey('pending_invitations', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_join_request_statistics()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceJoinRequestStatistics($alliance);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_requests', $result);
        $this->assertArrayHasKey('pending_requests', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_permission_statistics()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAlliancePermissionStatistics($alliance);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_permissions', $result);
        $this->assertArrayHasKey('active_permissions', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_role_statistics()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceRoleStatistics($alliance);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_roles', $result);
        $this->assertArrayHasKey('active_roles', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_activity_statistics()
    {
        $alliance = Alliance::factory()->create();
        $service = new AllianceService();
        $result = $service->getAllianceActivityStatistics($alliance);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_activities', $result);
        $this->assertArrayHasKey('recent_activities', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard_statistics()
    {
        $service = new AllianceService();
        $result = $service->getAllianceLeaderboardStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_alliances', $result);
        $this->assertArrayHasKey('ranked_alliances', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_war_leaderboard()
    {
        $service = new AllianceService();
        $result = $service->getAllianceWarLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_peace_leaderboard()
    {
        $service = new AllianceService();
        $result = $service->getAlliancePeaceLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_member_leaderboard()
    {
        $service = new AllianceService();
        $result = $service->getAllianceMemberLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_contribution_leaderboard()
    {
        $service = new AllianceService();
        $result = $service->getAllianceContributionLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_achievement_leaderboard()
    {
        $service = new AllianceService();
        $result = $service->getAllianceAchievementLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_event_leaderboard()
    {
        $service = new AllianceService();
        $result = $service->getAllianceEventLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_announcement_leaderboard()
    {
        $service = new AllianceService();
        $result = $service->getAllianceAnnouncementLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_invitation_leaderboard()
    {
        $service = new AllianceService();
        $result = $service->getAllianceInvitationLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_join_request_leaderboard()
    {
        $service = new AllianceService();
        $result = $service->getAllianceJoinRequestLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_permission_leaderboard()
    {
        $service = new AllianceService();
        $result = $service->getAlliancePermissionLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_role_leaderboard()
    {
        $service = new AllianceService();
        $result = $service->getAllianceRoleLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_activity_leaderboard()
    {
        $service = new AllianceService();
        $result = $service->getAllianceActivityLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
