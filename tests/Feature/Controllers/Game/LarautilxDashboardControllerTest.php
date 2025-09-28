<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LarautilxDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_dashboard_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'overview' => [
                        'total_users',
                        'active_sessions',
                        'system_health',
                        'performance_metrics',
                    ],
                    'integration_status' => [
                        'larautilx_status',
                        'cache_status',
                        'queue_status',
                        'database_status',
                    ],
                    'performance_metrics' => [
                        'response_times',
                        'memory_usage',
                        'cpu_usage',
                        'database_queries',
                    ],
                    'recent_activity' => [
                        'user_activity',
                        'system_events',
                        'error_logs',
                        'performance_alerts',
                    ],
                    'system_status' => [
                        'overall_health',
                        'component_status',
                        'alerts',
                        'recommendations',
                    ],
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_integration_status()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard/integration-status');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'larautilx_status',
                    'version_info',
                    'features_enabled',
                    'configuration',
                    'performance_metrics',
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

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard/performance');

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
                    'queue_performance',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_health()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard/health');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'overall_health',
                    'components' => [
                        'database',
                        'cache',
                        'queue',
                        'storage',
                        'external_services',
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
    public function it_can_get_user_activity()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard/user-activity');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'active_users',
                    'user_sessions',
                    'login_activity',
                    'user_engagement',
                    'geographic_distribution',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_events()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard/system-events');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'events' => [
                        '*' => [
                            'id',
                            'type',
                            'message',
                            'severity',
                            'timestamp',
                            'context',
                        ],
                    ],
                    'event_summary',
                    'recent_events',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_error_logs()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard/error-logs');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'errors' => [
                        '*' => [
                            'id',
                            'level',
                            'message',
                            'context',
                            'timestamp',
                            'resolved',
                        ],
                    ],
                    'error_summary',
                    'recent_errors',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_performance_alerts()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard/performance-alerts');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'alerts' => [
                        '*' => [
                            'id',
                            'type',
                            'severity',
                            'message',
                            'threshold',
                            'current_value',
                            'timestamp',
                        ],
                    ],
                    'alert_summary',
                    'active_alerts',
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

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard/cache-stats');

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
    public function it_can_get_database_statistics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard/database-stats');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'connection_status',
                    'database_size',
                    'table_count',
                    'slow_queries',
                    'connection_pool',
                    'performance_metrics',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_queue_statistics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard/queue-stats');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'queue_driver',
                    'pending_jobs',
                    'failed_jobs',
                    'processed_jobs',
                    'queue_size',
                    'workers_status',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_storage_statistics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard/storage-stats');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'storage_driver',
                    'total_space',
                    'used_space',
                    'available_space',
                    'file_count',
                    'performance_metrics',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_ai_integration_status()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard/ai-status');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'ai_models',
                    'model_status',
                    'usage_statistics',
                    'performance_metrics',
                    'capabilities',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_configuration_status()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard/config-status');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'configuration',
                    'settings',
                    'environment',
                    'version_info',
                    'feature_toggles',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_scheduler_status()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard/scheduler-status');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'scheduled_tasks',
                    'task_status',
                    'next_run',
                    'task_history',
                    'performance_metrics',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_dashboard_analytics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard/analytics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user_analytics',
                    'system_analytics',
                    'performance_analytics',
                    'usage_patterns',
                    'trends',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_dashboard_recommendations()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard/recommendations');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'performance_recommendations',
                    'security_recommendations',
                    'optimization_suggestions',
                    'maintenance_suggestions',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_dashboard_export()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard/export');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'export_url',
                    'export_format',
                    'export_size',
                    'download_token',
                ],
                'message',
            ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/larautilx/dashboard');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_can_get_dashboard_by_time_range()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard?time_range=24h');

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
    public function it_can_get_dashboard_by_metric_type()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard?metric_type=performance');

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
        $this->mock(\App\Services\LarautilxIntegrationService::class, function ($mock): void {
            $mock
                ->shouldReceive('getIntegrationStatus')
                ->andThrow(new \Exception('Integration service unavailable'));
        });

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard');

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
    public function it_handles_ai_service_errors()
    {
        $user = User::factory()->create();

        // Mock AI service to return an error
        $this->mock(\App\Services\AIService::class, function ($mock): void {
            $mock
                ->shouldReceive('getStatus')
                ->andThrow(new \Exception('AI service unavailable'));
        });

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard/ai-status');

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
    public function it_can_get_dashboard_widgets()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/larautilx/dashboard/widgets');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'widgets' => [
                        '*' => [
                            'id',
                            'type',
                            'title',
                            'data',
                            'position',
                            'size',
                        ],
                    ],
                ],
                'message',
            ]);
    }
}
