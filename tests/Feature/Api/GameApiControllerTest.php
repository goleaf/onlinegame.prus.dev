<?php

namespace Tests\Feature\Api;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GameApiControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;

    protected Player $player;

    protected Village $village;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id]);
        $this->village = Village::factory()->create(['player_id' => $this->player->id]);
    }

    public function test_can_create_village()
    {
        $response = $this
            ->actingAs($this->user, 'sanctum')
            ->postJson('/api/game/create-village', [
                'name' => 'Test Village',
                'x' => 100,
                'y' => 200,
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'village' => [
                        'id',
                        'name',
                        'x_coordinate',
                        'y_coordinate',
                        'population',
                    ],
                ],
            ]);
    }

    public function test_can_upgrade_building()
    {
        $response = $this
            ->actingAs($this->user, 'sanctum')
            ->postJson("/api/game/village/{$this->village->id}/upgrade-building", [
                'building_type' => 'wood',
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'village' => [
                        'id',
                        'population',
                    ],
                ],
            ]);
    }

    public function test_can_get_village_details()
    {
        $response = $this
            ->actingAs($this->user, 'sanctum')
            ->getJson("/api/game/village/{$this->village->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'x_coordinate',
                    'y_coordinate',
                ],
            ]);
    }

    public function test_can_get_player_stats()
    {
        $response = $this
            ->actingAs($this->user, 'sanctum')
            ->getJson('/api/game/player/stats');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'player' => [
                        'id',
                        'name',
                        'points',
                    ],
                    'villages' => [],
                ],
            ]);
    }

    public function test_can_get_player_villages()
    {
        $response = $this
            ->actingAs($this->user, 'sanctum')
            ->getJson('/api/game/player/villages');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [],
            ]);
    }

    public function test_can_calculate_distance_between_villages()
    {
        $village2 = Village::factory()->create(['player_id' => $this->player->id]);

        $response = $this
            ->actingAs($this->user, 'sanctum')
            ->postJson('/api/game/calculate-distance', [
                'village1_id' => $this->village->id,
                'village2_id' => $village2->id,
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'distance_km',
                    'bearing',
                    'travel_time_minutes',
                ],
            ]);
    }

    public function test_validation_errors_for_create_village()
    {
        $response = $this
            ->actingAs($this->user, 'sanctum')
            ->postJson('/api/game/create-village', [
                'name' => '',
                'x' => 'invalid',
                'y' => 'invalid',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    public function test_validation_errors_for_upgrade_building()
    {
        $response = $this
            ->actingAs($this->user, 'sanctum')
            ->postJson("/api/game/village/{$this->village->id}/upgrade-building", [
                'building_type' => 'invalid_type',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    public function test_unauthorized_access_to_other_player_village()
    {
        $otherPlayer = Player::factory()->create();
        $otherVillage = Village::factory()->create(['player_id' => $otherPlayer->id]);

        $response = $this
            ->actingAs($this->user, 'sanctum')
            ->getJson("/api/game/village/{$otherVillage->id}");

        $response
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized',
            ]);
    }

    public function test_village_not_found()
    {
        $response = $this
            ->actingAs($this->user, 'sanctum')
            ->getJson('/api/game/village/99999');

        $response
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Village not found',
            ]);
    }
}
