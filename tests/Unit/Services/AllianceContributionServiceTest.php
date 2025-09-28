<?php

namespace Tests\Unit\Services;

use App\Services\AllianceContributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AllianceContributionServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_alliance_contributions()
    {
        $service = new AllianceContributionService();
        $result = $service->getAllianceContributions(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('contributions', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_contribution_details()
    {
        $service = new AllianceContributionService();
        $result = $service->getAllianceContributionDetails(1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('contribution', $result);
        $this->assertArrayHasKey('alliance', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_contribution_statistics()
    {
        $service = new AllianceContributionService();
        $result = $service->getAllianceContributionStatistics(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_contributions', $result);
        $this->assertArrayHasKey('by_type', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_contribution_events()
    {
        $service = new AllianceContributionService();
        $result = $service->getAllianceContributionEvents(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('events', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_contribution_announcements()
    {
        $service = new AllianceContributionService();
        $result = $service->getAllianceContributionAnnouncements(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('announcements', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_contribution_invitations()
    {
        $service = new AllianceContributionService();
        $result = $service->getAllianceContributionInvitations(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('invitations', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_contribution_join_requests()
    {
        $service = new AllianceContributionService();
        $result = $service->getAllianceContributionJoinRequests(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('join_requests', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_contribution_permissions()
    {
        $service = new AllianceContributionService();
        $result = $service->getAllianceContributionPermissions(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('permissions', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_contribution_roles()
    {
        $service = new AllianceContributionService();
        $result = $service->getAllianceContributionRoles(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_contribution_combined_filters()
    {
        $service = new AllianceContributionService();
        $result = $service->getAllianceContributionCombinedFilters(1, [
            'status' => 'active',
            'type' => 'contribution',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_contribution_search()
    {
        $service = new AllianceContributionService();
        $result = $service->getAllianceContributionSearch(1, 'Test');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_contribution_sort()
    {
        $service = new AllianceContributionService();
        $result = $service->getAllianceContributionSort(1, 'points', 'desc');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_points', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_contribution_pagination()
    {
        $service = new AllianceContributionService();
        $result = $service->getAllianceContributionPagination(1, 1, 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
