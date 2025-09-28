<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameApiControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_game_status()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/status');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'version',
                'server_time',
                'maintenance_mode',
                'features',
                'limits',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_player_profile()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/api/game/player/profile');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'player' => [
                    'id',
                    'name',
                    'tribe',
                    'world',
                    'alliance',
                    'statistics',
                    'achievements',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_village_list()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        Village::factory()->count(3)->create(['player_id' => $player->id]);

        $response = $this->actingAs($user)->get('/api/game/villages');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'villages' => [
                    '*' => [
                        'id',
                        'name',
                        'coordinates',
                        'population',
                        'culture_points',
                        'buildings',
                        'resources',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_village_details()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $village = Village::factory()->create(['player_id' => $player->id]);

        $response = $this->actingAs($user)->get("/api/game/villages/{$village->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'village' => [
                    'id',
                    'name',
                    'coordinates',
                    'population',
                    'culture_points',
                    'buildings',
                    'resources',
                    'troops',
                    'defense',
                ],
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
            'name' => 'New Village',
            'x_coordinate' => 100,
            'y_coordinate' => 200,
            'world_id' => 1,
        ];

        $response = $this->actingAs($user)->post('/api/game/villages', $villageData);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'village' => [
                    'id',
                    'name',
                    'coordinates',
                    'population',
                    'culture_points',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('villages', [
            'name' => 'New Village',
            'x_coordinate' => 100,
            'y_coordinate' => 200,
            'player_id' => $player->id,
        ]);
    }

    /**
     * @test
     */
    public function it_can_upgrade_building()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $village = Village::factory()->create(['player_id' => $player->id]);

        $upgradeData = [
            'village_id' => $village->id,
            'building_type' => 'barracks',
            'level' => 2,
        ];

        $response = $this->actingAs($user)->post('/api/game/buildings/upgrade', $upgradeData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'building' => [
                    'id',
                    'building_type',
                    'level',
                    'upgrade_time',
                    'cost',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_train_troops()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $village = Village::factory()->create(['player_id' => $player->id]);

        $trainingData = [
            'village_id' => $village->id,
            'troop_type' => 'legionnaires',
            'quantity' => 10,
        ];

        $response = $this->actingAs($user)->post('/api/game/troops/train', $trainingData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'training_queue' => [
                    'id',
                    'troop_type',
                    'quantity',
                    'training_time',
                    'cost',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_send_troops()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $village = Village::factory()->create(['player_id' => $player->id]);
        $targetVillage = Village::factory()->create();

        $movementData = [
            'from_village_id' => $village->id,
            'to_village_id' => $targetVillage->id,
            'troops' => ['legionnaires' => 5],
            'movement_type' => 'attack',
        ];

        $response = $this->actingAs($user)->post('/api/game/movements/send', $movementData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'movement' => [
                    'id',
                    'from_village_id',
                    'to_village_id',
                    'troops',
                    'arrival_time',
                    'movement_type',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_game_statistics()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/api/game/statistics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'player_stats' => [
                    'total_villages',
                    'total_population',
                    'total_culture_points',
                    'battles_won',
                    'battles_lost',
                ],
                'world_stats' => [
                    'total_players',
                    'total_villages',
                    'active_alliances',
                ],
                'server_stats' => [
                    'uptime',
                    'online_players',
                    'total_registrations',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_leaderboard()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/leaderboard');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'leaderboard' => [
                    'players' => [
                        '*' => [
                            'rank',
                            'player_name',
                            'tribe',
                            'points',
                            'villages',
                        ],
                    ],
                    'alliances' => [
                        '*' => [
                            'rank',
                            'alliance_name',
                            'points',
                            'members',
                        ],
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_world_map()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/world-map');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'map_data' => [
                    'villages' => [
                        '*' => [
                            'id',
                            'coordinates',
                            'player_name',
                            'alliance_tag',
                            'population',
                        ],
                    ],
                    'alliances' => [
                        '*' => [
                            'id',
                            'name',
                            'tag',
                            'color',
                            'members',
                        ],
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_info()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/api/game/alliance');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'alliance' => [
                    'id',
                    'name',
                    'tag',
                    'members',
                    'rank',
                    'points',
                    'wars',
                    'diplomacy',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_battle_reports()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/api/game/battles/reports');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'reports' => [
                    '*' => [
                        'id',
                        'battle_type',
                        'result',
                        'attacker',
                        'defender',
                        'casualties',
                        'loot',
                        'battle_time',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_market_offers()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/market/offers');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'offers' => [
                    '*' => [
                        'id',
                        'offer_type',
                        'resource_type',
                        'amount',
                        'price_per_unit',
                        'seller',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_quests()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/api/game/quests');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'quests' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'type',
                        'difficulty',
                        'rewards',
                        'status',
                        'progress',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_achievements()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/api/game/achievements');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'achievements' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'type',
                        'progress',
                        'completed',
                        'rewards',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_artifacts()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/artifacts');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'artifacts' => [
                    '*' => [
                        'id',
                        'name',
                        'type',
                        'rarity',
                        'effects',
                        'requirements',
                        'discovered',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_notifications()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/api/game/notifications');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'notifications' => [
                    '*' => [
                        'id',
                        'type',
                        'title',
                        'message',
                        'priority',
                        'is_read',
                        'created_at',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/status');

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
            ->assertJsonValidationErrors(['name', 'x_coordinate', 'y_coordinate', 'world_id']);
    }

    /**
     * @test
     */
    public function it_validates_coordinates_range()
    {
        $user = User::factory()->create();

        $villageData = [
            'name' => 'Test Village',
            'x_coordinate' => 1000,  // Invalid: exceeds max 999
            'y_coordinate' => -1,  // Invalid: below min 0
            'world_id' => 1,
        ];

        $response = $this->actingAs($user)->post('/api/game/villages', $villageData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['x_coordinate', 'y_coordinate']);
    }

    /**
     * @test
     */
    public function it_validates_building_upgrade_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/buildings/upgrade', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['village_id', 'building_type', 'level']);
    }

    /**
     * @test
     */
    public function it_validates_troop_training_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/troops/train', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['village_id', 'troop_type', 'quantity']);
    }

    /**
     * @test
     */
    public function it_validates_movement_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/movements/send', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['from_village_id', 'to_village_id', 'troops', 'movement_type']);
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

    /**
     * @test
     */
    public function it_handles_game_service_errors()
    {
        $user = User::factory()->create();

        // Mock game service to return an error
        $this->mock(\App\Services\GameIntegrationService::class, function ($mock): void {
            $mock
                ->shouldReceive('getGameStatus')
                ->andThrow(new \Exception('Game service unavailable'));
        });

        $response = $this->actingAs($user)->get('/api/game/status');

        $response
            ->assertStatus(500)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }
}
