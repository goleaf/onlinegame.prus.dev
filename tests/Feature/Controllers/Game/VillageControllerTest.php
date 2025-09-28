<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VillageControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_all_villages()
    {
        $user = User::factory()->create();
        Village::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/villages');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'player_id',
                        'world_id',
                        'x_coordinate',
                        'y_coordinate',
                        'population',
                        'culture_points',
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
    public function it_can_get_specific_village()
    {
        $user = User::factory()->create();
        $village = Village::factory()->create();

        $response = $this->actingAs($user)->get("/api/game/villages/{$village->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'player_id',
                'world_id',
                'x_coordinate',
                'y_coordinate',
                'population',
                'culture_points',
                'player',
                'world',
                'buildings',
                'troops',
                'resources',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * @test
     */
    public function it_can_create_village()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $villageData = [
            'name' => 'Test Village',
            'player_id' => $player->id,
            'world_id' => 1,
            'x_coordinate' => 100,
            'y_coordinate' => 200,
            'population' => 1000,
            'culture_points' => 500,
        ];

        $response = $this->actingAs($user)->post('/api/game/villages', $villageData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'village' => [
                    'id',
                    'name',
                    'player_id',
                    'world_id',
                    'x_coordinate',
                    'y_coordinate',
                    'population',
                    'culture_points',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('villages', [
            'name' => 'Test Village',
            'player_id' => $player->id,
            'x_coordinate' => 100,
            'y_coordinate' => 200,
        ]);
    }

    /**
     * @test
     */
    public function it_can_update_village()
    {
        $user = User::factory()->create();
        $village = Village::factory()->create();

        $updateData = [
            'name' => 'Updated Village Name',
            'population' => 1500,
            'culture_points' => 750,
        ];

        $response = $this->actingAs($user)->put("/api/game/villages/{$village->id}", $updateData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'village' => [
                    'id',
                    'name',
                    'population',
                    'culture_points',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('villages', [
            'id' => $village->id,
            'name' => 'Updated Village Name',
            'population' => 1500,
        ]);
    }

    /**
     * @test
     */
    public function it_can_delete_village()
    {
        $user = User::factory()->create();
        $village = Village::factory()->create();

        $response = $this->actingAs($user)->delete("/api/game/villages/{$village->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $this->assertDatabaseMissing('villages', ['id' => $village->id]);
    }

    /**
     * @test
     */
    public function it_can_get_villages_with_stats()
    {
        $user = User::factory()->create();
        Village::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/api/game/villages/with-stats');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'player_id',
                        'world_id',
                        'x_coordinate',
                        'y_coordinate',
                        'population',
                        'culture_points',
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
    public function it_can_filter_villages_by_player()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create();
        Village::factory()->count(2)->create(['player_id' => $player->id]);
        Village::factory()->count(1)->create(['player_id' => Player::factory()->create()->id]);

        $response = $this->actingAs($user)->get("/api/game/villages?player_id={$player->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_filter_villages_by_world()
    {
        $user = User::factory()->create();
        Village::factory()->count(2)->create(['world_id' => 1]);
        Village::factory()->count(1)->create(['world_id' => 2]);

        $response = $this->actingAs($user)->get('/api/game/villages?world_id=1');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_search_villages_by_name()
    {
        $user = User::factory()->create();
        Village::factory()->create(['name' => 'Capital City']);
        Village::factory()->create(['name' => 'Small Town']);
        Village::factory()->create(['name' => 'Capital Village']);

        $response = $this->actingAs($user)->get('/api/game/villages?search=Capital');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_can_get_villages_by_coordinates()
    {
        $user = User::factory()->create();
        Village::factory()->create(['x_coordinate' => 100, 'y_coordinate' => 200]);
        Village::factory()->create(['x_coordinate' => 150, 'y_coordinate' => 250]);
        Village::factory()->create(['x_coordinate' => 300, 'y_coordinate' => 400]);

        $response = $this->actingAs($user)->get('/api/game/villages?x_min=50&x_max=200&y_min=150&y_max=300');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(2, $data);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/villages');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_validates_village_creation_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/villages', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'player_id', 'world_id', 'x_coordinate', 'y_coordinate']);
    }

    /**
     * @test
     */
    public function it_validates_coordinate_ranges()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $villageData = [
            'name' => 'Test Village',
            'player_id' => $player->id,
            'world_id' => 1,
            'x_coordinate' => 1000,  // Invalid: exceeds max 999
            'y_coordinate' => -1,  // Invalid: below min 0
        ];

        $response = $this->actingAs($user)->post('/api/game/villages', $villageData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['x_coordinate', 'y_coordinate']);
    }

    /**
     * @test
     */
    public function it_returns_404_for_nonexistent_village()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/villages/999');

        $response->assertStatus(404);
    }
}
