<?php

namespace Tests\Feature\Game;

use App\Models\Game\Battle;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BattleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Player $player;
    protected Village $village;
    protected Battle $battle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id]);
        $this->village = Village::factory()->create(['player_id' => $this->player->id]);
        $this->battle = Battle::factory()->create([
            'attacker_id' => $this->player->id,
            'defender_id' => $this->player->id,
            'village_id' => $this->village->id,
        ]);
    }

    /**
     * @test
     */
    public function it_can_get_all_battles()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/battles');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'attacker_id',
                        'defender_id',
                        'village_id',
                        'result',
                        'occurred_at',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page'
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_specific_battle()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson("/game/api/battles/{$this->battle->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'attacker_id',
                    'defender_id',
                    'village_id',
                    'result',
                    'occurred_at',
                    'attacker' => [
                        'id',
                        'name'
                    ],
                    'defender' => [
                        'id',
                        'name'
                    ],
                    'village' => [
                        'id',
                        'name'
                    ]
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_player_battles()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/battles/my-battles');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'attacker_id',
                        'defender_id',
                        'result',
                        'occurred_at'
                    ]
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_battle_statistics()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/battles/statistics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_battles',
                    'victories',
                    'defeats',
                    'draws',
                    'win_rate',
                    'total_loot_gained',
                    'recent_battles'
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_create_battle_report()
    {
        $battleData = [
            'attacker_id' => $this->player->id,
            'defender_id' => $this->player->id,
            'village_id' => $this->village->id,
            'attacker_troops' => ['legionnaires' => 100],
            'defender_troops' => ['legionnaires' => 80],
            'result' => 'victory',
        ];

        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/api/battles', $battleData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'attacker_id',
                    'defender_id',
                    'village_id',
                    'result',
                    'occurred_at'
                ]
            ]);

        $this->assertDatabaseHas('battles', [
            'attacker_id' => $this->player->id,
            'defender_id' => $this->player->id,
            'village_id' => $this->village->id,
            'result' => 'victory'
        ]);
    }

    /**
     * @test
     */
    public function it_validates_battle_creation_data()
    {
        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/api/battles', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'attacker_id',
                'defender_id',
                'village_id',
                'attacker_troops',
                'defender_troops',
                'result'
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_battle_leaderboard()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/battles/leaderboard');

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
                        'win_rate'
                    ]
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_filter_battles_by_result()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/battles?result=victory');

        $response->assertStatus(200);
        $battles = $response->json('data');

        foreach ($battles as $battle) {
            $this->assertEquals('victory', $battle['result']);
        }
    }

    /**
     * @test
     */
    public function it_can_filter_battles_by_attacker()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson("/game/api/battles?attacker_id={$this->player->id}");

        $response->assertStatus(200);
        $battles = $response->json('data');

        foreach ($battles as $battle) {
            $this->assertEquals($this->player->id, $battle['attacker_id']);
        }
    }
}
