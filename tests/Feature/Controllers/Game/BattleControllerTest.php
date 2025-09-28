<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\Game\Battle;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BattleControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_all_battles()
    {
        $user = User::factory()->create();
        Battle::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/battles');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'attacker_id',
                        'defender_id',
                        'village_id',
                        'attacker_troops',
                        'defender_troops',
                        'attacker_losses',
                        'defender_losses',
                        'loot',
                        'result',
                        'occurred_at',
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
    public function it_can_get_specific_battle()
    {
        $user = User::factory()->create();
        $battle = Battle::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/battles/{$battle->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'attacker_id',
                'defender_id',
                'village_id',
                'attacker_troops',
                'defender_troops',
                'attacker_losses',
                'defender_losses',
                'loot',
                'result',
                'occurred_at',
                'attacker',
                'defender',
                'village',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_player_battles()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        Battle::factory()->count(2)->create(['attacker_id' => $player->id]);
        Battle::factory()->count(1)->create(['defender_id' => $player->id]);

        $response = $this->actingAs($user)->get('/api/game/battles/my-battles');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'attacker_id',
                        'defender_id',
                        'result',
                        'occurred_at',
                        'attacker',
                        'defender',
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
    public function it_can_get_battle_statistics()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        Battle::factory()->count(5)->create(['attacker_id' => $player->id, 'result' => 'victory']);
        Battle::factory()->count(2)->create(['attacker_id' => $player->id, 'result' => 'defeat']);
        Battle::factory()->count(1)->create(['attacker_id' => $player->id, 'result' => 'draw']);

        $response = $this->actingAs($user)->get('/api/game/battles/statistics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'total_battles',
                'victories',
                'defeats',
                'draws',
                'win_rate',
                'total_loot_gained',
                'recent_battles',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_war_battles()
    {
        $user = User::factory()->create();
        $warId = 1;
        Battle::factory()->count(3)->create(['war_id' => $warId]);

        $response = $this->actingAs($user)->get("/api/game/battles/war/{$warId}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'attacker_id',
                        'defender_id',
                        'result',
                        'occurred_at',
                        'attacker',
                        'defender',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_create_battle_report()
    {
        $user = User::factory()->create();
        $attacker = Player::factory()->create();
        $defender = Player::factory()->create();
        $village = Village::factory()->create();

        $battleData = [
            'attacker_id' => $attacker->id,
            'defender_id' => $defender->id,
            'village_id' => $village->id,
            'attacker_troops' => ['legionnaires' => 100, 'praetorians' => 50],
            'defender_troops' => ['legionnaires' => 80, 'praetorians' => 40],
            'attacker_losses' => ['legionnaires' => 20, 'praetorians' => 10],
            'defender_losses' => ['legionnaires' => 60, 'praetorians' => 30],
            'loot' => ['wood' => 1000, 'clay' => 800, 'iron' => 600, 'crop' => 400],
            'result' => 'victory',
        ];

        $response = $this->actingAs($user)->post('/api/game/battles', $battleData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'battle' => [
                    'id',
                    'attacker_id',
                    'defender_id',
                    'village_id',
                    'result',
                    'occurred_at',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('battles', [
            'attacker_id' => $attacker->id,
            'defender_id' => $defender->id,
            'result' => 'victory',
        ]);
    }

    /**
     * @test
     */
    public function it_can_get_battle_leaderboard()
    {
        $user = User::factory()->create();
        $player1 = Player::factory()->create();
        $player2 = Player::factory()->create();

        Battle::factory()->count(3)->create(['attacker_id' => $player1->id, 'result' => 'victory']);
        Battle::factory()->count(1)->create(['attacker_id' => $player1->id, 'result' => 'defeat']);
        Battle::factory()->count(2)->create(['attacker_id' => $player2->id, 'result' => 'victory']);

        $response = $this->actingAs($user)->get('/api/game/battles/leaderboard');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'player_id',
                        'player_name',
                        'total_battles',
                        'victories',
                        'defeats',
                        'draws',
                        'win_rate',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_filter_battles_by_attacker()
    {
        $user = User::factory()->create();
        $attacker = Player::factory()->create();
        Battle::factory()->count(2)->create(['attacker_id' => $attacker->id]);
        Battle::factory()->count(1)->create(['attacker_id' => Player::factory()->create()->id]);

        $response = $this->actingAs($user)->get("/api/game/battles?attacker_id={$attacker->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_filter_battles_by_defender()
    {
        $user = User::factory()->create();
        $defender = Player::factory()->create();
        Battle::factory()->count(2)->create(['defender_id' => $defender->id]);
        Battle::factory()->count(1)->create(['defender_id' => Player::factory()->create()->id]);

        $response = $this->actingAs($user)->get("/api/game/battles?defender_id={$defender->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_filter_battles_by_result()
    {
        $user = User::factory()->create();
        Battle::factory()->count(2)->create(['result' => 'victory']);
        Battle::factory()->count(1)->create(['result' => 'defeat']);

        $response = $this->actingAs($user)->get('/api/game/battles?result=victory');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_filter_battles_by_war()
    {
        $user = User::factory()->create();
        $warId = 1;
        Battle::factory()->count(2)->create(['war_id' => $warId]);
        Battle::factory()->count(1)->create(['war_id' => 2]);

        $response = $this->actingAs($user)->get("/api/game/battles?war_id={$warId}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_filter_battles_by_date_range()
    {
        $user = User::factory()->create();
        $dateFrom = '2023-01-01';
        $dateTo = '2023-12-31';

        Battle::factory()->create(['occurred_at' => '2023-06-15']);
        Battle::factory()->create(['occurred_at' => '2024-01-15']);

        $response = $this->actingAs($user)->get("/api/game/battles?date_from={$dateFrom}&date_to={$dateTo}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/battles');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_validates_battle_creation_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/battles', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['attacker_id', 'defender_id', 'village_id', 'attacker_troops', 'defender_troops', 'result']);
    }

    /**
     * @test
     */
    public function it_returns_404_for_nonexistent_battle()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/battles/999');

        $response->assertStatus(404);
    }
}
