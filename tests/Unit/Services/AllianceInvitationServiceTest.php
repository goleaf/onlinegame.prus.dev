<?php

namespace Tests\Unit\Services;

use App\Services\AllianceInvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AllianceInvitationServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_alliance_invitations()
    {
        $service = new AllianceInvitationService();
        $result = $service->getAllianceInvitations(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('invitations', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_invitation_details()
    {
        $service = new AllianceInvitationService();
        $result = $service->getAllianceInvitationDetails(1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('invitation', $result);
        $this->assertArrayHasKey('alliance', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_invitation_statistics()
    {
        $service = new AllianceInvitationService();
        $result = $service->getAllianceInvitationStatistics(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_invitations', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_invitation_join_requests()
    {
        $service = new AllianceInvitationService();
        $result = $service->getAllianceInvitationJoinRequests(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('join_requests', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_invitation_permissions()
    {
        $service = new AllianceInvitationService();
        $result = $service->getAllianceInvitationPermissions(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('permissions', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_invitation_roles()
    {
        $service = new AllianceInvitationService();
        $result = $service->getAllianceInvitationRoles(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_invitation_combined_filters()
    {
        $service = new AllianceInvitationService();
        $result = $service->getAllianceInvitationCombinedFilters(1, [
            'status' => 'active',
            'type' => 'invitation',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_invitation_search()
    {
        $service = new AllianceInvitationService();
        $result = $service->getAllianceInvitationSearch(1, 'Test');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_invitation_sort()
    {
        $service = new AllianceInvitationService();
        $result = $service->getAllianceInvitationSort(1, 'points', 'desc');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_points', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_invitation_pagination()
    {
        $service = new AllianceInvitationService();
        $result = $service->getAllianceInvitationPagination(1, 1, 10);

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
