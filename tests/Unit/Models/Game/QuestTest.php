<?php

namespace Tests\Unit\Models\Game;

use App\Models\Game\Player;
use App\Models\Game\Quest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestTest extends TestCase
{
    use RefreshDatabase;

    protected Quest $quest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->quest = new Quest();
    }

    /**
     * @test
     */
    public function it_can_create_quest()
    {
        $quest = Quest::create([
            'name' => 'First Village',
            'key' => 'first_village',
            'description' => 'Build your first village',
            'instructions' => 'Construct a town hall',
            'category' => 'tutorial',
            'difficulty' => 'easy',
            'requirements' => ['building' => 'town_hall', 'level' => 1],
            'rewards' => ['experience' => 100, 'gold' => 50],
            'experience_reward' => 100,
            'gold_reward' => 50,
            'resource_rewards' => ['wood' => 100, 'clay' => 50],
            'is_repeatable' => false,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Quest::class, $quest);
        $this->assertEquals('First Village', $quest->name);
        $this->assertEquals('first_village', $quest->key);
        $this->assertEquals('tutorial', $quest->category);
        $this->assertEquals('easy', $quest->difficulty);
        $this->assertEquals(100, $quest->experience_reward);
        $this->assertEquals(50, $quest->gold_reward);
        $this->assertFalse($quest->is_repeatable);
        $this->assertTrue($quest->is_active);
    }

    /**
     * @test
     */
    public function it_casts_requirements_to_array()
    {
        $quest = Quest::create([
            'name' => 'Test Quest',
            'key' => 'test_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
            'requirements' => ['building' => 'town_hall', 'level' => 1],
        ]);

        $this->assertIsArray($quest->requirements);
        $this->assertEquals(['building' => 'town_hall', 'level' => 1], $quest->requirements);
    }

    /**
     * @test
     */
    public function it_casts_rewards_to_array()
    {
        $quest = Quest::create([
            'name' => 'Test Quest',
            'key' => 'test_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
            'rewards' => ['experience' => 100, 'gold' => 50],
        ]);

        $this->assertIsArray($quest->rewards);
        $this->assertEquals(['experience' => 100, 'gold' => 50], $quest->rewards);
    }

    /**
     * @test
     */
    public function it_casts_resource_rewards_to_array()
    {
        $quest = Quest::create([
            'name' => 'Test Quest',
            'key' => 'test_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
            'resource_rewards' => ['wood' => 100, 'clay' => 50],
        ]);

        $this->assertIsArray($quest->resource_rewards);
        $this->assertEquals(['wood' => 100, 'clay' => 50], $quest->resource_rewards);
    }

    /**
     * @test
     */
    public function it_casts_boolean_fields()
    {
        $quest = Quest::create([
            'name' => 'Test Quest',
            'key' => 'test_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
            'is_repeatable' => true,
            'is_active' => false,
            'isCustomEvent' => true,
        ]);

        $this->assertTrue($quest->is_repeatable);
        $this->assertFalse($quest->is_active);
        $this->assertTrue($quest->isCustomEvent);
    }

    /**
     * @test
     */
    public function it_belongs_to_many_players()
    {
        $quest = Quest::create([
            'name' => 'Test Quest',
            'key' => 'test_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
        ]);

        $player1 = Player::factory()->create();
        $player2 = Player::factory()->create();

        $quest->players()->attach($player1->id, [
            'status' => 'active',
            'progress' => 50,
            'started_at' => now(),
        ]);

        $quest->players()->attach($player2->id, [
            'status' => 'completed',
            'progress' => 100,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $this->assertCount(2, $quest->players);
        $this->assertTrue($quest->players->contains($player1));
        $this->assertTrue($quest->players->contains($player2));
    }

    /**
     * @test
     */
    public function it_has_active_scope()
    {
        Quest::create([
            'name' => 'Active Quest',
            'key' => 'active_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
            'is_active' => true,
        ]);

        Quest::create([
            'name' => 'Inactive Quest',
            'key' => 'inactive_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
            'is_active' => false,
        ]);

        $activeQuests = Quest::active()->get();
        $this->assertCount(1, $activeQuests);
        $this->assertEquals('Active Quest', $activeQuests->first()->name);
    }

    /**
     * @test
     */
    public function it_has_by_category_scope()
    {
        Quest::create([
            'name' => 'Tutorial Quest',
            'key' => 'tutorial_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
        ]);

        Quest::create([
            'name' => 'Building Quest',
            'key' => 'building_quest',
            'description' => 'Test description',
            'category' => 'building',
            'difficulty' => 'easy',
        ]);

        $tutorialQuests = Quest::byCategory('tutorial')->get();
        $this->assertCount(1, $tutorialQuests);
        $this->assertEquals('Tutorial Quest', $tutorialQuests->first()->name);
    }

    /**
     * @test
     */
    public function it_has_by_difficulty_scope()
    {
        Quest::create([
            'name' => 'Easy Quest',
            'key' => 'easy_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
        ]);

        Quest::create([
            'name' => 'Hard Quest',
            'key' => 'hard_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'hard',
        ]);

        $easyQuests = Quest::byDifficulty('easy')->get();
        $this->assertCount(1, $easyQuests);
        $this->assertEquals('Easy Quest', $easyQuests->first()->name);
    }

    /**
     * @test
     */
    public function it_has_repeatable_scope()
    {
        Quest::create([
            'name' => 'Repeatable Quest',
            'key' => 'repeatable_quest',
            'description' => 'Test description',
            'category' => 'daily',
            'difficulty' => 'easy',
            'is_repeatable' => true,
        ]);

        Quest::create([
            'name' => 'One-time Quest',
            'key' => 'one_time_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
            'is_repeatable' => false,
        ]);

        $repeatableQuests = Quest::repeatable()->get();
        $this->assertCount(1, $repeatableQuests);
        $this->assertEquals('Repeatable Quest', $repeatableQuests->first()->name);
    }

    /**
     * @test
     */
    public function it_has_tutorial_scope()
    {
        Quest::create([
            'name' => 'Tutorial Quest',
            'key' => 'tutorial_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
        ]);

        Quest::create([
            'name' => 'Building Quest',
            'key' => 'building_quest',
            'description' => 'Test description',
            'category' => 'building',
            'difficulty' => 'easy',
        ]);

        $tutorialQuests = Quest::tutorial()->get();
        $this->assertCount(1, $tutorialQuests);
        $this->assertEquals('Tutorial Quest', $tutorialQuests->first()->name);
    }

    /**
     * @test
     */
    public function it_has_building_scope()
    {
        Quest::create([
            'name' => 'Building Quest',
            'key' => 'building_quest',
            'description' => 'Test description',
            'category' => 'building',
            'difficulty' => 'easy',
        ]);

        Quest::create([
            'name' => 'Combat Quest',
            'key' => 'combat_quest',
            'description' => 'Test description',
            'category' => 'combat',
            'difficulty' => 'easy',
        ]);

        $buildingQuests = Quest::building()->get();
        $this->assertCount(1, $buildingQuests);
        $this->assertEquals('Building Quest', $buildingQuests->first()->name);
    }

    /**
     * @test
     */
    public function it_has_combat_scope()
    {
        Quest::create([
            'name' => 'Combat Quest',
            'key' => 'combat_quest',
            'description' => 'Test description',
            'category' => 'combat',
            'difficulty' => 'easy',
        ]);

        Quest::create([
            'name' => 'Building Quest',
            'key' => 'building_quest',
            'description' => 'Test description',
            'category' => 'building',
            'difficulty' => 'easy',
        ]);

        $combatQuests = Quest::combat()->get();
        $this->assertCount(1, $combatQuests);
        $this->assertEquals('Combat Quest', $combatQuests->first()->name);
    }

    /**
     * @test
     */
    public function it_has_recent_scope()
    {
        Quest::create([
            'name' => 'Recent Quest',
            'key' => 'recent_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
            'created_at' => now()->subDays(3),
        ]);

        Quest::create([
            'name' => 'Old Quest',
            'key' => 'old_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
            'created_at' => now()->subDays(40),
        ]);

        $recentQuests = Quest::recent(30)->get();
        $this->assertCount(1, $recentQuests);
        $this->assertEquals('Recent Quest', $recentQuests->first()->name);
    }

    /**
     * @test
     */
    public function it_has_search_scope()
    {
        Quest::create([
            'name' => 'Building Quest',
            'key' => 'building_quest',
            'description' => 'Build structures',
            'category' => 'building',
            'difficulty' => 'easy',
        ]);

        Quest::create([
            'name' => 'Combat Quest',
            'key' => 'combat_quest',
            'description' => 'Fight enemies',
            'category' => 'combat',
            'difficulty' => 'easy',
        ]);

        $searchResults = Quest::search('building')->get();
        $this->assertCount(1, $searchResults);
        $this->assertEquals('Building Quest', $searchResults->first()->name);
    }

    /**
     * @test
     */
    public function it_calculates_completion_rate()
    {
        $quest = Quest::create([
            'name' => 'Test Quest',
            'key' => 'test_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
        ]);

        // Mock the counts
        $quest->players_count = 10;
        $quest->completed_count = 7;

        $completionRate = $quest->completion_rate;
        $this->assertEquals(70.0, $completionRate);
    }

    /**
     * @test
     */
    public function it_returns_difficulty_color()
    {
        $easyQuest = Quest::create([
            'name' => 'Easy Quest',
            'key' => 'easy_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
        ]);

        $hardQuest = Quest::create([
            'name' => 'Hard Quest',
            'key' => 'hard_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'hard',
        ]);

        $this->assertEquals('green', $easyQuest->difficulty_color);
        $this->assertEquals('orange', $hardQuest->difficulty_color);
    }

    /**
     * @test
     */
    public function it_returns_category_icon()
    {
        $tutorialQuest = Quest::create([
            'name' => 'Tutorial Quest',
            'key' => 'tutorial_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
        ]);

        $buildingQuest = Quest::create([
            'name' => 'Building Quest',
            'key' => 'building_quest',
            'description' => 'Test description',
            'category' => 'building',
            'difficulty' => 'easy',
        ]);

        $this->assertEquals('book', $tutorialQuest->category_icon);
        $this->assertEquals('home', $buildingQuest->category_icon);
    }

    /**
     * @test
     */
    public function it_checks_if_completed_by_player()
    {
        $quest = Quest::create([
            'name' => 'Test Quest',
            'key' => 'test_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
        ]);

        $player = Player::factory()->create();

        $quest->players()->attach($player->id, [
            'status' => 'completed',
            'progress' => 100,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $this->assertTrue($quest->isCompletedByPlayer($player->id));
    }

    /**
     * @test
     */
    public function it_checks_if_active_for_player()
    {
        $quest = Quest::create([
            'name' => 'Test Quest',
            'key' => 'test_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
        ]);

        $player = Player::factory()->create();

        $quest->players()->attach($player->id, [
            'status' => 'active',
            'progress' => 50,
            'started_at' => now(),
        ]);

        $this->assertTrue($quest->isActiveForPlayer($player->id));
    }

    /**
     * @test
     */
    public function it_checks_if_can_be_started_by_player()
    {
        $quest = Quest::create([
            'name' => 'Test Quest',
            'key' => 'test_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
            'is_active' => true,
        ]);

        $player = Player::factory()->create();

        $this->assertTrue($quest->canBeStartedByPlayer($player->id));
    }

    /**
     * @test
     */
    public function it_cannot_be_started_if_completed()
    {
        $quest = Quest::create([
            'name' => 'Test Quest',
            'key' => 'test_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
            'is_active' => true,
        ]);

        $player = Player::factory()->create();

        $quest->players()->attach($player->id, [
            'status' => 'completed',
            'progress' => 100,
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $this->assertFalse($quest->canBeStartedByPlayer($player->id));
    }

    /**
     * @test
     */
    public function it_cannot_be_started_if_active()
    {
        $quest = Quest::create([
            'name' => 'Test Quest',
            'key' => 'test_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
            'is_active' => true,
        ]);

        $player = Player::factory()->create();

        $quest->players()->attach($player->id, [
            'status' => 'active',
            'progress' => 50,
            'started_at' => now(),
        ]);

        $this->assertFalse($quest->canBeStartedByPlayer($player->id));
    }

    /**
     * @test
     */
    public function it_cannot_be_started_if_inactive()
    {
        $quest = Quest::create([
            'name' => 'Test Quest',
            'key' => 'test_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
            'is_active' => false,
        ]);

        $player = Player::factory()->create();

        $this->assertFalse($quest->canBeStartedByPlayer($player->id));
    }

    /**
     * @test
     */
    public function it_generates_tutorial_quest()
    {
        $quest = Quest::generateTutorialQuest(
            'first_village',
            'First Village',
            'Build your first village'
        );

        $this->assertEquals('first_village', $quest->key);
        $this->assertEquals('First Village', $quest->name);
        $this->assertEquals('tutorial', $quest->category);
        $this->assertEquals('easy', $quest->difficulty);
        $this->assertFalse($quest->is_repeatable);
        $this->assertTrue($quest->is_active);
        $this->assertEquals(100, $quest->experience_reward);
        $this->assertEquals(50, $quest->gold_reward);
    }

    /**
     * @test
     */
    public function it_generates_daily_quest()
    {
        $quest = Quest::generateDailyQuest(
            'daily_battle',
            'Daily Battle',
            'Win a battle today'
        );

        $this->assertEquals('daily_battle', $quest->key);
        $this->assertEquals('Daily Battle', $quest->name);
        $this->assertEquals('daily', $quest->category);
        $this->assertEquals('medium', $quest->difficulty);
        $this->assertTrue($quest->is_repeatable);
        $this->assertTrue($quest->is_active);
        $this->assertEquals(200, $quest->experience_reward);
        $this->assertEquals(100, $quest->gold_reward);
    }

    /**
     * @test
     */
    public function it_generates_weekly_quest()
    {
        $quest = Quest::generateWeeklyQuest(
            'weekly_conquest',
            'Weekly Conquest',
            'Conquer a village this week'
        );

        $this->assertEquals('weekly_conquest', $quest->key);
        $this->assertEquals('Weekly Conquest', $quest->name);
        $this->assertEquals('weekly', $quest->category);
        $this->assertEquals('hard', $quest->difficulty);
        $this->assertTrue($quest->is_repeatable);
        $this->assertTrue($quest->is_active);
        $this->assertEquals(500, $quest->experience_reward);
        $this->assertEquals(250, $quest->gold_reward);
    }

    /**
     * @test
     */
    public function it_generates_reference_number()
    {
        $quest = Quest::create([
            'name' => 'Test Quest',
            'key' => 'test_quest',
            'description' => 'Test description',
            'category' => 'tutorial',
            'difficulty' => 'easy',
        ]);

        $this->assertNotNull($quest->reference_number);
        $this->assertStringStartsWith('QST-', $quest->reference_number);
    }

    /**
     * @test
     */
    public function it_can_be_filled_with_mass_assignment()
    {
        $data = [
            'name' => 'Mass Assignment Test',
            'key' => 'mass_assignment_test',
            'description' => 'Test mass assignment',
            'instructions' => 'Follow the instructions',
            'category' => 'building',
            'difficulty' => 'medium',
            'requirements' => ['building' => 'barracks', 'level' => 5],
            'rewards' => ['experience' => 200, 'gold' => 100],
            'experience_reward' => 200,
            'gold_reward' => 100,
            'resource_rewards' => ['wood' => 200, 'clay' => 100],
            'is_repeatable' => true,
            'is_active' => false,
            'isCustomEvent' => true,
        ];

        $quest = Quest::create($data);

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $quest->$key);
        }
    }

    /**
     * @test
     */
    public function it_has_reference_trait()
    {
        $this->assertTrue(method_exists($this->quest, 'generateReference'));
    }

    /**
     * @test
     */
    public function it_has_auditing_trait()
    {
        $this->assertTrue(method_exists($this->quest, 'audits'));
    }

    /**
     * @test
     */
    public function it_has_taxonomy_trait()
    {
        $this->assertTrue(method_exists($this->quest, 'taxonomies'));
    }

    /**
     * @test
     */
    public function it_has_notable_trait()
    {
        $this->assertTrue(method_exists($this->quest, 'notables'));
    }

    /**
     * @test
     */
    public function it_has_commentable_trait()
    {
        $this->assertTrue(method_exists($this->quest, 'comments'));
    }
}
