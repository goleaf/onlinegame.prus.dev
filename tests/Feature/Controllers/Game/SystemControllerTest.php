<?php

namespace Tests\Feature\Controllers\Game;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_system_configuration()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/system/config');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'game_settings',
                'server_info',
                'maintenance_mode',
                'version',
                'features',
                'limits',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_status()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/system/status');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'uptime',
                'memory_usage',
                'cpu_usage',
                'database_status',
                'cache_status',
                'queue_status',
                'last_updated',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_health()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/system/health');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
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
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_metrics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/system/metrics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'performance_metrics',
                'resource_usage',
                'response_times',
                'error_rates',
                'user_activity',
                'game_statistics',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_logs()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/system/logs');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
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
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_logs_by_level()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/system/logs?level=error');

        $response->assertStatus(200);
        $data = $response->json('data');
        // All logs should be error level
        foreach ($data as $log) {
            $this->assertEquals('error', $log['level']);
        }
    }

    /**
     * @test
     */
    public function it_can_get_system_cache_info()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/system/cache');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'cache_driver',
                'cache_size',
                'hit_rate',
                'miss_rate',
                'keys_count',
                'memory_usage',
                'performance',
            ]);
    }

    /**
     * @test
     */
    public function it_can_clear_system_cache()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/system/cache/clear');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'cleared_keys',
                'cache_size_before',
                'cache_size_after',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_queue_info()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/system/queue');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'queue_driver',
                'pending_jobs',
                'failed_jobs',
                'processed_jobs',
                'queue_size',
                'workers_status',
            ]);
    }

    /**
     * @test
     */
    public function it_can_restart_system_queue()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/system/queue/restart');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'queue_status',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_database_info()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/system/database');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'connection_status',
                'database_size',
                'table_count',
                'slow_queries',
                'connection_pool',
                'performance_metrics',
            ]);
    }

    /**
     * @test
     */
    public function it_can_optimize_system_database()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/system/database/optimize');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'optimization_results',
                'performance_improvement',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_backup_info()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/system/backup');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'backup_status',
                'last_backup',
                'backup_size',
                'backup_location',
                'retention_policy',
                'next_backup',
            ]);
    }

    /**
     * @test
     */
    public function it_can_create_system_backup()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/system/backup/create');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'backup_id',
                'backup_size',
                'estimated_time',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_maintenance_info()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/system/maintenance');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'maintenance_mode',
                'maintenance_message',
                'scheduled_maintenance',
                'maintenance_history',
            ]);
    }

    /**
     * @test
     */
    public function it_can_enable_maintenance_mode()
    {
        $user = User::factory()->create();

        $maintenanceData = [
            'message' => 'System maintenance in progress',
            'estimated_duration' => 60,
        ];

        $response = $this->actingAs($user)->post('/api/game/system/maintenance/enable', $maintenanceData);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'maintenance_mode',
                'maintenance_message',
            ]);
    }

    /**
     * @test
     */
    public function it_can_disable_maintenance_mode()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/system/maintenance/disable');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'maintenance_mode',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_security_info()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/system/security');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'security_status',
                'threat_level',
                'recent_attacks',
                'security_measures',
                'recommendations',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_performance_info()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/system/performance');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'overall_performance',
                'response_times',
                'throughput',
                'error_rates',
                'resource_usage',
                'bottlenecks',
                'recommendations',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_updates_info()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/system/updates');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'current_version',
                'available_updates',
                'update_status',
                'last_update',
                'update_history',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_integrations_info()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/system/integrations');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'integrations' => [
                    '*' => [
                        'name',
                        'status',
                        'version',
                        'configuration',
                        'last_sync',
                    ],
                ],
            ]);
    }

    /**
     * @test
     */
    public function it_requires_authentication()
    {
        $response = $this->get('/api/game/system/config');

        $response->assertStatus(401);
    }

    /**
     * @test
     */
    public function it_requires_admin_privileges_for_sensitive_operations()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/system/cache/clear');

        // This should require admin privileges
        $response->assertStatus(403);
    }

    /**
     * @test
     */
    public function it_validates_maintenance_mode_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/game/system/maintenance/enable', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /**
     * @test
     */
    public function it_can_get_system_analytics()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/system/analytics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'user_activity',
                'game_statistics',
                'performance_metrics',
                'error_analytics',
                'usage_patterns',
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_recommendations()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/api/game/system/recommendations');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'performance_recommendations',
                'security_recommendations',
                'optimization_suggestions',
                'maintenance_suggestions',
            ]);
    }
}
