<?php

namespace Tests\Unit\Services;

use App\Services\AllianceAchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AllianceAchievementServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_alliance_achievements()
    {
        $service = new AllianceAchievementService();
        $result = $service->getAllianceAchievements(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('achievements', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_achievement_details()
    {
        $service = new AllianceAchievementService();
        $result = $service->getAllianceAchievementDetails(1, 1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('achievement', $result);
        $this->assertArrayHasKey('alliance', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_achievement_statistics()
    {
        $service = new AllianceAchievementService();
        $result = $service->getAllianceAchievementStatistics(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_achievements', $result);
        $this->assertArrayHasKey('by_type', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_achievement_events()
    {
        $service = new AllianceAchievementService();
        $result = $service->getAllianceAchievementEvents(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('events', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_achievement_announcements()
    {
        $service = new AllianceAchievementService();
        $result = $service->getAllianceAchievementAnnouncements(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('announcements', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_achievement_invitations()
    {
        $service = new AllianceAchievementService();
        $result = $service->getAllianceAchievementInvitations(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('invitations', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_achievement_join_requests()
    {
        $service = new AllianceAchievementService();
        $result = $service->getAllianceAchievementJoinRequests(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('join_requests', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_achievement_permissions()
    {
        $service = new AllianceAchievementService();
        $result = $service->getAllianceAchievementPermissions(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('permissions', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_achievement_roles()
    {
        $service = new AllianceAchievementService();
        $result = $service->getAllianceAchievementRoles(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('roles', $result);
        $this->assertArrayHasKey('total', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_achievement_combined_filters()
    {
        $service = new AllianceAchievementService();
        $result = $service->getAllianceAchievementCombinedFilters(1, [
            'status' => 'active',
            'type' => 'achievement',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_achievement_search()
    {
        $service = new AllianceAchievementService();
        $result = $service->getAllianceAchievementSearch(1, 'Test');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_status', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_achievement_sort()
    {
        $service = new AllianceAchievementService();
        $result = $service->getAllianceAchievementSort(1, 'points', 'desc');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_points', $result);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_achievement_pagination()
    {
        $service = new AllianceAchievementService();
        $result = $service->getAllianceAchievementPagination(1, 1, 10);

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
