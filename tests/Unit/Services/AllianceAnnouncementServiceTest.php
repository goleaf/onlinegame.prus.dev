<?php

namespace Tests\Unit\Services;

use App\Services\AllianceAnnouncementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AllianceAnnouncementServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_alliance_announcements()
    {
        $service = new AllianceAnnouncementService();
        $result = $service->getAllianceAnnouncements(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('announcements', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_announcement_details()
    {
        $service = new AllianceAnnouncementService();
        $result = $service->getAllianceAnnouncementDetails(1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('announcement', $result);
        $this->assertArrayHasKey('alliance', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_announcement_statistics()
    {
        $service = new AllianceAnnouncementService();
        $result = $service->getAllianceAnnouncementStatistics(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_announcements', $result);
        $this->assertArrayHasKey('by_type', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_announcement_invitations()
    {
        $service = new AllianceAnnouncementService();
        $result = $service->getAllianceAnnouncementInvitations(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('invitations', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_announcement_join_requests()
    {
        $service = new AllianceAnnouncementService();
        $result = $service->getAllianceAnnouncementJoinRequests(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('join_requests', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_announcement_permissions()
    {
        $service = new AllianceAnnouncementService();
        $result = $service->getAllianceAnnouncementPermissions(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('permissions', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_announcement_roles()
    {
        $service = new AllianceAnnouncementService();
        $result = $service->getAllianceAnnouncementRoles(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_announcement_combined_filters()
    {
        $service = new AllianceAnnouncementService();
        $result = $service->getAllianceAnnouncementCombinedFilters(1, [
            'status' => 'active',
            'type' => 'announcement',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_announcement_search()
    {
        $service = new AllianceAnnouncementService();
        $result = $service->getAllianceAnnouncementSearch(1, 'Test');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_announcement_sort()
    {
        $service = new AllianceAnnouncementService();
        $result = $service->getAllianceAnnouncementSort(1, 'points', 'desc');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_points', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_announcement_pagination()
    {
        $service = new AllianceAnnouncementService();
        $result = $service->getAllianceAnnouncementPagination(1, 1, 10);

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
