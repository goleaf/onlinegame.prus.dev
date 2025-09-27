<?php

namespace Tests\Feature\Game;

use App\Models\Game\Battle;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BattleControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Player $player;
    protected Village $village;
    protected Player $attacker;
    protected Player $defender;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id]);
        $this->village = Village::factory()->create(['player_id' => $this->player->id]);
        $this->attacker = Player::factory()->create();
        $this->defender = Player::factory()->create();
    }

    /** @test */
    public function it_can_list_battles()
    {
        $battles = Battle::factory()->count(3)->create([
            'attacker_id' => $this->attacker->id,
            'defender_id' => $this->defender->id,
            'village_id' => $this->village->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/game/api/battles');

        $response->assertStatus(200)
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
                        'updated_at',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ]
            ]);
    }

    /** @test */
    public function it_can_filter_battles_by_result()
    {
        Battle::factory()->create([
            'attacker_id' => $this->attacker->id,
            'defender_id' => $this->defender->id,
            'village_id' => $this->village->id,
            'result' => 'victory',
        ]);

        Battle::factory()->create([
            'attacker_id' => $this->attacker->id,
            'defender_id' => $this->defender->id,
            'village_id' => $this->village->id,
            'result' => 'defeat',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/game/api/battles?result=victory');

        $response->assertStatus(200);
        $responseData = $response->json('data');
        $this->assertCount(1, $responseData);
        $this->assertEquals('victory', $responseData[0]['result']);
    }

    /** @test */
    public function it_can_create_a_battle()
    {
        $battleData = [
            'attacker_id' => $this->attacker->id,
            'defender_id' => $this->defender->id,
            'village_id' => $this->village->id,
            'attacker_troops' => json_encode(['legionnaires' => 100]),
            'defender_troops' => json_encode(['legionnaires' => 80]),
            'result' => 'victory',
            'occurred_at' => now()->toDateTimeString(),
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/game/api/battles', $battleData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'attacker_id',
                    'defender_id',
                    'village_id',
                    'result',
                    'occurred_at',
                ]
            ]);

        $this->assertDatabaseHas('battles', [
            'attacker_id' => $this->attacker->id,
            'defender_id' => $this->defender->id,
            'village_id' => $this->village->id,
            'result' => 'victory',
        ]);
    }

    /** @test */
    public function it_can_show_a_battle()
    {
        $battle = Battle::factory()->create([
            'attacker_id' => $this->attacker->id,
            'defender_id' => $this->defender->id,
            'village_id' => $this->village->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/game/api/battles/{$battle->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'attacker_id',
                    'defender_id',
                    'village_id',
                    'result',
                    'occurred_at',
                    'attacker',
                    'defender',
                    'village',
                ]
            ]);
    }

    /** @test */
    public function it_validates_battle_creation_data()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/game/api/battles', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'attacker_id',
                'defender_id',
                'village_id',
                'attacker_troops',
                'defender_troops',
                'result',
                'occurred_at',
            ]);
    }

    /** @test */
    public function it_can_update_a_battle()
    {
        $battle = Battle::factory()->create([
            'attacker_id' => $this->attacker->id,
            'defender_id' => $this->defender->id,
            'village_id' => $this->village->id,
            'result' => 'victory',
        ]);

        $updateData = [
            'result' => 'draw',
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/game/api/battles/{$battle->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'result' => 'draw',
                ]
            ]);

        $this->assertDatabaseHas('battles', [
            'id' => $battle->id,
            'result' => 'draw',
        ]);
    }

    /** @test */
    public function it_can_delete_a_battle()
    {
        $battle = Battle::factory()->create([
            'attacker_id' => $this->attacker->id,
            'defender_id' => $this->defender->id,
            'village_id' => $this->village->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/game/api/battles/{$battle->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('battles', [
            'id' => $battle->id,
        ]);
    }
}
