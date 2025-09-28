<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\Game\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_all_players()
    {
        $user = User::factory()->create();
        Player::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/players');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'tribe',
                        'alliance_id',
                        'world_id',
                        'level',
                        'experience',
                        'points',
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
    public function it_can_get_specific_player()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/players/{$player->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'tribe',
                'alliance_id',
                'world_id',
                'level',
                'experience',
                'points',
                'user',
                'world',
                'alliance',
                'villages',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * @test
     */
    public function it_can_create_player()
    {
        $user = User::factory()->create();

        $playerData = [
            'name' => 'TestPlayer',
            'tribe' => 'roman',
            'world_id' => 1,
        ];

        $response = $this->actingAs($user)->post('/api/game/players', $playerData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'player' => [
                    'id',
                    'name',
                    'tribe',
                    'world_id',
                    'level',
                    'experience',
                    'points',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('players', [
            'name' => 'TestPlayer',
            'tribe' => 'roman',
            'world_id' => 1,
        ]);
    }

    /**
     * @test
     */
    public function it_can_update_player()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create();

        $updateData = [
            'name' => 'UpdatedPlayerName',
            'tribe' => 'teuton',
        ];

        $response = $this->actingAs($user)->put("/api/game/players/{$player->id}", $updateData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'player' => [
                    'id',
                    'name',
                    'tribe',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('players', [
            'id' => $player->id,
            'name' => 'UpdatedPlayerName',
            'tribe' => 'teuton',
        ]);
    }

    /**
     * @test
     */
    public function it_can_delete_player()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create();

        $response = $this->actingAs($user)->delete("/api/game/players/{$player->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseMissing('players', ['id' => $player->id]);
    }

    /**
     * @test
     */
    public function it_can_get_players_with_stats()
    {
        $user = User::factory()->create();
        Player::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/players/with-stats');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'tribe',
                        'alliance_id',
                        'world_id',
                        'level',
                        'experience',
                        'points',
                        'stats',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_filter_players_by_world()
    {
        $user = User::factory()->create();
        Player::factory()->count(2)->create(['world_id' => 1]);
        Player::factory()->count(1)->create(['world_id' => 2]);

        $response = $this->actingAs($user)->get('/api/game/players?world_id=1');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_filter_players_by_tribe()
    {
        $user = User::factory()->create();
        Player::factory()->count(2)->create(['tribe' => 'roman']);
        Player::factory()->count(1)->create(['tribe' => 'teuton']);

        $response = $this->actingAs($user)->get('/api/game/players?tribe=roman');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_filter_players_by_alliance()
    {
        $user = User::factory()->create();
        $allianceId = 1;
        Player::factory()->count(2)->create(['alliance_id' => $allianceId]);
        Player::factory()->count(1)->create(['alliance_id' => 2]);

        $response = $this->actingAs($user)->get("/api/game/players?alliance_id={$allianceId}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_search_players_by_name()
    {
        $user = User::factory()->create();
        Player::factory()->create(['name' => 'PlayerOne']);
        Player::factory()->create(['name' => 'PlayerTwo']);
        Player::factory()->create(['name' => 'AnotherPlayer']);

        $response = $this->actingAs($user)->get('/api/game/players?search=Player');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_player_leaderboard()
    {
        $user = User::factory()->create();
        Player::factory()->count(5)->create();

        $response = $this->actingAs($user)->get('/api/game/players/leaderboard');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'tribe',
                        'level',
                        'experience',
                        'points',
                        'rank',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_player_statistics()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/players/{$player->id}/statistics");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'player_id',
                'total_villages',
                'total_population',
                'total_culture_points',
                'battle_statistics',
                'resource_statistics',
                'achievement_statistics',
            ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/players');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_validates_player_creation_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/players', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'tribe', 'world_id']);
    }

    /**
     * @test
     */
    public function it_validates_tribe_enum()
    {
        $user = User::factory()->create();

        $playerData = [
            'name' => 'TestPlayer',
            'tribe' => 'invalid_tribe',
            'world_id' => 1,
        ];

        $response = $this->actingAs($user)->post('/api/game/players', $playerData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tribe']);
    }

    /**
     * @test
     */
    public function it_returns_404_for_nonexistent_player()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/players/999');

        $response->assertStatus(404);
    }
}
