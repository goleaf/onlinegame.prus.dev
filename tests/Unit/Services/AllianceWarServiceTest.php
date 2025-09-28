<?php

namespace Tests\Unit\Services;

use App\Services\AllianceWarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AllianceWarServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_declare_war()
    {
        $service = new AllianceWarService();
        $result = $service->declareWar(1, 2, 'Test war');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('war_id', $result);
    }

    /**
     * @test
     */
    public function it_can_accept_war()
    {
        $service = new AllianceWarService();
        $result = $service->acceptWar(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('war_id', $result);
    }

    /**
     * @test
     */
    public function it_can_reject_war()
    {
        $service = new AllianceWarService();
        $result = $service->rejectWar(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('war_id', $result);
    }

    /**
     * @test
     */
    public function it_can_cancel_war()
    {
        $service = new AllianceWarService();
        $result = $service->cancelWar(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('war_id', $result);
    }

    /**
     * @test
     */
    public function it_can_end_war()
    {
        $service = new AllianceWarService();
        $result = $service->endWar(1, 'victory');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('war_id', $result);
    }

    /**
     * @test
     */
    public function it_can_get_war_details()
    {
        $service = new AllianceWarService();
        $result = $service->getWarDetails(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('war', $result);
        $this->assertArrayHasKey('attacker', $result);
        $this->assertArrayHasKey('defender', $result);
    }

    /**
     * @test
     */
    public function it_can_get_war_statistics()
    {
        $service = new AllianceWarService();
        $result = $service->getWarStatistics(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_battles', $result);
        $this->assertArrayHasKey('attacker_wins', $result);
        $this->assertArrayHasKey('defender_wins', $result);
    }

    /**
     * @test
     */
    public function it_can_get_war_battles()
    {
        $service = new AllianceWarService();
        $result = $service->getWarBattles(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('battles', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_war_members()
    {
        $service = new AllianceWarService();
        $result = $service->getWarMembers(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('attacker_members', $result);
        $this->assertArrayHasKey('defender_members', $result);
    }

    /**
     * @test
     */
    public function it_can_get_war_events()
    {
        $service = new AllianceWarService();
        $result = $service->getWarEvents(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('events', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_war_announcements()
    {
        $service = new AllianceWarService();
        $result = $service->getWarAnnouncements(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('announcements', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_war_invitations()
    {
        $service = new AllianceWarService();
        $result = $service->getWarInvitations(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('invitations', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_war_join_requests()
    {
        $service = new AllianceWarService();
        $result = $service->getWarJoinRequests(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('join_requests', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_war_permissions()
    {
        $service = new AllianceWarService();
        $result = $service->getWarPermissions(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('permissions', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_war_roles()
    {
        $service = new AllianceWarService();
        $result = $service->getWarRoles(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_war_combined_filters()
    {
        $service = new AllianceWarService();
        $result = $service->getWarCombinedFilters(1, [
            'status' => 'active',
            'type' => 'war',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_war_search()
    {
        $service = new AllianceWarService();
        $result = $service->getWarSearch(1, 'Test');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_war_sort()
    {
        $service = new AllianceWarService();
        $result = $service->getWarSort(1, 'points', 'desc');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_points', $result);
    }

    /**
     * @test
     */
    public function it_can_get_war_pagination()
    {
        $service = new AllianceWarService();
        $result = $service->getWarPagination(1, 1, 10);

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
