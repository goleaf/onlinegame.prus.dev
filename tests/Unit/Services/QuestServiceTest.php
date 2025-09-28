<?php

namespace Tests\Unit\Services;

use App\Models\Game\Player;
use App\Models\Game\Quest;
use App\Services\QuestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class QuestServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_quest()
    {
        $player = Player::factory()->create();
        $data = [
            'title' => 'Test Quest',
            'description' => 'Test quest description',
            'type' => 'tutorial',
            'difficulty' => 'easy',
            'requirements' => [['type' => 'level', 'value' => 1]],
            'rewards' => [['type' => 'experience', 'value' => 100]],
        ];

        $service = new QuestService();
        $result = $service->createQuest($player, $data);

        $this->assertInstanceOf(Quest::class, $result);
        $this->assertEquals($data['title'], $result->title);
        $this->assertEquals($data['description'], $result->description);
        $this->assertEquals($data['type'], $result->type);
        $this->assertEquals($data['difficulty'], $result->difficulty);
    }

    /**
     * @test
     */
    public function it_can_assign_quest()
    {
        $player = Player::factory()->create();
        $quest = Quest::factory()->create();

        $service = new QuestService();
        $result = $service->assignQuest($player, $quest);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_complete_quest()
    {
        $player = Player::factory()->create();
        $quest = Quest::factory()->create();

        $service = new QuestService();
        $result = $service->completeQuest($player, $quest);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_abandon_quest()
    {
        $player = Player::factory()->create();
        $quest = Quest::factory()->create();

        $service = new QuestService();
        $result = $service->abandonQuest($player, $quest);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_get_player_quests()
    {
        $player = Player::factory()->create();
        $quests = collect([
            Quest::factory()->create(),
            Quest::factory()->create(),
        ]);

        $player->shouldReceive('quests')->andReturn($quests);

        $service = new QuestService();
        $result = $service->getPlayerQuests($player);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_quest_by_type()
    {
        $player = Player::factory()->create();
        $quests = collect([
            Quest::factory()->create(['type' => 'tutorial']),
            Quest::factory()->create(['type' => 'daily']),
        ]);

        $player->shouldReceive('quests')->andReturn($quests);

        $service = new QuestService();
        $result = $service->getQuestsByType($player, 'tutorial');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_quest_by_difficulty()
    {
        $player = Player::factory()->create();
        $quests = collect([
            Quest::factory()->create(['difficulty' => 'easy']),
            Quest::factory()->create(['difficulty' => 'hard']),
        ]);

        $player->shouldReceive('quests')->andReturn($quests);

        $service = new QuestService();
        $result = $service->getQuestsByDifficulty($player, 'easy');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_quest_by_status()
    {
        $player = Player::factory()->create();
        $quests = collect([
            Quest::factory()->create(['status' => 'active']),
            Quest::factory()->create(['status' => 'completed']),
        ]);

        $player->shouldReceive('quests')->andReturn($quests);

        $service = new QuestService();
        $result = $service->getQuestsByStatus($player, 'active');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_quest_by_requirements()
    {
        $player = Player::factory()->create();
        $quests = collect([
            Quest::factory()->create(['requirements' => [['type' => 'level', 'value' => 1]]]),
            Quest::factory()->create(['requirements' => [['type' => 'level', 'value' => 5]]]),
        ]);

        $player->shouldReceive('quests')->andReturn($quests);

        $service = new QuestService();
        $result = $service->getQuestsByRequirements($player, 'level', 1);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_quest_by_rewards()
    {
        $player = Player::factory()->create();
        $quests = collect([
            Quest::factory()->create(['rewards' => [['type' => 'experience', 'value' => 100]]]),
            Quest::factory()->create(['rewards' => [['type' => 'resources', 'value' => 500]]]),
        ]);

        $player->shouldReceive('quests')->andReturn($quests);

        $service = new QuestService();
        $result = $service->getQuestsByRewards($player, 'experience', 100);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_quest_by_creation_date()
    {
        $player = Player::factory()->create();
        $quests = collect([
            Quest::factory()->create(['created_at' => now()]),
            Quest::factory()->create(['created_at' => now()->subDays(1)]),
        ]);

        $player->shouldReceive('quests')->andReturn($quests);

        $service = new QuestService();
        $result = $service->getQuestsByCreationDate($player, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_quest_by_completion_date()
    {
        $player = Player::factory()->create();
        $quests = collect([
            Quest::factory()->create(['completed_at' => now()]),
            Quest::factory()->create(['completed_at' => now()->subDays(1)]),
        ]);

        $player->shouldReceive('quests')->andReturn($quests);

        $service = new QuestService();
        $result = $service->getQuestsByCompletionDate($player, now()->subDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_quest_by_expiration_date()
    {
        $player = Player::factory()->create();
        $quests = collect([
            Quest::factory()->create(['expires_at' => now()->addDays(1)]),
            Quest::factory()->create(['expires_at' => now()->addDays(2)]),
        ]);

        $player->shouldReceive('quests')->andReturn($quests);

        $service = new QuestService();
        $result = $service->getQuestsByExpirationDate($player, now()->addDays(1));

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_quest_by_priority()
    {
        $player = Player::factory()->create();
        $quests = collect([
            Quest::factory()->create(['priority' => 'high']),
            Quest::factory()->create(['priority' => 'low']),
        ]);

        $player->shouldReceive('quests')->andReturn($quests);

        $service = new QuestService();
        $result = $service->getQuestsByPriority($player, 'high');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_quest_by_category()
    {
        $player = Player::factory()->create();
        $quests = collect([
            Quest::factory()->create(['category' => 'combat']),
            Quest::factory()->create(['category' => 'building']),
        ]);

        $player->shouldReceive('quests')->andReturn($quests);

        $service = new QuestService();
        $result = $service->getQuestsByCategory($player, 'combat');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_quest_by_tags()
    {
        $player = Player::factory()->create();
        $quests = collect([
            Quest::factory()->create(['tags' => ['tutorial', 'beginner']]),
            Quest::factory()->create(['tags' => ['advanced', 'expert']]),
        ]);

        $player->shouldReceive('quests')->andReturn($quests);

        $service = new QuestService();
        $result = $service->getQuestsByTags($player, 'tutorial');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_quest_by_combined_filters()
    {
        $player = Player::factory()->create();
        $quests = collect([
            Quest::factory()->create(['type' => 'tutorial', 'difficulty' => 'easy']),
            Quest::factory()->create(['type' => 'daily', 'difficulty' => 'hard']),
        ]);

        $player->shouldReceive('quests')->andReturn($quests);

        $service = new QuestService();
        $result = $service->getQuestsByCombinedFilters($player, [
            'type' => 'tutorial',
            'difficulty' => 'easy',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_quest_by_search()
    {
        $player = Player::factory()->create();
        $quests = collect([
            Quest::factory()->create(['title' => 'Test Quest']),
            Quest::factory()->create(['title' => 'Another Quest']),
        ]);

        $player->shouldReceive('quests')->andReturn($quests);

        $service = new QuestService();
        $result = $service->getQuestsBySearch($player, 'Test');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_quest_by_sort()
    {
        $player = Player::factory()->create();
        $quests = collect([
            Quest::factory()->create(['priority' => 'high']),
            Quest::factory()->create(['priority' => 'low']),
        ]);

        $player->shouldReceive('quests')->andReturn($quests);

        $service = new QuestService();
        $result = $service->getQuestsBySort($player, 'priority', 'desc');

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_quest_by_pagination()
    {
        $player = Player::factory()->create();
        $quests = collect([
            Quest::factory()->create(),
            Quest::factory()->create(),
        ]);

        $player->shouldReceive('quests')->andReturn($quests);

        $service = new QuestService();
        $result = $service->getQuestsByPagination($player, 1, 10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    /**
     * @test
     */
    public function it_can_get_quest_statistics()
    {
        $service = new QuestService();
        $result = $service->getQuestStatistics();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_quests', $result);
        $this->assertArrayHasKey('by_type', $result);
        $this->assertArrayHasKey('by_difficulty', $result);
    }

    /**
     * @test
     */
    public function it_can_get_quest_leaderboard()
    {
        $service = new QuestService();
        $result = $service->getQuestLeaderboard(10);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
