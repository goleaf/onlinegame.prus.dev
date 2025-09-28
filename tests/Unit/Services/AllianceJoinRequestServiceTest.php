<?php

namespace Tests\Unit\Services;

use App\Services\AllianceJoinRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AllianceJoinRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_alliance_join_requests()
    {
        $service = new AllianceJoinRequestService();
        $result = $service->getAllianceJoinRequests(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('join_requests', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_join_request_details()
    {
        $service = new AllianceJoinRequestService();
        $result = $service->getAllianceJoinRequestDetails(1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('join_request', $result);
        $this->assertArrayHasKey('alliance', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_join_request_statistics()
    {
        $service = new AllianceJoinRequestService();
        $result = $service->getAllianceJoinRequestStatistics(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_join_requests', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_join_request_permissions()
    {
        $service = new AllianceJoinRequestService();
        $result = $service->getAllianceJoinRequestPermissions(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('permissions', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_join_request_roles()
    {
        $service = new AllianceJoinRequestService();
        $result = $service->getAllianceJoinRequestRoles(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_join_request_combined_filters()
    {
        $service = new AllianceJoinRequestService();
        $result = $service->getAllianceJoinRequestCombinedFilters(1, [
            'status' => 'active',
            'type' => 'join_request',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_join_request_search()
    {
        $service = new AllianceJoinRequestService();
        $result = $service->getAllianceJoinRequestSearch(1, 'Test');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_join_request_sort()
    {
        $service = new AllianceJoinRequestService();
        $result = $service->getAllianceJoinRequestSort(1, 'points', 'desc');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_points', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_join_request_pagination()
    {
        $service = new AllianceJoinRequestService();
        $result = $service->getAllianceJoinRequestPagination(1, 1, 10);

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
