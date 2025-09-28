<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LarautilxControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_larautilx_status()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/status');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'integration_status',
                    'version',
                    'features',
                    'performance_metrics',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_cache_statistics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/cache/stats');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'cache_driver',
                    'hit_rate',
                    'miss_rate',
                    'memory_usage',
                    'keys_count',
                    'performance_metrics',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_clear_cache_by_tags()
    {
        $user = User::factory()->create();

        $cacheData = [
            'tags' => ['users', 'players', 'villages'],
        ];

        $response = $this->actingAs($user)->post('/api/game/larautilx/cache/clear', $cacheData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'cleared_tags',
                    'keys_cleared',
                    'cache_size_before',
                    'cache_size_after',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_integration_features()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/features');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'available_features',
                    'enabled_features',
                    'feature_status',
                    'configuration',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_performance_metrics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/performance');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'response_times',
                    'memory_usage',
                    'cpu_usage',
                    'database_queries',
                    'cache_performance',
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

        $response = $this->actingAs($user)->get('/api/game/larautilx/logs');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'logs' => [
                        '*' => [
                            'id',
                            'level',
                            'message',
                            'context',
                            'created_at',
                        ],
                    ],
                    'meta' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page',
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

        $response = $this->actingAs($user)->get('/api/game/larautilx/health');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'overall_health',
                    'components' => [
                        'cache',
                        'database',
                        'queue',
                        'storage',
                    ],
                    'alerts',
                    'recommendations',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_integration_configuration()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/config');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'configuration',
                    'settings',
                    'environment',
                    'version_info',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_update_integration_settings()
    {
        $user = User::factory()->create();

        $settingsData = [
            'cache_ttl' => 3600,
            'performance_mode' => 'optimized',
            'debug_mode' => false,
        ];

        $response = $this->actingAs($user)->put('/api/game/larautilx/config', $settingsData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'updated_settings',
                    'configuration',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_integration_statistics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/statistics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'usage_statistics',
                    'performance_metrics',
                    'error_rates',
                    'integration_activity',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_test_integration_connection()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/larautilx/test-connection');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'connection_status',
                    'response_time',
                    'test_results',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_sync_integration_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/larautilx/sync');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'sync_status',
                    'items_synced',
                    'sync_duration',
                    'last_sync',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_integration_errors()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/errors');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'errors' => [
                        '*' => [
                            'id',
                            'error_type',
                            'message',
                            'context',
                            'resolved',
                            'created_at',
                        ],
                    ],
                    'error_summary',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_resolve_integration_errors()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/larautilx/errors/resolve');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'resolved_errors',
                    'resolution_status',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/larautilx/status');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_validates_cache_clear_request()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/larautilx/cache/clear', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tags']);
    }

    /**
     * @test
     */
    public function it_validates_tags_array()
    {
        $user = User::factory()->create();

        $cacheData = [
            'tags' => 'invalid_tags',  // Should be array
        ];

        $response = $this->actingAs($user)->post('/api/game/larautilx/cache/clear', $cacheData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tags']);
    }

    /**
     * @test
     */
    public function it_validates_tag_strings()
    {
        $user = User::factory()->create();

        $cacheData = [
            'tags' => [123, 456],  // Should be strings
        ];

        $response = $this->actingAs($user)->post('/api/game/larautilx/cache/clear', $cacheData);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tags.0', 'tags.1']);
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

        $response = $this->actingAs($user)->get('/api/game/larautilx/status');

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
    public function it_can_get_integration_dashboard_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'overview',
                    'performance_metrics',
                    'recent_activity',
                    'system_status',
                ],
                'message',
            ]);
    }
}
