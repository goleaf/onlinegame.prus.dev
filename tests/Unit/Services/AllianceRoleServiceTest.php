<?php

namespace Tests\Unit\Services;

use App\Services\AllianceRoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AllianceRoleServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_alliance_roles()
    {
        $service = new AllianceRoleService();
        $result = $service->getAllianceRoles(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_role_details()
    {
        $service = new AllianceRoleService();
        $result = $service->getAllianceRoleDetails(1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('role', $result);
        $this->assertArrayHasKey('alliance', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_role_statistics()
    {
        $service = new AllianceRoleService();
        $result = $service->getAllianceRoleStatistics(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_roles', $result);
        $this->assertArrayHasKey('by_type', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_role_combined_filters()
    {
        $service = new AllianceRoleService();
        $result = $service->getAllianceRoleCombinedFilters(1, [
            'status' => 'active',
            'type' => 'role',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_role_search()
    {
        $service = new AllianceRoleService();
        $result = $service->getAllianceRoleSearch(1, 'Test');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_role_sort()
    {
        $service = new AllianceRoleService();
        $result = $service->getAllianceRoleSort(1, 'points', 'desc');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_points', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_role_pagination()
    {
        $service = new AllianceRoleService();
        $result = $service->getAllianceRolePagination(1, 1, 10);

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
