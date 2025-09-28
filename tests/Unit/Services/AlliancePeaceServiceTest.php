<?php

namespace Tests\Unit\Services;

use App\Services\AlliancePeaceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AlliancePeaceServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_declare_peace()
    {
        $service = new AlliancePeaceService();
        $result = $service->declarePeace(1, 2, 'Test peace');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('peace_id', $result);
    }

    /**
     * @test
     */
    public function it_can_accept_peace()
    {
        $service = new AlliancePeaceService();
        $result = $service->acceptPeace(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('peace_id', $result);
    }

    /**
     * @test
     */
    public function it_can_reject_peace()
    {
        $service = new AlliancePeaceService();
        $result = $service->rejectPeace(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('peace_id', $result);
    }

    /**
     * @test
     */
    public function it_can_cancel_peace()
    {
        $service = new AlliancePeaceService();
        $result = $service->cancelPeace(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('peace_id', $result);
    }

    /**
     * @test
     */
    public function it_can_end_peace()
    {
        $service = new AlliancePeaceService();
        $result = $service->endPeace(1, 'success');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('peace_id', $result);
    }

    /**
     * @test
     */
    public function it_can_get_peace_details()
    {
        $service = new AlliancePeaceService();
        $result = $service->getPeaceDetails(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('peace', $result);
        $this->assertArrayHasKey('alliance1', $result);
        $this->assertArrayHasKey('alliance2', $result);
    }

    /**
     * @test
     */
    public function it_can_get_peace_statistics()
    {
        $service = new AlliancePeaceService();
        $result = $service->getPeaceStatistics(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_peace', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_peace_events()
    {
        $service = new AlliancePeaceService();
        $result = $service->getPeaceEvents(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('events', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_peace_announcements()
    {
        $service = new AlliancePeaceService();
        $result = $service->getPeaceAnnouncements(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('announcements', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_peace_invitations()
    {
        $service = new AlliancePeaceService();
        $result = $service->getPeaceInvitations(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('invitations', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_peace_join_requests()
    {
        $service = new AlliancePeaceService();
        $result = $service->getPeaceJoinRequests(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('join_requests', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_peace_permissions()
    {
        $service = new AlliancePeaceService();
        $result = $service->getPeacePermissions(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('permissions', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_peace_roles()
    {
        $service = new AlliancePeaceService();
        $result = $service->getPeaceRoles(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_peace_combined_filters()
    {
        $service = new AlliancePeaceService();
        $result = $service->getPeaceCombinedFilters(1, [
            'status' => 'active',
            'type' => 'peace',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_peace_search()
    {
        $service = new AlliancePeaceService();
        $result = $service->getPeaceSearch(1, 'Test');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_peace_sort()
    {
        $service = new AlliancePeaceService();
        $result = $service->getPeaceSort(1, 'points', 'desc');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_points', $result);
    }

    /**
     * @test
     */
    public function it_can_get_peace_pagination()
    {
        $service = new AlliancePeaceService();
        $result = $service->getPeacePagination(1, 1, 10);

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
