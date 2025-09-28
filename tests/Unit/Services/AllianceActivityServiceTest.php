<?php

namespace Tests\Unit\Services;

use App\Services\AllianceActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AllianceActivityServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_alliance_activities()
    {
        $service = new AllianceActivityService();
        $result = $service->getAllianceActivities(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('activities', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_activity_details()
    {
        $service = new AllianceActivityService();
        $result = $service->getAllianceActivityDetails(1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('activity', $result);
        $this->assertArrayHasKey('alliance', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_activity_statistics()
    {
        $service = new AllianceActivityService();
        $result = $service->getAllianceActivityStatistics(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_activities', $result);
        $this->assertArrayHasKey('by_action', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_activity_events()
    {
        $service = new AllianceActivityService();
        $result = $service->getAllianceActivityEvents(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('events', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_activity_announcements()
    {
        $service = new AllianceActivityService();
        $result = $service->getAllianceActivityAnnouncements(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('announcements', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_activity_invitations()
    {
        $service = new AllianceActivityService();
        $result = $service->getAllianceActivityInvitations(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('invitations', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_activity_join_requests()
    {
        $service = new AllianceActivityService();
        $result = $service->getAllianceActivityJoinRequests(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('join_requests', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_activity_permissions()
    {
        $service = new AllianceActivityService();
        $result = $service->getAllianceActivityPermissions(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('permissions', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_activity_roles()
    {
        $service = new AllianceActivityService();
        $result = $service->getAllianceActivityRoles(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_activity_combined_filters()
    {
        $service = new AllianceActivityService();
        $result = $service->getAllianceActivityCombinedFilters(1, [
            'status' => 'active',
            'type' => 'activity',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_activity_search()
    {
        $service = new AllianceActivityService();
        $result = $service->getAllianceActivitySearch(1, 'Test');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_activity_sort()
    {
        $service = new AllianceActivityService();
        $result = $service->getAllianceActivitySort(1, 'points', 'desc');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_points', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_activity_pagination()
    {
        $service = new AllianceActivityService();
        $result = $service->getAllianceActivityPagination(1, 1, 10);

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
