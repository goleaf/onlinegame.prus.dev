<?php

namespace Tests\Unit\Services;

use App\Models\Game\Achievement;
use App\Models\Game\Player;
use App\Models\Game\PlayerAchievement;
use App\Models\Game\Village;
use App\Services\AchievementSystemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AchievementSystemServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_check_and_award_achievements()
    {
        $player = Player::factory()->create();
        $achievement = Achievement::factory()->create([
            'is_active' => true,
            'requirements' => [['type' => 'level', 'value' => 1]],
        ]);

        $player->shouldReceive('villages')->andReturn(collect([]));

        $service = new AchievementSystemService();
        $result = $service->checkAndAwardAchievements($player, []);

        $this->assertIsArray($result);
    }

    /**
     * @test
     */
    public function it_can_award_achievement()
    {
        $player = Player::factory()->create();
        $achievement = Achievement::factory()->create([
            'experience_reward' => 100,
            'resource_reward_wood' => 500,
        ]);

        $village = Village::factory()->create(['player_id' => $player->id]);
        $player->shouldReceive('villages')->andReturn(collect([$village]));
        $village->shouldReceive('resources')->andReturn(collect([]));

        $service = new AchievementSystemService();
        $result = $service->awardAchievement($player, $achievement);

        $this->assertInstanceOf(PlayerAchievement::class, $result);
        $this->assertEquals($player->id, $result->player_id);
        $this->assertEquals($achievement->id, $result->achievement_id);
    }

    /**
     * @test
     */
    public function it_can_get_available_achievements()
    {
        $player = Player::factory()->create();
        $achievement = Achievement::factory()->create([
            'is_active' => true,
            'required_level' => 1,
        ]);

        $player->shouldReceive('villages')->andReturn(collect([]));

        $service = new AchievementSystemService();
        $result = $service->getAvailableAchievements($player);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_player_achievements()
    {
        $player = Player::factory()->create();
        $achievement = Achievement::factory()->create();
        PlayerAchievement::factory()->create([
            'player_id' => $player->id,
            'achievement_id' => $achievement->id,
        ]);

        $service = new AchievementSystemService();
        $result = $service->getPlayerAchievements($player);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_player_achievement_progress()
    {
        $player = Player::factory()->create();
        $achievement = Achievement::factory()->create([
            'is_active' => true,
            'required_level' => 1,
        ]);

        $player->shouldReceive('villages')->andReturn(collect([]));

        $service = new AchievementSystemService();
        $result = $service->getPlayerAchievementProgress($player);

        $this->assertIsArray($result);
    }

    /**
     * @test
     */
    public function it_can_get_achievement_statistics()
    {
        $service = new AchievementSystemService();
        $result = $service->getAchievementStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('player_achievements', $result);
        $this->assertArrayHasKey('achievements', $result);
        $this->assertArrayHasKey('categories', $result);
    }

    /**
     * @test
     */
    public function it_can_get_top_achievers()
    {
        $service = new AchievementSystemService();
        $result = $service->getTopAchievers(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_rare_achievements()
    {
        $service = new AchievementSystemService();
        $result = $service->getRareAchievements(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_check_achievement_requirements()
    {
        $player = Player::factory()->create(['level' => 5]);
        $achievement = Achievement::factory()->create([
            'requirements' => [['type' => 'level', 'value' => 3]],
        ]);

        $service = new AchievementSystemService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('checkAchievementRequirements');
        $method->setAccessible(true);

        $result = $method->invoke($service, $player, $achievement, []);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_check_individual_requirement()
    {
        $player = Player::factory()->create(['level' => 5]);
        $requirement = ['type' => 'level', 'value' => 3];

        $service = new AchievementSystemService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('checkRequirement');
        $method->setAccessible(true);

        $result = $method->invoke($service, $player, $requirement, []);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_check_village_count_requirement()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $player->shouldReceive('villages')->andReturn(collect([$village]));

        $requirement = ['type' => 'village_count', 'value' => 1];

        $service = new AchievementSystemService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('checkRequirement');
        $method->setAccessible(true);

        $result = $method->invoke($service, $player, $requirement, []);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_check_total_resources_requirement()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $village->shouldReceive('resources')->andReturn(collect([
            (object) ['amount' => 1000],
            (object) ['amount' => 500],
        ]));
        $player->shouldReceive('villages')->andReturn(collect([$village]));

        $requirement = ['type' => 'total_resources', 'value' => 1000];

        $service = new AchievementSystemService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('checkRequirement');
        $method->setAccessible(true);

        $result = $method->invoke($service, $player, $requirement, []);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_check_buildings_built_requirement()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $village->shouldReceive('buildings')->andReturn(collect([
            (object) ['level' => 5],
            (object) ['level' => 3],
        ]));
        $player->shouldReceive('villages')->andReturn(collect([$village]));

        $requirement = ['type' => 'buildings_built', 'value' => 5];

        $service = new AchievementSystemService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('checkRequirement');
        $method->setAccessible(true);

        $result = $method->invoke($service, $player, $requirement, []);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_check_troops_trained_requirement()
    {
        $player = Player::factory()->create();
        $village = Village::factory()->create(['player_id' => $player->id]);
        $village->shouldReceive('troops')->andReturn(collect([
            (object) ['amount' => 100],
            (object) ['amount' => 50],
        ]));
        $player->shouldReceive('villages')->andReturn(collect([$village]));

        $requirement = ['type' => 'troops_trained', 'value' => 100];

        $service = new AchievementSystemService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('checkRequirement');
        $method->setAccessible(true);

        $result = $method->invoke($service, $player, $requirement, []);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_check_attacks_launched_requirement()
    {
        $player = Player::factory()->create();
        $requirement = ['type' => 'attacks_launched', 'value' => 5];
        $triggerData = ['attacks_launched' => 10];

        $service = new AchievementSystemService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('checkRequirement');
        $method->setAccessible(true);

        $result = $method->invoke($service, $player, $requirement, $triggerData);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_check_battles_won_requirement()
    {
        $player = Player::factory()->create();
        $requirement = ['type' => 'battles_won', 'value' => 3];
        $triggerData = ['battles_won' => 5];

        $service = new AchievementSystemService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('checkRequirement');
        $method->setAccessible(true);

        $result = $method->invoke($service, $player, $requirement, $triggerData);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_check_alliance_joined_requirement()
    {
        $player = Player::factory()->create(['alliance_id' => 1]);
        $requirement = ['type' => 'alliance_joined', 'value' => 1];

        $service = new AchievementSystemService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('checkRequirement');
        $method->setAccessible(true);

        $result = $method->invoke($service, $player, $requirement, []);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_check_wonder_contributed_requirement()
    {
        $player = Player::factory()->create();
        $requirement = ['type' => 'wonder_contributed', 'value' => 5];
        $triggerData = ['wonder_contributions' => 10];

        $service = new AchievementSystemService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('checkRequirement');
        $method->setAccessible(true);

        $result = $method->invoke($service, $player, $requirement, $triggerData);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_check_quests_completed_requirement()
    {
        $player = Player::factory()->create();
        $player->shouldReceive('playerQuests')->andReturn(collect([
            (object) ['status' => 'completed'],
            (object) ['status' => 'completed'],
        ]));

        $requirement = ['type' => 'quests_completed', 'value' => 2];

        $service = new AchievementSystemService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('checkRequirement');
        $method->setAccessible(true);

        $result = $method->invoke($service, $player, $requirement, []);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_check_market_trades_requirement()
    {
        $player = Player::factory()->create();
        $requirement = ['type' => 'market_trades', 'value' => 5];
        $triggerData = ['market_trades' => 10];

        $service = new AchievementSystemService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('checkRequirement');
        $method->setAccessible(true);

        $result = $method->invoke($service, $player, $requirement, $triggerData);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_calculate_achievement_progress()
    {
        $player = Player::factory()->create(['level' => 3]);
        $achievement = Achievement::factory()->create([
            'requirements' => [['type' => 'level', 'value' => 5]],
        ]);

        $service = new AchievementSystemService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('calculateAchievementProgress');
        $method->setAccessible(true);

        $result = $method->invoke($service, $player, $achievement);

        $this->assertIsFloat($result);
        $this->assertEquals(60.0, $result);  // 3/5 * 100 = 60%
    }

    /**
     * @test
     */
    public function it_can_get_requirement_progress()
    {
        $player = Player::factory()->create(['level' => 3]);
        $requirement = ['type' => 'level', 'value' => 5];

        $service = new AchievementSystemService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getRequirementProgress');
        $method->setAccessible(true);

        $result = $method->invoke($service, $player, $requirement);

        $this->assertIsFloat($result);
        $this->assertEquals(60.0, $result);  // 3/5 * 100 = 60%
    }

    /**
     * @test
     */
    public function it_can_award_achievement_rewards()
    {
        $player = Player::factory()->create();
        $achievement = Achievement::factory()->create([
            'experience_reward' => 100,
            'resource_reward_wood' => 500,
        ]);

        $village = Village::factory()->create(['player_id' => $player->id]);
        $player->shouldReceive('villages')->andReturn(collect([$village]));
        $village->shouldReceive('resources')->andReturn(collect([]));

        $service = new AchievementSystemService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('awardAchievementRewards');
        $method->setAccessible(true);

        $method->invoke($service, $player, $achievement);

        $this->assertTrue(true);  // Method executes without errors
    }

    /**
     * @test
     */
    public function it_can_add_resource_to_village()
    {
        $village = Village::factory()->create();
        $resource = Mockery::mock();
        $resource->shouldReceive('increment')->once();
        $village->shouldReceive('resources')->andReturn(collect([$resource]));

        $service = new AchievementSystemService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('addResourceToVillage');
        $method->setAccessible(true);

        $method->invoke($service, $village, 'wood', 500);

        $this->assertTrue(true);  // Method executes without errors
    }

    /**
     * @test
     */
    public function it_can_clear_player_achievement_cache()
    {
        $player = Player::factory()->create();

        $service = new AchievementSystemService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('clearPlayerAchievementCache');
        $method->setAccessible(true);

        $method->invoke($service, $player);

        $this->assertTrue(true);  // Method executes without errors
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
