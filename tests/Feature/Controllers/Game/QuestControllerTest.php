<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\Game\Achievement;
use App\Models\Game\PlayerQuest;
use App\Models\Game\Quest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_all_quests()
    {
        $user = User::factory()->create();
        Quest::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/quests');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
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
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_specific_quest()
    {
        $user = User::factory()->create();
        $quest = Quest::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/quests/{$quest->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'title',
                'description',
                'type',
                'difficulty',
                'category',
                'requirements',
                'rewards',
                'is_active',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * @test
     */
    public function it_can_create_quest()
    {
        $user = User::factory()->create();

        $questData = [
            'title' => 'Defeat 10 Monsters',
            'description' => 'Kill 10 monsters to complete this quest',
            'type' => 'daily',
            'difficulty' => 'medium',
            'category' => 'combat',
            'requirements' => ['monsters_killed' => 10],
            'rewards' => ['experience' => 100, 'gold' => 50],
            'is_active' => true,
        ];

        $response = $this->actingAs($user)->post('/api/game/quests', $questData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'quest' => [
                    'id',
                    'title',
                    'description',
                    'type',
                    'difficulty',
                    'category',
                    'requirements',
                    'rewards',
                    'is_active',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('quests', [
            'title' => 'Defeat 10 Monsters',
            'type' => 'daily',
            'difficulty' => 'medium',
        ]);
    }

    /**
     * @test
     */
    public function it_can_update_quest()
    {
        $user = User::factory()->create();
        $quest = Quest::factory()->create();

        $updateData = [
            'title' => 'Updated Quest Title',
            'description' => 'Updated quest description',
            'difficulty' => 'hard',
        ];

        $response = $this->actingAs($user)->put("/api/game/quests/{$quest->id}", $updateData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'quest' => [
                    'id',
                    'title',
                    'description',
                    'difficulty',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('quests', [
            'id' => $quest->id,
            'title' => 'Updated Quest Title',
            'difficulty' => 'hard',
        ]);
    }

    /**
     * @test
     */
    public function it_can_delete_quest()
    {
        $user = User::factory()->create();
        $quest = Quest::factory()->create();

        $response = $this->actingAs($user)->delete("/api/game/quests/{$quest->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseMissing('quests', ['id' => $quest->id]);
    }

    /**
     * @test
     */
    public function it_can_get_player_quests()
    {
        $user = User::factory()->create();
        $player = $user->player;
        PlayerQuest::factory()->count(3)->create(['player_id' => $player->id]);

        $response = $this->actingAs($user)->get('/api/game/quests/my-quests');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'quest_id',
                        'player_id',
                        'status',
                        'progress',
                        'started_at',
                        'completed_at',
                        'quest',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_start_quest()
    {
        $user = User::factory()->create();
        $quest = Quest::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post("/api/game/quests/{$quest->id}/start");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'player_quest' => [
                    'id',
                    'quest_id',
                    'player_id',
                    'status',
                    'progress',
                    'started_at',
                ],
            ]);

        $this->assertDatabaseHas('player_quests', [
            'quest_id' => $quest->id,
            'player_id' => $user->player->id,
            'status' => 'in_progress',
        ]);
    }

    /**
     * @test
     */
    public function it_can_complete_quest()
    {
        $user = User::factory()->create();
        $player = $user->player;
        $quest = Quest::factory()->create();
        $playerQuest = PlayerQuest::factory()->create([
            'quest_id' => $quest->id,
            'player_id' => $player->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($user)->post("/api/game/quests/{$quest->id}/complete");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'rewards' => [
                    'experience',
                    'gold',
                    'items',
                ],
            ]);

        $this->assertDatabaseHas('player_quests', [
            'id' => $playerQuest->id,
            'status' => 'completed',
        ]);
    }

    /**
     * @test
     */
    public function it_can_get_quest_progress()
    {
        $user = User::factory()->create();
        $player = $user->player;
        $quest = Quest::factory()->create();
        $playerQuest = PlayerQuest::factory()->create([
            'quest_id' => $quest->id,
            'player_id' => $player->id,
            'status' => 'in_progress',
            'progress' => 50,
        ]);

        $response = $this->actingAs($user)->get("/api/game/quests/{$quest->id}/progress");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'quest_id',
                'player_id',
                'status',
                'progress',
                'requirements',
                'completed_requirements',
                'started_at',
                'estimated_completion',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_achievements()
    {
        $user = User::factory()->create();
        Achievement::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/quests/achievements');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'achievement_type',
                        'title',
                        'description',
                        'icon',
                        'rarity',
                        'points',
                        'experience_reward',
                        'gold_reward',
                        'item_reward',
                        'requirements',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_filter_quests_by_type()
    {
        $user = User::factory()->create();
        Quest::factory()->count(2)->create(['type' => 'daily']);
        Quest::factory()->count(1)->create(['type' => 'weekly']);

        $response = $this->actingAs($user)->get('/api/game/quests?type=daily');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_filter_quests_by_difficulty()
    {
        $user = User::factory()->create();
        Quest::factory()->count(2)->create(['difficulty' => 'easy']);
        Quest::factory()->count(1)->create(['difficulty' => 'hard']);

        $response = $this->actingAs($user)->get('/api/game/quests?difficulty=easy');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_filter_quests_by_category()
    {
        $user = User::factory()->create();
        Quest::factory()->count(2)->create(['category' => 'combat']);
        Quest::factory()->count(1)->create(['category' => 'exploration']);

        $response = $this->actingAs($user)->get('/api/game/quests?category=combat');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/quests');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_validates_quest_creation_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/quests', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'type', 'difficulty', 'category']);
    }

    /**
     * @test
     */
    public function it_validates_quest_type_enum()
    {
        $user = User::factory()->create();

        $questData = [
            'title' => 'Test Quest',
            'type' => 'invalid_type',
            'difficulty' => 'easy',
            'category' => 'test',
        ];

        $response = $this->actingAs($user)->post('/api/game/quests', $questData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    /**
     * @test
     */
    public function it_returns_404_for_nonexistent_quest()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/quests/999');

        $response->assertStatus(404);
    }
}
