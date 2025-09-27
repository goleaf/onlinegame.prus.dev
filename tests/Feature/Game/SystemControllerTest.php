<?php

namespace Tests\Feature\Game;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * @test
     */
    public function it_can_get_system_configuration()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/system/config');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'game' => [
                        'worlds',
                        'players',
                        'villages',
                        'alliances',
                        'features',
                    ]
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_health_status()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/system/health');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'status',
                    'checks' => [
                        'database',
                        'cache',
                        'storage',
                        'game_system',
                        'larautilx',
                    ],
                    'timestamp',
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_metrics()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/system/metrics');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'timestamp',
                    'performance' => [
                        'memory_usage',
                        'memory_peak',
                        'memory_limit',
                    ],
                    'database' => [
                        'connections',
                        'query_count',
                    ],
                    'cache' => [
                        'driver',
                        'stores',
                    ],
                    'game_metrics' => [
                        'active_sessions',
                        'total_requests_today',
                        'new_registrations_today',
                    ],
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_scheduled_tasks()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/system/scheduled-tasks');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'tasks',
                    'summary' => [
                        'total_tasks',
                        'overdue_tasks',
                        'running_tasks',
                        'due_tasks',
                    ],
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_clear_system_caches()
    {
        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/api/system/clear-caches', [
                'cache_types' => ['config', 'route']
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'cleared_caches',
                    'timestamp',
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_system_logs()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/system/logs?level=info&limit=50');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'logs',
                    'metadata' => [
                        'level',
                        'limit',
                        'since',
                        'total_retrieved',
                    ],
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_update_system_configuration()
    {
        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/api/system/config/update', [
                'key' => 'test_setting',
                'value' => 'test_value',
                'section' => 'testing',
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'key',
                    'value',
                    'updated_at',
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_validates_configuration_update_data()
    {
        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/api/system/config/update', []);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'key',
                'value',
            ]);
    }

    /**
     * @test
     */
    public function it_can_filter_configuration_by_section()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/system/config?section=game');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'game',
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_get_specific_configuration_key()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/system/config?key=app.name');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'specific_key',
                ]
            ]);
    }

    /**
     * @test
     */
    public function it_can_include_app_configuration()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/api/system/config?include_app=true');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'app',
                ]
            ]);
    }
}
