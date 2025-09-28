<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Achievement;
use App\Models\Game\Player;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_achievement()
    {
        $player = Player::factory()->create();

        $achievement = Achievement::create([
            'player_id' => $player->id,
            'achievement_type' => 'battle_victory',
            'title' => 'First Victory',
            'description' => 'Win your first battle',
            'icon' => 'victory.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => 'sword',
            'requirements' => ['battles_won' => 1],
            'progress' => 0,
            'max_progress' => 1,
            'is_completed' => false,
            'completed_at' => null,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'combat',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => ['experience' => 50, 'gold' => 25],
            'metadata' => ['source' => 'tutorial', 'version' => '1.0'],
        ]);

        $this->assertDatabaseHas('achievements', [
            'player_id' => $player->id,
            'achievement_type' => 'battle_victory',
            'title' => 'First Victory',
            'rarity' => 'common',
        ]);
    }

    /**
     * @test
     */
    public function it_can_fill_fillable_attributes()
    {
        $player = Player::factory()->create();

        $achievement = new Achievement([
            'player_id' => $player->id,
            'achievement_type' => 'level_up',
            'title' => 'Level Master',
            'description' => 'Reach level 50',
            'icon' => 'level.png',
            'rarity' => 'rare',
            'points' => 500,
            'experience_reward' => 200,
            'gold_reward' => 100,
            'item_reward' => 'armor',
            'requirements' => ['level' => 50],
            'progress' => 25,
            'max_progress' => 50,
            'is_completed' => false,
            'completed_at' => null,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'progression',
            'difficulty' => 'hard',
            'prerequisites' => ['level_10', 'level_25'],
            'rewards' => ['experience' => 200, 'gold' => 100],
            'metadata' => ['source' => 'progression', 'version' => '1.1'],
        ]);

        $this->assertEquals($player->id, $achievement->player_id);
        $this->assertEquals('level_up', $achievement->achievement_type);
        $this->assertEquals('Level Master', $achievement->title);
        $this->assertEquals('rare', $achievement->rarity);
    }

    /**
     * @test
     */
    public function it_casts_booleans()
    {
        $player = Player::factory()->create();

        $achievement = Achievement::create([
            'player_id' => $player->id,
            'achievement_type' => 'test_achievement',
            'title' => 'Test Achievement',
            'description' => 'A test achievement',
            'icon' => 'test.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'progress' => 0,
            'max_progress' => 1,
            'is_completed' => true,
            'completed_at' => now(),
            'is_hidden' => false,
            'is_repeatable' => true,
            'category' => 'test',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $this->assertTrue($achievement->is_completed);
        $this->assertFalse($achievement->is_hidden);
        $this->assertTrue($achievement->is_repeatable);
    }

    /**
     * @test
     */
    public function it_casts_dates()
    {
        $player = Player::factory()->create();

        $achievement = Achievement::create([
            'player_id' => $player->id,
            'achievement_type' => 'test_achievement',
            'title' => 'Test Achievement',
            'description' => 'A test achievement',
            'icon' => 'test.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'progress' => 0,
            'max_progress' => 1,
            'is_completed' => true,
            'completed_at' => now(),
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'test',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $this->assertInstanceOf('Carbon\Carbon', $achievement->completed_at);
    }

    /**
     * @test
     */
    public function it_casts_json_fields()
    {
        $player = Player::factory()->create();

        $achievement = Achievement::create([
            'player_id' => $player->id,
            'achievement_type' => 'test_achievement',
            'title' => 'Test Achievement',
            'description' => 'A test achievement',
            'icon' => 'test.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => ['level' => 10, 'battles' => 5],
            'progress' => 0,
            'max_progress' => 1,
            'is_completed' => false,
            'completed_at' => null,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'test',
            'difficulty' => 'easy',
            'prerequisites' => ['achievement_1', 'achievement_2'],
            'rewards' => ['experience' => 50, 'gold' => 25, 'item' => 'sword'],
            'metadata' => ['source' => 'test', 'version' => '1.0', 'author' => 'system'],
        ]);

        $this->assertIsArray($achievement->requirements);
        $this->assertIsArray($achievement->prerequisites);
        $this->assertIsArray($achievement->rewards);
        $this->assertIsArray($achievement->metadata);
    }

    /**
     * @test
     */
    public function it_can_scope_achievements_by_player()
    {
        $player1 = Player::factory()->create();
        $player2 = Player::factory()->create();

        Achievement::create([
            'player_id' => $player1->id,
            'achievement_type' => 'battle_victory',
            'title' => 'Player 1 Achievement',
            'description' => 'A test achievement',
            'icon' => 'test.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'progress' => 0,
            'max_progress' => 1,
            'is_completed' => false,
            'completed_at' => null,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'test',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        Achievement::create([
            'player_id' => $player2->id,
            'achievement_type' => 'battle_victory',
            'title' => 'Player 2 Achievement',
            'description' => 'A test achievement',
            'icon' => 'test.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'progress' => 0,
            'max_progress' => 1,
            'is_completed' => false,
            'completed_at' => null,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'test',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $player1Achievements = Achievement::byPlayer($player1->id)->get();
        $this->assertCount(1, $player1Achievements);
        $this->assertEquals($player1->id, $player1Achievements->first()->player_id);
    }

    /**
     * @test
     */
    public function it_can_scope_achievements_by_type()
    {
        $player = Player::factory()->create();

        Achievement::create([
            'player_id' => $player->id,
            'achievement_type' => 'battle_victory',
            'title' => 'Battle Achievement',
            'description' => 'A battle achievement',
            'icon' => 'battle.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'progress' => 0,
            'max_progress' => 1,
            'is_completed' => false,
            'completed_at' => null,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'combat',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        Achievement::create([
            'player_id' => $player->id,
            'achievement_type' => 'level_up',
            'title' => 'Level Achievement',
            'description' => 'A level achievement',
            'icon' => 'level.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'progress' => 0,
            'max_progress' => 1,
            'is_completed' => false,
            'completed_at' => null,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'progression',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $battleAchievements = Achievement::byType('battle_victory')->get();
        $this->assertCount(1, $battleAchievements);
        $this->assertEquals('battle_victory', $battleAchievements->first()->achievement_type);
    }

    /**
     * @test
     */
    public function it_can_scope_achievements_by_rarity()
    {
        $player = Player::factory()->create();

        Achievement::create([
            'player_id' => $player->id,
            'achievement_type' => 'common_achievement',
            'title' => 'Common Achievement',
            'description' => 'A common achievement',
            'icon' => 'common.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'progress' => 0,
            'max_progress' => 1,
            'is_completed' => false,
            'completed_at' => null,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'test',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        Achievement::create([
            'player_id' => $player->id,
            'achievement_type' => 'rare_achievement',
            'title' => 'Rare Achievement',
            'description' => 'A rare achievement',
            'icon' => 'rare.png',
            'rarity' => 'rare',
            'points' => 500,
            'experience_reward' => 200,
            'gold_reward' => 100,
            'item_reward' => 'sword',
            'requirements' => [],
            'progress' => 0,
            'max_progress' => 1,
            'is_completed' => false,
            'completed_at' => null,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'test',
            'difficulty' => 'hard',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $rareAchievements = Achievement::byRarity('rare')->get();
        $this->assertCount(1, $rareAchievements);
        $this->assertEquals('rare', $rareAchievements->first()->rarity);
    }

    /**
     * @test
     */
    public function it_can_scope_completed_achievements()
    {
        $player = Player::factory()->create();

        Achievement::create([
            'player_id' => $player->id,
            'achievement_type' => 'completed_achievement',
            'title' => 'Completed Achievement',
            'description' => 'A completed achievement',
            'icon' => 'completed.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'progress' => 1,
            'max_progress' => 1,
            'is_completed' => true,
            'completed_at' => now(),
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'test',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        Achievement::create([
            'player_id' => $player->id,
            'achievement_type' => 'incomplete_achievement',
            'title' => 'Incomplete Achievement',
            'description' => 'An incomplete achievement',
            'icon' => 'incomplete.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'progress' => 0,
            'max_progress' => 1,
            'is_completed' => false,
            'completed_at' => null,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'test',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $completedAchievements = Achievement::completed()->get();
        $this->assertCount(1, $completedAchievements);
        $this->assertTrue($completedAchievements->first()->is_completed);
    }

    /**
     * @test
     */
    public function it_can_scope_achievements_by_category()
    {
        $player = Player::factory()->create();

        Achievement::create([
            'player_id' => $player->id,
            'achievement_type' => 'battle_achievement',
            'title' => 'Battle Achievement',
            'description' => 'A battle achievement',
            'icon' => 'battle.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'progress' => 0,
            'max_progress' => 1,
            'is_completed' => false,
            'completed_at' => null,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'combat',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        Achievement::create([
            'player_id' => $player->id,
            'achievement_type' => 'level_achievement',
            'title' => 'Level Achievement',
            'description' => 'A level achievement',
            'icon' => 'level.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'progress' => 0,
            'max_progress' => 1,
            'is_completed' => false,
            'completed_at' => null,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'progression',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $combatAchievements = Achievement::byCategory('combat')->get();
        $this->assertCount(1, $combatAchievements);
        $this->assertEquals('combat', $combatAchievements->first()->category);
    }

    /**
     * @test
     */
    public function it_can_get_player_relationship()
    {
        $player = Player::factory()->create();

        $achievement = Achievement::create([
            'player_id' => $player->id,
            'achievement_type' => 'test_achievement',
            'title' => 'Test Achievement',
            'description' => 'A test achievement',
            'icon' => 'test.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'progress' => 0,
            'max_progress' => 1,
            'is_completed' => false,
            'completed_at' => null,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'test',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Relations\BelongsTo', $achievement->player());
        $this->assertEquals($player->id, $achievement->player->id);
    }

    /**
     * @test
     */
    public function it_can_get_achievement_summary()
    {
        $player = Player::factory()->create();

        $achievement = Achievement::create([
            'player_id' => $player->id,
            'achievement_type' => 'battle_victory',
            'title' => 'First Victory',
            'description' => 'Win your first battle',
            'icon' => 'victory.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'progress' => 0,
            'max_progress' => 1,
            'is_completed' => false,
            'completed_at' => null,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'combat',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $summary = $achievement->getSummary();
        $this->assertIsString($summary);
        $this->assertStringContainsString('First Victory', $summary);
    }

    /**
     * @test
     */
    public function it_can_get_achievement_details()
    {
        $player = Player::factory()->create();

        $achievement = Achievement::create([
            'player_id' => $player->id,
            'achievement_type' => 'battle_victory',
            'title' => 'First Victory',
            'description' => 'Win your first battle',
            'icon' => 'victory.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'progress' => 0,
            'max_progress' => 1,
            'is_completed' => false,
            'completed_at' => null,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'combat',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $details = $achievement->getDetails();
        $this->assertIsArray($details);
        $this->assertArrayHasKey('title', $details);
        $this->assertArrayHasKey('description', $details);
    }

    /**
     * @test
     */
    public function it_can_get_achievement_statistics()
    {
        $player = Player::factory()->create();

        $achievement = Achievement::create([
            'player_id' => $player->id,
            'achievement_type' => 'battle_victory',
            'title' => 'First Victory',
            'description' => 'Win your first battle',
            'icon' => 'victory.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'progress' => 0,
            'max_progress' => 1,
            'is_completed' => false,
            'completed_at' => null,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'combat',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $stats = $achievement->getStatistics();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('points', $stats);
        $this->assertArrayHasKey('progress', $stats);
    }

    /**
     * @test
     */
    public function it_can_get_achievement_timeline()
    {
        $player = Player::factory()->create();

        $achievement = Achievement::create([
            'player_id' => $player->id,
            'achievement_type' => 'battle_victory',
            'title' => 'First Victory',
            'description' => 'Win your first battle',
            'icon' => 'victory.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'progress' => 0,
            'max_progress' => 1,
            'is_completed' => false,
            'completed_at' => null,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'combat',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $timeline = $achievement->getTimeline();
        $this->assertIsArray($timeline);
        $this->assertArrayHasKey('progress_percentage', $timeline);
        $this->assertArrayHasKey('remaining', $timeline);
    }
}
