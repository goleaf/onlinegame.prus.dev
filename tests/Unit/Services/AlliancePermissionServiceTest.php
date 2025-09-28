<?php

namespace Tests\Unit\Services;

use App\Services\AlliancePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AlliancePermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_alliance_permissions()
    {
        $service = new AlliancePermissionService();
        $result = $service->getAlliancePermissions(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('permissions', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_permission_details()
    {
        $service = new AlliancePermissionService();
        $result = $service->getAlliancePermissionDetails(1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('permission', $result);
        $this->assertArrayHasKey('alliance', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_permission_statistics()
    {
        $service = new AlliancePermissionService();
        $result = $service->getAlliancePermissionStatistics(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_permissions', $result);
        $this->assertArrayHasKey('by_type', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_permission_roles()
    {
        $service = new AlliancePermissionService();
        $result = $service->getAlliancePermissionRoles(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_permission_combined_filters()
    {
        $service = new AlliancePermissionService();
        $result = $service->getAlliancePermissionCombinedFilters(1, [
            'status' => 'active',
            'type' => 'permission',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_permission_search()
    {
        $service = new AlliancePermissionService();
        $result = $service->getAlliancePermissionSearch(1, 'Test');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_permission_sort()
    {
        $service = new AlliancePermissionService();
        $result = $service->getAlliancePermissionSort(1, 'points', 'desc');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_points', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_permission_pagination()
    {
        $service = new AlliancePermissionService();
        $result = $service->getAlliancePermissionPagination(1, 1, 10);

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
