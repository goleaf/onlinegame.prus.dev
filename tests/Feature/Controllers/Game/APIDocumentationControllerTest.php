<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class APIDocumentationControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_larautilx_api_documentation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/documentation/larautilx');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'title',
                'version',
                'description',
                'base_url',
                'authentication' => [
                    'type',
                    'description',
                    'example',
                ],
                'endpoints' => [
                    '*' => [
                        'method',
                        'path',
                        'description',
                        'parameters',
                        'responses',
                    ],
                ],
                'schemas' => [
                    '*' => [
                        'name',
                        'type',
                        'properties',
                        'required',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_game_api_documentation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/documentation/game');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'title',
                'version',
                'description',
                'base_url',
                'authentication',
                'endpoints' => [
                    '*' => [
                        'method',
                        'path',
                        'description',
                        'parameters',
                        'responses',
                    ],
                ],
                'game_specific' => [
                    'battle_system',
                    'alliance_system',
                    'village_system',
                    'quest_system',
                    'artifact_system',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_authentication_documentation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/documentation/auth');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'authentication_methods' => [
                    'bearer_token',
                    'api_key',
                    'oauth2',
                ],
                'endpoints' => [
                    'login',
                    'register',
                    'logout',
                    'refresh_token',
                    'forgot_password',
                    'reset_password',
                ],
                'security_considerations',
                'rate_limiting',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_battle_system_documentation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/documentation/battle');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'battle_system' => [
                    'combat_mechanics',
                    'troop_types',
                    'battle_calculations',
                    'war_system',
                ],
                'endpoints' => [
                    'battle_simulation',
                    'troop_movement',
                    'war_declaration',
                    'battle_reports',
                ],
                'examples' => [
                    'attack_calculation',
                    'defense_bonus',
                    'troop_effectiveness',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_alliance_system_documentation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/documentation/alliance');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'alliance_system' => [
                    'alliance_management',
                    'diplomacy',
                    'war_system',
                    'member_management',
                ],
                'endpoints' => [
                    'alliance_creation',
                    'member_invitation',
                    'diplomacy_actions',
                    'war_declaration',
                ],
                'permissions' => [
                    'leader_permissions',
                    'officer_permissions',
                    'member_permissions',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_village_system_documentation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/documentation/village');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'village_system' => [
                    'village_management',
                    'building_system',
                    'resource_production',
                    'coordinate_system',
                ],
                'endpoints' => [
                    'village_creation',
                    'building_construction',
                    'resource_management',
                    'coordinate_validation',
                ],
                'building_types' => [
                    'resource_buildings',
                    'military_buildings',
                    'defense_buildings',
                    'special_buildings',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_quest_system_documentation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/documentation/quest');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'quest_system' => [
                    'quest_types',
                    'quest_progression',
                    'achievement_system',
                    'reward_system',
                ],
                'endpoints' => [
                    'quest_creation',
                    'quest_progress',
                    'achievement_unlock',
                    'reward_claim',
                ],
                'quest_categories' => [
                    'tutorial_quests',
                    'daily_quests',
                    'achievement_quests',
                    'special_quests',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_artifact_system_documentation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/documentation/artifact');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'artifact_system' => [
                    'artifact_types',
                    'artifact_effects',
                    'artifact_discovery',
                    'server_artifacts',
                ],
                'endpoints' => [
                    'artifact_creation',
                    'artifact_activation',
                    'artifact_effects',
                    'artifact_discovery',
                ],
                'artifact_rarity' => [
                    'common',
                    'uncommon',
                    'rare',
                    'epic',
                    'legendary',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_market_system_documentation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/documentation/market');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'market_system' => [
                    'trading_mechanics',
                    'resource_exchange',
                    'market_offers',
                    'price_fluctuation',
                ],
                'endpoints' => [
                    'market_offers',
                    'trade_execution',
                    'price_history',
                    'market_statistics',
                ],
                'trading_rules' => [
                    'offer_creation',
                    'trade_validation',
                    'resource_transfer',
                    'market_fees',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_notification_system_documentation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/documentation/notification');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'notification_system' => [
                    'notification_types',
                    'priority_levels',
                    'delivery_methods',
                    'notification_templates',
                ],
                'endpoints' => [
                    'notification_creation',
                    'notification_delivery',
                    'notification_preferences',
                    'notification_history',
                ],
                'notification_channels' => [
                    'in_game',
                    'email',
                    'push_notifications',
                    'sms',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_chat_system_documentation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/documentation/chat');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'chat_system' => [
                    'chat_channels',
                    'message_types',
                    'moderation_tools',
                    'real_time_communication',
                ],
                'endpoints' => [
                    'message_sending',
                    'channel_management',
                    'message_history',
                    'moderation_actions',
                ],
                'chat_features' => [
                    'global_chat',
                    'alliance_chat',
                    'private_messages',
                    'system_messages',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_ai_system_documentation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/documentation/ai');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'ai_system' => [
                    'ai_models',
                    'content_generation',
                    'quest_creation',
                    'artifact_generation',
                ],
                'endpoints' => [
                    'ai_generation',
                    'quest_ai',
                    'artifact_ai',
                    'battle_ai',
                ],
                'ai_capabilities' => [
                    'text_generation',
                    'content_creation',
                    'game_mechanics',
                    'player_assistance',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_management_documentation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/documentation/system');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'system_management' => [
                    'health_monitoring',
                    'performance_metrics',
                    'maintenance_tools',
                    'security_features',
                ],
                'endpoints' => [
                    'system_status',
                    'health_checks',
                    'maintenance_mode',
                    'security_scan',
                ],
                'admin_features' => [
                    'user_management',
                    'system_configuration',
                    'backup_management',
                    'log_analysis',
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_complete_api_documentation()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/documentation/complete');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'overview',
                'authentication',
                'rate_limiting',
                'error_handling',
                'systems' => [
                    'battle_system',
                    'alliance_system',
                    'village_system',
                    'quest_system',
                    'artifact_system',
                    'market_system',
                    'notification_system',
                    'chat_system',
                    'ai_system',
                    'system_management',
                ],
                'examples',
                'sdk_information',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_api_schemas()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/documentation/schemas');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'schemas' => [
                    '*' => [
                        'name',
                        'type',
                        'properties',
                        'required',
                        'examples',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_api_examples()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/documentation/examples');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'examples' => [
                    '*' => [
                        'endpoint',
                        'method',
                        'request_example',
                        'response_example',
                        'description',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/documentation/larautilx');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_can_get_documentation_by_version()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/documentation/larautilx?version=1.0.0');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'title',
                'version',
                'description',
                'base_url',
                'authentication',
                'endpoints',
                'schemas',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_documentation_by_format()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/documentation/larautilx?format=json');

        $response
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');
    }

    /**
     * @test
     */
    public function it_can_get_documentation_by_language()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/documentation/larautilx?language=en');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'title',
                'version',
                'description',
                'base_url',
                'authentication',
                'endpoints',
                'schemas',
            ]);
    }

    /**
     * @test
     */
    public function it_handles_integration_service_errors()
    {
        $user = User::factory()->create();

        // Mock integration service to return an error
        $this->mock(\App\Services\LarautilxIntegrationService::class, function ($mock): void {
            $mock
                ->shouldReceive('getIntegrationStatus')
                ->andThrow(new \Exception('Integration service unavailable'));
        });

        $response = $this->actingAs($user)->get('/api/game/documentation/larautilx');

        $response
            ->assertStatus(500)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }
}
