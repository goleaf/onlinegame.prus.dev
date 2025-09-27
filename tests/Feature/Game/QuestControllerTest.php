<?php

namespace Tests\Feature\Game;

use Tests\TestCase;
use App\Models\Game\Player;
use App\Models\Game\Quest;
use App\Models\Game\PlayerQuest;
use App\Models\Game\Achievement;
use App\Models\Game\PlayerAchievement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class QuestControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $player;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id]);
        
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_get_quests()
    {
        Quest::factory()->count(5)->create(['is_active' => true]);

        $response = $this->getJson('/game/api/quests');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'data' => [
                            '*' => [
                                'id',
                                'title',
                                'description',
                                'type',
                                'difficulty',
                                'category',
                                'requirements',
                                'rewards',
                                'is_active',
                                'created_at'
                            ]
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_can_filter_quests_by_type()
    {
        Quest::factory()->create(['type' => 'daily', 'is_active' => true]);
        Quest::factory()->create(['type' => 'weekly', 'is_active' => true]);

        $response = $this->getJson('/game/api/quests?type=daily');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('daily', $data[0]['type']);
    }

    /** @test */
    public function it_can_get_specific_quest()
    {
        $quest = Quest::factory()->create(['is_active' => true]);

        $response = $this->getJson("/game/api/quests/{$quest->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'title',
                        'description',
                        'type',
                        'difficulty',
                        'category',
                        'requirements',
                        'rewards',
                        'progress',
                        'status'
                    ]
                ]);
    }

    /** @test */
    public function it_can_get_player_quests()
    {
        $quest = Quest::factory()->create(['is_active' => true]);
        PlayerQuest::factory()->create([
            'player_id' => $this->player->id,
            'quest_id' => $quest->id,
            'status' => 'in_progress'
        ]);

        $response = $this->getJson('/game/api/quests/my-quests');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        '*' => [
                            'id',
                            'quest_id',
                            'status',
                            'progress',
                            'started_at',
                            'quest' => [
                                'id',
                                'title',
                                'description',
                                'type',
                                'difficulty'
                            ]
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_can_start_a_quest()
    {
        $quest = Quest::factory()->create(['is_active' => true]);

        $response = $this->postJson("/game/api/quests/{$quest->id}/start");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'player_id',
                        'quest_id',
                        'status',
                        'progress',
                        'started_at'
                    ]
                ]);

        $this->assertDatabaseHas('player_quests', [
            'player_id' => $this->player->id,
            'quest_id' => $quest->id,
            'status' => 'in_progress'
        ]);
    }

    /** @test */
    public function it_cannot_start_inactive_quest()
    {
        $quest = Quest::factory()->create(['is_active' => false]);

        $response = $this->postJson("/game/api/quests/{$quest->id}/start");

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Quest is not available'
                ]);
    }

    /** @test */
    public function it_cannot_start_already_started_quest()
    {
        $quest = Quest::factory()->create(['is_active' => true]);
        PlayerQuest::factory()->create([
            'player_id' => $this->player->id,
            'quest_id' => $quest->id,
            'status' => 'in_progress'
        ]);

        $response = $this->postJson("/game/api/quests/{$quest->id}/start");

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Quest is already started or completed'
                ]);
    }

    /** @test */
    public function it_can_complete_a_quest()
    {
        $quest = Quest::factory()->create([
            'is_active' => true,
            'requirements' => ['count' => 1],
            'rewards' => ['experience' => 100]
        ]);
        
        $playerQuest = PlayerQuest::factory()->create([
            'player_id' => $this->player->id,
            'quest_id' => $quest->id,
            'status' => 'in_progress',
            'progress' => 1
        ]);

        $response = $this->postJson("/game/api/quests/{$quest->id}/complete");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'rewards'
                    ]
                ]);

        $this->assertDatabaseHas('player_quests', [
            'id' => $playerQuest->id,
            'status' => 'completed'
        ]);
    }

    /** @test */
    public function it_cannot_complete_quest_without_progress()
    {
        $quest = Quest::factory()->create([
            'is_active' => true,
            'requirements' => ['count' => 5]
        ]);
        
        PlayerQuest::factory()->create([
            'player_id' => $this->player->id,
            'quest_id' => $quest->id,
            'status' => 'in_progress',
            'progress' => 2
        ]);

        $response = $this->postJson("/game/api/quests/{$quest->id}/complete");

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'message' => 'Quest requirements not met'
                ]);
    }

    /** @test */
    public function it_can_get_player_achievements()
    {
        $achievement = Achievement::factory()->create();
        PlayerAchievement::factory()->create([
            'player_id' => $this->player->id,
            'achievement_id' => $achievement->id
        ]);

        $response = $this->getJson('/game/api/quests/achievements');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        '*' => [
                            'id',
                            'player_id',
                            'achievement_id',
                            'unlocked_at',
                            'achievement' => [
                                'id',
                                'name',
                                'description',
                                'category',
                                'rarity'
                            ]
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_can_get_achievement_leaderboard()
    {
        $achievement = Achievement::factory()->create(['points' => 100]);
        PlayerAchievement::factory()->create([
            'player_id' => $this->player->id,
            'achievement_id' => $achievement->id
        ]);

        $response = $this->getJson('/game/api/quests/achievement-leaderboard?limit=5');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        '*' => [
                            'player_id',
                            'player_name',
                            'total_achievements',
                            'total_points'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_can_get_quest_statistics()
    {
        $quest = Quest::factory()->create(['is_active' => true]);
        PlayerQuest::factory()->create([
            'player_id' => $this->player->id,
            'quest_id' => $quest->id,
            'status' => 'completed'
        ]);

        $achievement = Achievement::factory()->create(['points' => 50]);
        PlayerAchievement::factory()->create([
            'player_id' => $this->player->id,
            'achievement_id' => $achievement->id
        ]);

        $response = $this->getJson('/game/api/quests/statistics');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'quests' => [
                            'total_started',
                            'total_completed',
                            'completion_rate',
                            'active_quests',
                            'total_experience_gained'
                        ],
                        'achievements' => [
                            'total_unlocked',
                            'total_points',
                            'by_rarity'
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        auth()->logout();

        $response = $this->getJson('/game/api/quests');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_quest_filters()
    {
        $response = $this->getJson('/game/api/quests?type=invalid_type');

        // Should still return results but ignore invalid filter
        $response->assertStatus(200);
    }

    /** @test */
    public function it_handles_nonexistent_quest()
    {
        $response = $this->getJson('/game/api/quests/99999');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Quest not found.'
                ]);
    }

    /** @test */
    public function it_can_filter_achievements_by_category()
    {
        $battleAchievement = Achievement::factory()->create(['category' => 'battle']);
        $buildingAchievement = Achievement::factory()->create(['category' => 'building']);
        
        PlayerAchievement::factory()->create([
            'player_id' => $this->player->id,
            'achievement_id' => $battleAchievement->id
        ]);
        
        PlayerAchievement::factory()->create([
            'player_id' => $this->player->id,
            'achievement_id' => $buildingAchievement->id
        ]);

        $response = $this->getJson('/game/api/quests/achievements?category=battle');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('battle', $data[0]['achievement']['category']);
    }

    /** @test */
    public function it_can_filter_achievements_by_rarity()
    {
        $commonAchievement = Achievement::factory()->create(['rarity' => 'common']);
        $rareAchievement = Achievement::factory()->create(['rarity' => 'rare']);
        
        PlayerAchievement::factory()->create([
            'player_id' => $this->player->id,
            'achievement_id' => $commonAchievement->id
        ]);
        
        PlayerAchievement::factory()->create([
            'player_id' => $this->player->id,
            'achievement_id' => $rareAchievement->id
        ]);

        $response = $this->getJson('/game/api/quests/achievements?rarity=rare');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('rare', $data[0]['achievement']['rarity']);
    }
}
