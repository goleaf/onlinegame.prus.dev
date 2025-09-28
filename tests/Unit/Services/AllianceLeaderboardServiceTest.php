<?php

namespace Tests\Unit\Services;

use App\Services\AllianceLeaderboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AllianceLeaderboardServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard()
    {
        $service = new AllianceLeaderboardService();
        $result = $service->getAllianceLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard_by_points()
    {
        $service = new AllianceLeaderboardService();
        $result = $service->getAllianceLeaderboardByPoints(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard_by_members()
    {
        $service = new AllianceLeaderboardService();
        $result = $service->getAllianceLeaderboardByMembers(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard_by_wars()
    {
        $service = new AllianceLeaderboardService();
        $result = $service->getAllianceLeaderboardByWars(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard_by_peace()
    {
        $service = new AllianceLeaderboardService();
        $result = $service->getAllianceLeaderboardByPeace(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard_by_achievements()
    {
        $service = new AllianceLeaderboardService();
        $result = $service->getAllianceLeaderboardByAchievements(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard_by_contributions()
    {
        $service = new AllianceLeaderboardService();
        $result = $service->getAllianceLeaderboardByContributions(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard_by_activity()
    {
        $service = new AllianceLeaderboardService();
        $result = $service->getAllianceLeaderboardByActivity(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard_by_events()
    {
        $service = new AllianceLeaderboardService();
        $result = $service->getAllianceLeaderboardByEvents(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard_by_announcements()
    {
        $service = new AllianceLeaderboardService();
        $result = $service->getAllianceLeaderboardByAnnouncements(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard_by_invitations()
    {
        $service = new AllianceLeaderboardService();
        $result = $service->getAllianceLeaderboardByInvitations(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard_by_join_requests()
    {
        $service = new AllianceLeaderboardService();
        $result = $service->getAllianceLeaderboardByJoinRequests(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard_by_permissions()
    {
        $service = new AllianceLeaderboardService();
        $result = $service->getAllianceLeaderboardByPermissions(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard_by_roles()
    {
        $service = new AllianceLeaderboardService();
        $result = $service->getAllianceLeaderboardByRoles(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard_by_combined_filters()
    {
        $service = new AllianceLeaderboardService();
        $result = $service->getAllianceLeaderboardByCombinedFilters([
            'points' => 'desc',
            'members' => 'desc',
        ], 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard_by_search()
    {
        $service = new AllianceLeaderboardService();
        $result = $service->getAllianceLeaderboardBySearch('Test', 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard_by_sort()
    {
        $service = new AllianceLeaderboardService();
        $result = $service->getAllianceLeaderboardBySort('points', 'desc', 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard_by_pagination()
    {
        $service = new AllianceLeaderboardService();
        $result = $service->getAllianceLeaderboardByPagination(1, 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_leaderboard_statistics()
    {
        $service = new AllianceLeaderboardService();
        $result = $service->getAllianceLeaderboardStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_alliances', $result);
        $this->assertArrayHasKey('by_points', $result);
        $this->assertArrayHasKey('by_members', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
