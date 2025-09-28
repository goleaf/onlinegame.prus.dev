<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecureGameControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_access_secure_dashboard()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/game/dashboard');

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_redirects_unauthenticated_users_to_login()
    {
        $response = $this->get('/game/dashboard');

        $response->assertRedirect('/login');
    }

    /**
     * @test
     */
    public function it_shows_no_player_view_when_user_has_no_player()
    {
        $user = User::factory()->create();
        // No player created for this user

        $response = $this->actingAs($user)->get('/game/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('game.no-player');
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

        $response = $this->actingAs($user)->post('/game/buildings/upgrade', $upgradeData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'building' => [
                    'id',
                    'building_type',
                    'level',
                    'upgrade_time',
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

        $response = $this->actingAs($user)->post('/game/troops/train', $trainingData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'training_queue' => [
                    'id',
                    'troop_type',
                    'quantity',
                    'training_time',
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

        $response = $this->actingAs($user)->post('/game/movements/send', $movementData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'movement' => [
                    'id',
                    'from_village_id',
                    'to_village_id',
                    'troops',
                    'arrival_time',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_manage_resources()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $village = Village::factory()->create(['player_id' => $player->id]);

        $resourceData = [
            'village_id' => $village->id,
            'resource_type' => 'wood',
            'amount' => 1000,
        ];

        $response = $this->actingAs($user)->post('/game/resources/manage', $resourceData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'resources' => [
                    'wood',
                    'clay',
                    'iron',
                    'crop',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_security_status()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/game/security/status');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'security_level',
                'threats_detected',
                'protection_status',
                'recommendations',
            ]);
    }

    /**
     * @test
     */
    public function it_can_report_security_incident()
    {
        $user = User::factory()->create();

        $incidentData = [
            'incident_type' => 'suspicious_activity',
            'description' => 'Unusual login attempts detected',
            'severity' => 'medium',
        ];

        $response = $this->actingAs($user)->post('/game/security/report', $incidentData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'incident_id',
                'status',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_player_statistics()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/game/player/statistics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'player_stats',
                'village_stats',
                'battle_stats',
                'resource_stats',
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

        $response = $this->actingAs($user)->get("/game/villages/{$village->id}");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'village' => [
                    'id',
                    'name',
                    'coordinates',
                    'buildings',
                    'resources',
                    'troops',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_information()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/game/alliance/info');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'alliance' => [
                    'id',
                    'name',
                    'tag',
                    'members',
                    'rank',
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

        $response = $this->actingAs($user)->get('/game/battles/reports');

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
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/game/market/offers');

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

        $response = $this->actingAs($user)->get('/game/quests');

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

        $response = $this->actingAs($user)->get('/game/achievements');

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
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/game/artifacts');

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
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_notifications()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/game/notifications');

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
    public function it_requires_authentication_for_all_endpoints()
    {
        $endpoints = [
            '/game/dashboard',
            '/game/buildings/upgrade',
            '/game/troops/train',
            '/game/movements/send',
            '/game/resources/manage',
            '/game/security/status',
            '/game/player/statistics',
            '/game/villages/1',
            '/game/alliance/info',
            '/game/battles/reports',
            '/game/market/offers',
            '/game/quests',
            '/game/achievements',
            '/game/artifacts',
            '/game/notifications',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->get($endpoint);
            $response->assertStatus(401);
        }
    }

    /**
     * @test
     */
    public function it_validates_building_upgrade_request()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/game/buildings/upgrade', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['village_id', 'building_type', 'level']);
    }

    /**
     * @test
     */
    public function it_validates_troop_training_request()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/game/troops/train', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['village_id', 'troop_type', 'quantity']);
    }

    /**
     * @test
     */
    public function it_validates_movement_request()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/game/movements/send', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['from_village_id', 'to_village_id', 'troops', 'movement_type']);
    }

    /**
     * @test
     */
    public function it_validates_security_incident_report()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/game/security/report', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['incident_type', 'description', 'severity']);
    }

    /**
     * @test
     */
    public function it_handles_security_service_errors()
    {
        $user = User::factory()->create();

        // Mock security service to return an error
        $this->mock(\App\Services\GameSecurityService::class, function ($mock): void {
            $mock
                ->shouldReceive('getSecurityStatus')
                ->andThrow(new \Exception('Security service unavailable'));
        });

        $response = $this->actingAs($user)->get('/game/security/status');

        $response->assertStatus(500);
    }
}
