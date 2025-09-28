<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Alliance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllianceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_alliance()
    {
        $alliance = Alliance::create([
            'name' => 'Test Alliance',
            'description' => 'A test alliance for testing purposes',
            'tag' => 'TEST',
            'color' => '#ff0000',
            'banner' => 'test_banner.png',
            'leader_id' => 1,
            'founded_at' => now(),
            'is_active' => true,
            'is_public' => true,
            'member_limit' => 50,
            'current_members' => 0,
            'level' => 1,
            'experience' => 0,
            'reputation' => 0,
            'territory_count' => 0,
            'war_count' => 0,
            'victory_count' => 0,
            'defeat_count' => 0,
            'settings' => ['allow_public_join' => true, 'require_approval' => false],
            'metadata' => ['source' => 'test', 'version' => '1.0'],
        ]);

        $this->assertDatabaseHas('alliances', [
            'name' => 'Test Alliance',
            'tag' => 'TEST',
            'leader_id' => 1,
        ]);
    }

    /**
     * @test
     */
    public function it_can_fill_fillable_attributes()
    {
        $alliance = new Alliance([
            'name' => 'Elite Alliance',
            'description' => 'An elite alliance for experienced players',
            'tag' => 'ELITE',
            'color' => '#00ff00',
            'banner' => 'elite_banner.png',
            'leader_id' => 2,
            'founded_at' => now(),
            'is_active' => true,
            'is_public' => false,
            'member_limit' => 100,
            'current_members' => 25,
            'level' => 5,
            'experience' => 1000,
            'reputation' => 500,
            'territory_count' => 10,
            'war_count' => 5,
            'victory_count' => 4,
            'defeat_count' => 1,
            'settings' => ['allow_public_join' => false, 'require_approval' => true],
            'metadata' => ['source' => 'elite', 'version' => '1.1'],
        ]);

        $this->assertEquals('Elite Alliance', $alliance->name);
        $this->assertEquals('ELITE', $alliance->tag);
        $this->assertEquals(2, $alliance->leader_id);
    }

    /**
     * @test
     */
    public function it_casts_booleans()
    {
        $alliance = Alliance::create([
            'name' => 'Test Alliance',
            'description' => 'A test alliance',
            'tag' => 'TEST',
            'color' => '#ff0000',
            'banner' => 'test_banner.png',
            'leader_id' => 1,
            'founded_at' => now(),
            'is_active' => true,
            'is_public' => false,
            'member_limit' => 50,
            'current_members' => 0,
            'level' => 1,
            'experience' => 0,
            'reputation' => 0,
            'territory_count' => 0,
            'war_count' => 0,
            'victory_count' => 0,
            'defeat_count' => 0,
            'settings' => [],
            'metadata' => [],
        ]);

        $this->assertTrue($alliance->is_active);
        $this->assertFalse($alliance->is_public);
    }

    /**
     * @test
     */
    public function it_casts_dates()
    {
        $alliance = Alliance::create([
            'name' => 'Test Alliance',
            'description' => 'A test alliance',
            'tag' => 'TEST',
            'color' => '#ff0000',
            'banner' => 'test_banner.png',
            'leader_id' => 1,
            'founded_at' => now(),
            'is_active' => false,
            'is_public' => false,
            'member_limit' => 50,
            'current_members' => 0,
            'level' => 1,
            'experience' => 0,
            'reputation' => 0,
            'territory_count' => 0,
            'war_count' => 0,
            'victory_count' => 0,
            'defeat_count' => 0,
            'settings' => [],
            'metadata' => [],
        ]);

        $this->assertInstanceOf('Carbon\Carbon', $alliance->founded_at);
    }

    /**
     * @test
     */
    public function it_casts_json_fields()
    {
        $alliance = Alliance::create([
            'name' => 'Test Alliance',
            'description' => 'A test alliance',
            'tag' => 'TEST',
            'color' => '#ff0000',
            'banner' => 'test_banner.png',
            'leader_id' => 1,
            'founded_at' => now(),
            'is_active' => false,
            'is_public' => false,
            'member_limit' => 50,
            'current_members' => 0,
            'level' => 1,
            'experience' => 0,
            'reputation' => 0,
            'territory_count' => 0,
            'war_count' => 0,
            'victory_count' => 0,
            'defeat_count' => 0,
            'settings' => ['allow_public_join' => true, 'require_approval' => false],
            'metadata' => ['source' => 'test', 'version' => '1.0', 'author' => 'system'],
        ]);

        $this->assertIsArray($alliance->settings);
        $this->assertIsArray($alliance->metadata);
    }

    /**
     * @test
     */
    public function it_can_scope_active_alliances()
    {
        Alliance::create([
            'name' => 'Active Alliance',
            'description' => 'An active alliance',
            'tag' => 'ACTIVE',
            'color' => '#ff0000',
            'banner' => 'active_banner.png',
            'leader_id' => 1,
            'founded_at' => now(),
            'is_active' => true,
            'is_public' => false,
            'member_limit' => 50,
            'current_members' => 0,
            'level' => 1,
            'experience' => 0,
            'reputation' => 0,
            'territory_count' => 0,
            'war_count' => 0,
            'victory_count' => 0,
            'defeat_count' => 0,
            'settings' => [],
            'metadata' => [],
        ]);

        Alliance::create([
            'name' => 'Inactive Alliance',
            'description' => 'An inactive alliance',
            'tag' => 'INACTIVE',
            'color' => '#00ff00',
            'banner' => 'inactive_banner.png',
            'leader_id' => 2,
            'founded_at' => now(),
            'is_active' => false,
            'is_public' => false,
            'member_limit' => 50,
            'current_members' => 0,
            'level' => 1,
            'experience' => 0,
            'reputation' => 0,
            'territory_count' => 0,
            'war_count' => 0,
            'victory_count' => 0,
            'defeat_count' => 0,
            'settings' => [],
            'metadata' => [],
        ]);

        $activeAlliances = Alliance::active()->get();
        $this->assertCount(1, $activeAlliances);
        $this->assertTrue($activeAlliances->first()->is_active);
    }

    /**
     * @test
     */
    public function it_can_scope_public_alliances()
    {
        Alliance::create([
            'name' => 'Public Alliance',
            'description' => 'A public alliance',
            'tag' => 'PUBLIC',
            'color' => '#ff0000',
            'banner' => 'public_banner.png',
            'leader_id' => 1,
            'founded_at' => now(),
            'is_active' => false,
            'is_public' => true,
            'member_limit' => 50,
            'current_members' => 0,
            'level' => 1,
            'experience' => 0,
            'reputation' => 0,
            'territory_count' => 0,
            'war_count' => 0,
            'victory_count' => 0,
            'defeat_count' => 0,
            'settings' => [],
            'metadata' => [],
        ]);

        Alliance::create([
            'name' => 'Private Alliance',
            'description' => 'A private alliance',
            'tag' => 'PRIVATE',
            'color' => '#00ff00',
            'banner' => 'private_banner.png',
            'leader_id' => 2,
            'founded_at' => now(),
            'is_active' => false,
            'is_public' => false,
            'member_limit' => 50,
            'current_members' => 0,
            'level' => 1,
            'experience' => 0,
            'reputation' => 0,
            'territory_count' => 0,
            'war_count' => 0,
            'victory_count' => 0,
            'defeat_count' => 0,
            'settings' => [],
            'metadata' => [],
        ]);

        $publicAlliances = Alliance::public()->get();
        $this->assertCount(1, $publicAlliances);
        $this->assertTrue($publicAlliances->first()->is_public);
    }

    /**
     * @test
     */
    public function it_can_scope_alliances_by_level()
    {
        Alliance::create([
            'name' => 'Low Level Alliance',
            'description' => 'A low level alliance',
            'tag' => 'LOW',
            'color' => '#ff0000',
            'banner' => 'low_banner.png',
            'leader_id' => 1,
            'founded_at' => now(),
            'is_active' => false,
            'is_public' => false,
            'member_limit' => 50,
            'current_members' => 0,
            'level' => 1,
            'experience' => 0,
            'reputation' => 0,
            'territory_count' => 0,
            'war_count' => 0,
            'victory_count' => 0,
            'defeat_count' => 0,
            'settings' => [],
            'metadata' => [],
        ]);

        Alliance::create([
            'name' => 'High Level Alliance',
            'description' => 'A high level alliance',
            'tag' => 'HIGH',
            'color' => '#00ff00',
            'banner' => 'high_banner.png',
            'leader_id' => 2,
            'founded_at' => now(),
            'is_active' => false,
            'is_public' => false,
            'member_limit' => 50,
            'current_members' => 0,
            'level' => 10,
            'experience' => 1000,
            'reputation' => 500,
            'territory_count' => 0,
            'war_count' => 0,
            'victory_count' => 0,
            'defeat_count' => 0,
            'settings' => [],
            'metadata' => [],
        ]);

        $highLevelAlliances = Alliance::byLevel(10)->get();
        $this->assertCount(1, $highLevelAlliances);
        $this->assertEquals(10, $highLevelAlliances->first()->level);
    }

    /**
     * @test
     */
    public function it_can_scope_alliances_by_leader()
    {
        Alliance::create([
            'name' => 'Leader 1 Alliance',
            'description' => 'An alliance led by leader 1',
            'tag' => 'LEADER1',
            'color' => '#ff0000',
            'banner' => 'leader1_banner.png',
            'leader_id' => 1,
            'founded_at' => now(),
            'is_active' => false,
            'is_public' => false,
            'member_limit' => 50,
            'current_members' => 0,
            'level' => 1,
            'experience' => 0,
            'reputation' => 0,
            'territory_count' => 0,
            'war_count' => 0,
            'victory_count' => 0,
            'defeat_count' => 0,
            'settings' => [],
            'metadata' => [],
        ]);

        Alliance::create([
            'name' => 'Leader 2 Alliance',
            'description' => 'An alliance led by leader 2',
            'tag' => 'LEADER2',
            'color' => '#00ff00',
            'banner' => 'leader2_banner.png',
            'leader_id' => 2,
            'founded_at' => now(),
            'is_active' => false,
            'is_public' => false,
            'member_limit' => 50,
            'current_members' => 0,
            'level' => 1,
            'experience' => 0,
            'reputation' => 0,
            'territory_count' => 0,
            'war_count' => 0,
            'victory_count' => 0,
            'defeat_count' => 0,
            'settings' => [],
            'metadata' => [],
        ]);

        $leader1Alliances = Alliance::byLeader(1)->get();
        $this->assertCount(1, $leader1Alliances);
        $this->assertEquals(1, $leader1Alliances->first()->leader_id);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_summary()
    {
        $alliance = Alliance::create([
            'name' => 'Test Alliance',
            'description' => 'A test alliance for testing purposes',
            'tag' => 'TEST',
            'color' => '#ff0000',
            'banner' => 'test_banner.png',
            'leader_id' => 1,
            'founded_at' => now(),
            'is_active' => false,
            'is_public' => false,
            'member_limit' => 50,
            'current_members' => 0,
            'level' => 1,
            'experience' => 0,
            'reputation' => 0,
            'territory_count' => 0,
            'war_count' => 0,
            'victory_count' => 0,
            'defeat_count' => 0,
            'settings' => [],
            'metadata' => [],
        ]);

        $summary = $alliance->getSummary();
        $this->assertIsString($summary);
        $this->assertStringContainsString('Test Alliance', $summary);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_details()
    {
        $alliance = Alliance::create([
            'name' => 'Test Alliance',
            'description' => 'A test alliance for testing purposes',
            'tag' => 'TEST',
            'color' => '#ff0000',
            'banner' => 'test_banner.png',
            'leader_id' => 1,
            'founded_at' => now(),
            'is_active' => false,
            'is_public' => false,
            'member_limit' => 50,
            'current_members' => 0,
            'level' => 1,
            'experience' => 0,
            'reputation' => 0,
            'territory_count' => 0,
            'war_count' => 0,
            'victory_count' => 0,
            'defeat_count' => 0,
            'settings' => [],
            'metadata' => [],
        ]);

        $details = $alliance->getDetails();
        $this->assertIsArray($details);
        $this->assertArrayHasKey('name', $details);
        $this->assertArrayHasKey('description', $details);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_statistics()
    {
        $alliance = Alliance::create([
            'name' => 'Test Alliance',
            'description' => 'A test alliance for testing purposes',
            'tag' => 'TEST',
            'color' => '#ff0000',
            'banner' => 'test_banner.png',
            'leader_id' => 1,
            'founded_at' => now(),
            'is_active' => false,
            'is_public' => false,
            'member_limit' => 50,
            'current_members' => 0,
            'level' => 1,
            'experience' => 0,
            'reputation' => 0,
            'territory_count' => 0,
            'war_count' => 0,
            'victory_count' => 0,
            'defeat_count' => 0,
            'settings' => [],
            'metadata' => [],
        ]);

        $stats = $alliance->getStatistics();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('level', $stats);
        $this->assertArrayHasKey('experience', $stats);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_timeline()
    {
        $alliance = Alliance::create([
            'name' => 'Test Alliance',
            'description' => 'A test alliance for testing purposes',
            'tag' => 'TEST',
            'color' => '#ff0000',
            'banner' => 'test_banner.png',
            'leader_id' => 1,
            'founded_at' => now(),
            'is_active' => false,
            'is_public' => false,
            'member_limit' => 50,
            'current_members' => 0,
            'level' => 1,
            'experience' => 0,
            'reputation' => 0,
            'territory_count' => 0,
            'war_count' => 0,
            'victory_count' => 0,
            'defeat_count' => 0,
            'settings' => [],
            'metadata' => [],
        ]);

        $timeline = $alliance->getTimeline();
        $this->assertIsArray($timeline);
        $this->assertArrayHasKey('founded_at', $timeline);
        $this->assertArrayHasKey('created_at', $timeline);
    }
}
