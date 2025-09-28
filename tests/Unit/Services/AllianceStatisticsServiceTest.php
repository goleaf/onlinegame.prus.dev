<?php

namespace Tests\Unit\Services;

use App\Services\AllianceStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AllianceStatisticsServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_alliance_statistics()
    {
        $service = new AllianceStatisticsService();
        $result = $service->getAllianceStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_alliances', $result);
        $this->assertArrayHasKey('by_status', $result);
        $this->assertArrayHasKey('by_members', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics_by_status()
    {
        $service = new AllianceStatisticsService();
        $result = $service->getAllianceStatisticsByStatus();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('active', $result);
        $this->assertArrayHasKey('inactive', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics_by_members()
    {
        $service = new AllianceStatisticsService();
        $result = $service->getAllianceStatisticsByMembers();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_members', $result);
        $this->assertArrayHasKey('average_members', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics_by_wars()
    {
        $service = new AllianceStatisticsService();
        $result = $service->getAllianceStatisticsByWars();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_wars', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics_by_peace()
    {
        $service = new AllianceStatisticsService();
        $result = $service->getAllianceStatisticsByPeace();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_peace', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics_by_achievements()
    {
        $service = new AllianceStatisticsService();
        $result = $service->getAllianceStatisticsByAchievements();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_achievements', $result);
        $this->assertArrayHasKey('by_type', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics_by_contributions()
    {
        $service = new AllianceStatisticsService();
        $result = $service->getAllianceStatisticsByContributions();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_contributions', $result);
        $this->assertArrayHasKey('by_type', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics_by_activity()
    {
        $service = new AllianceStatisticsService();
        $result = $service->getAllianceStatisticsByActivity();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_activities', $result);
        $this->assertArrayHasKey('by_action', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics_by_events()
    {
        $service = new AllianceStatisticsService();
        $result = $service->getAllianceStatisticsByEvents();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_events', $result);
        $this->assertArrayHasKey('by_type', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics_by_announcements()
    {
        $service = new AllianceStatisticsService();
        $result = $service->getAllianceStatisticsByAnnouncements();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_announcements', $result);
        $this->assertArrayHasKey('by_type', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics_by_invitations()
    {
        $service = new AllianceStatisticsService();
        $result = $service->getAllianceStatisticsByInvitations();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_invitations', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics_by_join_requests()
    {
        $service = new AllianceStatisticsService();
        $result = $service->getAllianceStatisticsByJoinRequests();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_join_requests', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics_by_permissions()
    {
        $service = new AllianceStatisticsService();
        $result = $service->getAllianceStatisticsByPermissions();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_permissions', $result);
        $this->assertArrayHasKey('by_type', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics_by_roles()
    {
        $service = new AllianceStatisticsService();
        $result = $service->getAllianceStatisticsByRoles();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_roles', $result);
        $this->assertArrayHasKey('by_type', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics_by_combined_filters()
    {
        $service = new AllianceStatisticsService();
        $result = $service->getAllianceStatisticsByCombinedFilters([
            'status' => 'active',
            'members' => '>10',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_alliances', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics_by_search()
    {
        $service = new AllianceStatisticsService();
        $result = $service->getAllianceStatisticsBySearch('Test');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_alliances', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics_by_sort()
    {
        $service = new AllianceStatisticsService();
        $result = $service->getAllianceStatisticsBySort('points', 'desc');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_alliances', $result);
        $this->assertArrayHasKey('by_points', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics_by_pagination()
    {
        $service = new AllianceStatisticsService();
        $result = $service->getAllianceStatisticsByPagination(1, 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_alliances', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
