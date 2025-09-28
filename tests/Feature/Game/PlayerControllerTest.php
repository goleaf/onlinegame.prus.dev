<?php

namespace Tests\Feature\Game;

use App\Models\Game\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Player $player;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->player = Player::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_can_get_players()
    {
        Player::factory()->count(3)->create();

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/players');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [],
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_can_filter_players_by_online_status()
    {
        Player::factory()->create(['is_online' => true]);
        Player::factory()->create(['is_online' => false]);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/players?is_online=true');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertTrue($data[0]['is_online']);
    }

    public function test_can_filter_players_by_active_status()
    {
        Player::factory()->create(['is_active' => true]);
        Player::factory()->create(['is_active' => false]);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/players?is_active=true');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertTrue($data[0]['is_active']);
    }

    public function test_can_filter_players_by_points_range()
    {
        Player::factory()->create(['points' => 1000]);
        Player::factory()->create(['points' => 5000]);
        Player::factory()->create(['points' => 10000]);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/players?min_points=2000&max_points=8000');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals(5000, $data[0]['points']);
    }

    public function test_can_search_players_by_name()
    {
        Player::factory()->create(['name' => 'John Doe']);
        Player::factory()->create(['name' => 'Jane Smith']);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/players?search=John');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('John', $data[0]['name']);
    }

    public function test_can_get_player_details()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson("/game/players/{$this->player->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'points',
                    'is_online',
                    'is_active',
                    'villages_count',
                    'population',
                ],
            ]);
    }

    public function test_can_update_player_status()
    {
        $response = $this
            ->actingAs($this->user)
            ->putJson("/game/players/{$this->player->id}/status", [
                'is_active' => false,
                'is_online' => true,
                'last_active_at' => now()->toISOString(),
            ]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Player status updated successfully.',
            ]);

        $this->assertFalse($this->player->fresh()->is_active);
        $this->assertTrue($this->player->fresh()->is_online);
    }

    public function test_validation_errors_for_update_status()
    {
        $response = $this
            ->actingAs($this->user)
            ->putJson("/game/players/{$this->player->id}/status", [
                'is_active' => 'invalid_boolean',
                'is_online' => 'invalid_boolean',
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    public function test_can_get_online_players()
    {
        Player::factory()->create(['is_online' => true]);
        Player::factory()->create(['is_online' => false]);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/players?is_online=true');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertTrue($data[0]['is_online']);
    }

    public function test_can_sort_players_by_points()
    {
        Player::factory()->create(['points' => 1000]);
        Player::factory()->create(['points' => 5000]);
        Player::factory()->create(['points' => 3000]);

        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/players?sort_by=points&sort_direction=desc');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertEquals(5000, $data[0]['points']);
        $this->assertEquals(3000, $data[1]['points']);
        $this->assertEquals(1000, $data[2]['points']);
    }

    public function test_requires_authentication()
    {
        $response = $this->getJson('/game/players');
        $response->assertStatus(401);

        $response = $this->getJson("/game/players/{$this->player->id}");
        $response->assertStatus(401);
    }

    public function test_player_not_found()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/players/99999');

        $response->assertStatus(404);
    }
}
