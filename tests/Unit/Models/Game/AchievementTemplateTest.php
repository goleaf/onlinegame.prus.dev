<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\AchievementTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementTemplateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_achievement_template()
    {
        $template = AchievementTemplate::create([
            'name' => 'Battle Master',
            'description' => 'Master of combat',
            'icon' => 'battle_master.png',
            'rarity' => 'epic',
            'points' => 1000,
            'experience_reward' => 500,
            'gold_reward' => 250,
            'item_reward' => 'legendary_sword',
            'requirements' => ['battles_won' => 100, 'level' => 50],
            'max_progress' => 100,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'combat',
            'difficulty' => 'hard',
            'prerequisites' => ['battle_victory', 'level_25'],
            'rewards' => ['experience' => 500, 'gold' => 250, 'item' => 'legendary_sword'],
            'metadata' => ['source' => 'combat', 'version' => '1.0'],
        ]);

        $this->assertDatabaseHas('achievement_templates', [
            'name' => 'Battle Master',
            'rarity' => 'epic',
            'category' => 'combat',
        ]);
    }

    /**
     * @test
     */
    public function it_can_fill_fillable_attributes()
    {
        $template = new AchievementTemplate([
            'name' => 'Level Master',
            'description' => 'Reach maximum level',
            'icon' => 'level_master.png',
            'rarity' => 'legendary',
            'points' => 2000,
            'experience_reward' => 1000,
            'gold_reward' => 500,
            'item_reward' => 'ultimate_armor',
            'requirements' => ['level' => 100],
            'max_progress' => 1,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'progression',
            'difficulty' => 'extreme',
            'prerequisites' => ['level_50', 'level_75'],
            'rewards' => ['experience' => 1000, 'gold' => 500],
            'metadata' => ['source' => 'progression', 'version' => '1.1'],
        ]);

        $this->assertEquals('Level Master', $template->name);
        $this->assertEquals('legendary', $template->rarity);
        $this->assertEquals('progression', $template->category);
    }

    /**
     * @test
     */
    public function it_casts_booleans()
    {
        $template = AchievementTemplate::create([
            'name' => 'Test Template',
            'description' => 'A test template',
            'icon' => 'test.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'max_progress' => 1,
            'is_hidden' => true,
            'is_repeatable' => true,
            'category' => 'test',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $this->assertTrue($template->is_hidden);
        $this->assertTrue($template->is_repeatable);
    }

    /**
     * @test
     */
    public function it_casts_json_fields()
    {
        $template = AchievementTemplate::create([
            'name' => 'Test Template',
            'description' => 'A test template',
            'icon' => 'test.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => ['level' => 10, 'battles' => 5],
            'max_progress' => 1,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'test',
            'difficulty' => 'easy',
            'prerequisites' => ['achievement_1', 'achievement_2'],
            'rewards' => ['experience' => 50, 'gold' => 25],
            'metadata' => ['source' => 'test', 'version' => '1.0'],
        ]);

        $this->assertIsArray($template->requirements);
        $this->assertIsArray($template->prerequisites);
        $this->assertIsArray($template->rewards);
        $this->assertIsArray($template->metadata);
    }

    /**
     * @test
     */
    public function it_can_scope_templates_by_rarity()
    {
        AchievementTemplate::create([
            'name' => 'Common Template',
            'description' => 'A common template',
            'icon' => 'common.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'max_progress' => 1,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'test',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        AchievementTemplate::create([
            'name' => 'Rare Template',
            'description' => 'A rare template',
            'icon' => 'rare.png',
            'rarity' => 'rare',
            'points' => 500,
            'experience_reward' => 200,
            'gold_reward' => 100,
            'item_reward' => 'sword',
            'requirements' => [],
            'max_progress' => 1,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'test',
            'difficulty' => 'medium',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $rareTemplates = AchievementTemplate::byRarity('rare')->get();
        $this->assertCount(1, $rareTemplates);
        $this->assertEquals('rare', $rareTemplates->first()->rarity);
    }

    /**
     * @test
     */
    public function it_can_scope_templates_by_category()
    {
        AchievementTemplate::create([
            'name' => 'Combat Template',
            'description' => 'A combat template',
            'icon' => 'combat.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'max_progress' => 1,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'combat',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        AchievementTemplate::create([
            'name' => 'Progression Template',
            'description' => 'A progression template',
            'icon' => 'progression.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'max_progress' => 1,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'progression',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $combatTemplates = AchievementTemplate::byCategory('combat')->get();
        $this->assertCount(1, $combatTemplates);
        $this->assertEquals('combat', $combatTemplates->first()->category);
    }

    /**
     * @test
     */
    public function it_can_scope_templates_by_difficulty()
    {
        AchievementTemplate::create([
            'name' => 'Easy Template',
            'description' => 'An easy template',
            'icon' => 'easy.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'max_progress' => 1,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'test',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        AchievementTemplate::create([
            'name' => 'Hard Template',
            'description' => 'A hard template',
            'icon' => 'hard.png',
            'rarity' => 'rare',
            'points' => 500,
            'experience_reward' => 200,
            'gold_reward' => 100,
            'item_reward' => 'sword',
            'requirements' => [],
            'max_progress' => 1,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'test',
            'difficulty' => 'hard',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $hardTemplates = AchievementTemplate::byDifficulty('hard')->get();
        $this->assertCount(1, $hardTemplates);
        $this->assertEquals('hard', $hardTemplates->first()->difficulty);
    }

    /**
     * @test
     */
    public function it_can_scope_visible_templates()
    {
        AchievementTemplate::create([
            'name' => 'Visible Template',
            'description' => 'A visible template',
            'icon' => 'visible.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'max_progress' => 1,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'test',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        AchievementTemplate::create([
            'name' => 'Hidden Template',
            'description' => 'A hidden template',
            'icon' => 'hidden.png',
            'rarity' => 'rare',
            'points' => 500,
            'experience_reward' => 200,
            'gold_reward' => 100,
            'item_reward' => 'sword',
            'requirements' => [],
            'max_progress' => 1,
            'is_hidden' => true,
            'is_repeatable' => false,
            'category' => 'test',
            'difficulty' => 'medium',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $visibleTemplates = AchievementTemplate::visible()->get();
        $this->assertCount(1, $visibleTemplates);
        $this->assertFalse($visibleTemplates->first()->is_hidden);
    }

    /**
     * @test
     */
    public function it_can_scope_repeatable_templates()
    {
        AchievementTemplate::create([
            'name' => 'Repeatable Template',
            'description' => 'A repeatable template',
            'icon' => 'repeatable.png',
            'rarity' => 'common',
            'points' => 100,
            'experience_reward' => 50,
            'gold_reward' => 25,
            'item_reward' => null,
            'requirements' => [],
            'max_progress' => 1,
            'is_hidden' => false,
            'is_repeatable' => true,
            'category' => 'test',
            'difficulty' => 'easy',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        AchievementTemplate::create([
            'name' => 'Non-Repeatable Template',
            'description' => 'A non-repeatable template',
            'icon' => 'non_repeatable.png',
            'rarity' => 'rare',
            'points' => 500,
            'experience_reward' => 200,
            'gold_reward' => 100,
            'item_reward' => 'sword',
            'requirements' => [],
            'max_progress' => 1,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'test',
            'difficulty' => 'medium',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $repeatableTemplates = AchievementTemplate::repeatable()->get();
        $this->assertCount(1, $repeatableTemplates);
        $this->assertTrue($repeatableTemplates->first()->is_repeatable);
    }

    /**
     * @test
     */
    public function it_can_get_template_summary()
    {
        $template = AchievementTemplate::create([
            'name' => 'Battle Master',
            'description' => 'Master of combat',
            'icon' => 'battle_master.png',
            'rarity' => 'epic',
            'points' => 1000,
            'experience_reward' => 500,
            'gold_reward' => 250,
            'item_reward' => 'legendary_sword',
            'requirements' => [],
            'max_progress' => 1,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'combat',
            'difficulty' => 'hard',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $summary = $template->getSummary();
        $this->assertIsString($summary);
        $this->assertStringContainsString('Battle Master', $summary);
    }

    /**
     * @test
     */
    public function it_can_get_template_details()
    {
        $template = AchievementTemplate::create([
            'name' => 'Battle Master',
            'description' => 'Master of combat',
            'icon' => 'battle_master.png',
            'rarity' => 'epic',
            'points' => 1000,
            'experience_reward' => 500,
            'gold_reward' => 250,
            'item_reward' => 'legendary_sword',
            'requirements' => [],
            'max_progress' => 1,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'combat',
            'difficulty' => 'hard',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $details = $template->getDetails();
        $this->assertIsArray($details);
        $this->assertArrayHasKey('name', $details);
        $this->assertArrayHasKey('description', $details);
    }

    /**
     * @test
     */
    public function it_can_get_template_statistics()
    {
        $template = AchievementTemplate::create([
            'name' => 'Battle Master',
            'description' => 'Master of combat',
            'icon' => 'battle_master.png',
            'rarity' => 'epic',
            'points' => 1000,
            'experience_reward' => 500,
            'gold_reward' => 250,
            'item_reward' => 'legendary_sword',
            'requirements' => [],
            'max_progress' => 1,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'combat',
            'difficulty' => 'hard',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $stats = $template->getStatistics();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('points', $stats);
        $this->assertArrayHasKey('experience_reward', $stats);
    }

    /**
     * @test
     */
    public function it_can_get_template_requirements()
    {
        $template = AchievementTemplate::create([
            'name' => 'Battle Master',
            'description' => 'Master of combat',
            'icon' => 'battle_master.png',
            'rarity' => 'epic',
            'points' => 1000,
            'experience_reward' => 500,
            'gold_reward' => 250,
            'item_reward' => 'legendary_sword',
            'requirements' => ['battles_won' => 100, 'level' => 50],
            'max_progress' => 1,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'combat',
            'difficulty' => 'hard',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $requirements = $template->getRequirements();
        $this->assertIsArray($requirements);
        $this->assertArrayHasKey('battles_won', $requirements);
        $this->assertArrayHasKey('level', $requirements);
    }

    /**
     * @test
     */
    public function it_can_get_template_rewards()
    {
        $template = AchievementTemplate::create([
            'name' => 'Battle Master',
            'description' => 'Master of combat',
            'icon' => 'battle_master.png',
            'rarity' => 'epic',
            'points' => 1000,
            'experience_reward' => 500,
            'gold_reward' => 250,
            'item_reward' => 'legendary_sword',
            'requirements' => [],
            'max_progress' => 1,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'combat',
            'difficulty' => 'hard',
            'prerequisites' => [],
            'rewards' => ['experience' => 500, 'gold' => 250],
            'metadata' => [],
        ]);

        $rewards = $template->getRewards();
        $this->assertIsArray($rewards);
        $this->assertArrayHasKey('experience', $rewards);
        $this->assertArrayHasKey('gold', $rewards);
    }

    /**
     * @test
     */
    public function it_can_get_template_metadata()
    {
        $template = AchievementTemplate::create([
            'name' => 'Battle Master',
            'description' => 'Master of combat',
            'icon' => 'battle_master.png',
            'rarity' => 'epic',
            'points' => 1000,
            'experience_reward' => 500,
            'gold_reward' => 250,
            'item_reward' => 'legendary_sword',
            'requirements' => [],
            'max_progress' => 1,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'combat',
            'difficulty' => 'hard',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => ['source' => 'combat', 'version' => '1.0'],
        ]);

        $metadata = $template->getMetadata();
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('source', $metadata);
        $this->assertArrayHasKey('version', $metadata);
    }

    /**
     * @test
     */
    public function it_can_get_template_timeline()
    {
        $template = AchievementTemplate::create([
            'name' => 'Battle Master',
            'description' => 'Master of combat',
            'icon' => 'battle_master.png',
            'rarity' => 'epic',
            'points' => 1000,
            'experience_reward' => 500,
            'gold_reward' => 250,
            'item_reward' => 'legendary_sword',
            'requirements' => [],
            'max_progress' => 1,
            'is_hidden' => false,
            'is_repeatable' => false,
            'category' => 'combat',
            'difficulty' => 'hard',
            'prerequisites' => [],
            'rewards' => [],
            'metadata' => [],
        ]);

        $timeline = $template->getTimeline();
        $this->assertIsArray($timeline);
        $this->assertArrayHasKey('max_progress', $timeline);
        $this->assertArrayHasKey('difficulty', $timeline);
    }
}
