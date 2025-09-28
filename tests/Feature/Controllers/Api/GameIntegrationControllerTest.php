<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameIntegrationControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_player_dashboard_data()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        Village::factory()->count(3)->create(['player_id' => $player->id]);

        $response = $this->actingAs($user)->get('/api/game-integration/dashboard');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'player' => [
                        'id',
                        'name',
                        'tribe',
                        'world',
                        'alliance',
                        'statistics',
                    ],
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
                    'alliance' => [
                        'id',
                        'name',
                        'tag',
                        'members',
                        'rank',
                        'points',
                    ],
                    'notifications' => [
                        '*' => [
                            'id',
                            'type',
                            'title',
                            'message',
                            'priority',
                            'is_read',
                        ],
                    ],
                    'recent_activity' => [
                        '*' => [
                            'id',
                            'type',
                            'description',
                            'timestamp',
                        ],
                    ],
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_village_overview()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);
        $village = Village::factory()->create(['player_id' => $player->id]);

        $response = $this->actingAs($user)->get("/api/game-integration/villages/{$village->id}/overview");

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
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
                    'building_queues' => [
                        '*' => [
                            'id',
                            'building_type',
                            'level',
                            'completion_time',
                            'cost',
                        ],
                    ],
                    'training_queues' => [
                        '*' => [
                            'id',
                            'troop_type',
                            'quantity',
                            'completion_time',
                            'cost',
                        ],
                    ],
                    'recent_activity' => [
                        '*' => [
                            'id',
                            'type',
                            'description',
                            'timestamp',
                        ],
                    ],
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_overview()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/api/game-integration/alliance/overview');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
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
                    'members' => [
                        '*' => [
                            'id',
                            'name',
                            'tribe',
                            'points',
                            'villages',
                            'role',
                        ],
                    ],
                    'recent_activity' => [
                        '*' => [
                            'id',
                            'type',
                            'description',
                            'timestamp',
                        ],
                    ],
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_market_overview()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game-integration/market/overview');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'market_offers' => [
                        '*' => [
                            'id',
                            'offer_type',
                            'resource_type',
                            'amount',
                            'price_per_unit',
                            'seller',
                        ],
                    ],
                    'market_statistics' => [
                        'total_offers',
                        'average_prices',
                        'trade_volume',
                        'active_traders',
                    ],
                    'recent_trades' => [
                        '*' => [
                            'id',
                            'buyer',
                            'seller',
                            'resource_type',
                            'amount',
                            'price',
                            'timestamp',
                        ],
                    ],
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_quest_overview()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/api/game-integration/quests/overview');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'active_quests' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'type',
                            'difficulty',
                            'progress',
                            'rewards',
                        ],
                    ],
                    'completed_quests' => [
                        '*' => [
                            'id',
                            'title',
                            'type',
                            'completed_at',
                            'rewards_received',
                        ],
                    ],
                    'available_quests' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'type',
                            'difficulty',
                            'requirements',
                            'rewards',
                        ],
                    ],
                    'quest_statistics' => [
                        'total_completed',
                        'total_active',
                        'total_available',
                        'completion_rate',
                    ],
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_battle_overview()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/api/game-integration/battles/overview');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'recent_battles' => [
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
                    'battle_statistics' => [
                        'total_battles',
                        'battles_won',
                        'battles_lost',
                        'total_casualties',
                        'total_loot',
                    ],
                    'troop_statistics' => [
                        'total_troops',
                        'troops_in_battle',
                        'troops_available',
                        'troop_types',
                    ],
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_artifact_overview()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game-integration/artifacts/overview');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'discovered_artifacts' => [
                        '*' => [
                            'id',
                            'name',
                            'type',
                            'rarity',
                            'effects',
                            'discovered_at',
                        ],
                    ],
                    'available_artifacts' => [
                        '*' => [
                            'id',
                            'name',
                            'type',
                            'rarity',
                            'effects',
                            'requirements',
                        ],
                    ],
                    'artifact_statistics' => [
                        'total_discovered',
                        'total_available',
                        'discovery_rate',
                        'rarity_distribution',
                    ],
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_notification_overview()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/api/game-integration/notifications/overview');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'unread_notifications' => [
                        '*' => [
                            'id',
                            'type',
                            'title',
                            'message',
                            'priority',
                            'created_at',
                        ],
                    ],
                    'recent_notifications' => [
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
                    'notification_statistics' => [
                        'total_notifications',
                        'unread_count',
                        'read_count',
                        'notification_types',
                    ],
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_status()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game-integration/system/status');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'system_health' => [
                        'overall_status',
                        'database_status',
                        'cache_status',
                        'queue_status',
                        'storage_status',
                    ],
                    'performance_metrics' => [
                        'response_time',
                        'memory_usage',
                        'cpu_usage',
                        'database_queries',
                    ],
                    'maintenance_info' => [
                        'maintenance_mode',
                        'scheduled_maintenance',
                        'last_maintenance',
                    ],
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_integration_analytics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game-integration/analytics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_analytics' => [
                        'login_frequency',
                        'session_duration',
                        'feature_usage',
                        'activity_patterns',
                    ],
                    'game_analytics' => [
                        'player_statistics',
                        'village_growth',
                        'alliance_activity',
                        'battle_frequency',
                    ],
                    'system_analytics' => [
                        'performance_metrics',
                        'error_rates',
                        'usage_statistics',
                        'optimization_opportunities',
                    ],
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_cross_system_data()
    {
        $user = User::factory()->create();
        $player = Player::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/api/game-integration/cross-system');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'player_data' => [
                        'profile',
                        'statistics',
                        'achievements',
                        'progress',
                    ],
                    'village_data' => [
                        'villages',
                        'buildings',
                        'resources',
                        'troops',
                    ],
                    'alliance_data' => [
                        'alliance_info',
                        'members',
                        'wars',
                        'diplomacy',
                    ],
                    'market_data' => [
                        'offers',
                        'trades',
                        'statistics',
                    ],
                    'quest_data' => [
                        'active_quests',
                        'completed_quests',
                        'available_quests',
                    ],
                    'battle_data' => [
                        'recent_battles',
                        'troop_statistics',
                        'battle_history',
                    ],
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_integration_health()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game-integration/health');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'integration_status' => [
                        'overall_health',
                        'component_status',
                        'last_check',
                        'uptime',
                    ],
                    'service_status' => [
                        'database',
                        'cache',
                        'queue',
                        'storage',
                        'external_services',
                    ],
                    'performance_metrics' => [
                        'response_times',
                        'error_rates',
                        'throughput',
                        'resource_usage',
                    ],
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_integration_logs()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game-integration/logs');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'recent_logs' => [
                        '*' => [
                            'id',
                            'level',
                            'message',
                            'context',
                            'timestamp',
                        ],
                    ],
                    'log_statistics' => [
                        'total_logs',
                        'error_count',
                        'warning_count',
                        'info_count',
                    ],
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game-integration/dashboard');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_can_get_integration_by_time_range()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game-integration/dashboard?time_range=24h');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_integration_by_scope()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game-integration/dashboard?scope=player');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_handles_integration_service_errors()
    {
        $user = User::factory()->create();

        // Mock integration service to return an error
        $this->mock(\App\Services\GameIntegrationService::class, function ($mock): void {
            $mock
                ->shouldReceive('getDashboardData')
                ->andThrow(new \Exception('Integration service unavailable'));
        });

        $response = $this->actingAs($user)->get('/api/game-integration/dashboard');

        $response
            ->assertStatus(500)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_integration_metrics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game-integration/metrics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'integration_metrics' => [
                        'total_requests',
                        'successful_requests',
                        'failed_requests',
                        'average_response_time',
                    ],
                    'component_metrics' => [
                        'database_queries',
                        'cache_hits',
                        'queue_jobs',
                        'storage_operations',
                    ],
                ],
                'message',
            ]);
    }
}
