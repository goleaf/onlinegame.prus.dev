<?php

namespace Tests\Unit\Services;

use App\Services\AllianceEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AllianceEventServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_alliance_events()
    {
        $service = new AllianceEventService();
        $result = $service->getAllianceEvents(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('events', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_event_details()
    {
        $service = new AllianceEventService();
        $result = $service->getAllianceEventDetails(1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('event', $result);
        $this->assertArrayHasKey('alliance', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_event_statistics()
    {
        $service = new AllianceEventService();
        $result = $service->getAllianceEventStatistics(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_events', $result);
        $this->assertArrayHasKey('by_type', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_event_announcements()
    {
        $service = new AllianceEventService();
        $result = $service->getAllianceEventAnnouncements(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('announcements', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_event_invitations()
    {
        $service = new AllianceEventService();
        $result = $service->getAllianceEventInvitations(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('invitations', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_event_join_requests()
    {
        $service = new AllianceEventService();
        $result = $service->getAllianceEventJoinRequests(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('join_requests', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_event_permissions()
    {
        $service = new AllianceEventService();
        $result = $service->getAllianceEventPermissions(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('permissions', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_event_roles()
    {
        $service = new AllianceEventService();
        $result = $service->getAllianceEventRoles(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_event_combined_filters()
    {
        $service = new AllianceEventService();
        $result = $service->getAllianceEventCombinedFilters(1, [
            'status' => 'active',
            'type' => 'event',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_event_search()
    {
        $service = new AllianceEventService();
        $result = $service->getAllianceEventSearch(1, 'Test');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_event_sort()
    {
        $service = new AllianceEventService();
        $result = $service->getAllianceEventSort(1, 'points', 'desc');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_points', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_event_pagination()
    {
        $service = new AllianceEventService();
        $result = $service->getAllianceEventPagination(1, 1, 10);

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
