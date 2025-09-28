<?php

namespace Tests\Feature\Game;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LarautilxDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_get_dashboard_data()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/larautilx/dashboard');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'integration_status',
                    'ai_service_status',
                    'feature_toggles',
                    'system_health',
                    'performance_metrics',
                    'scheduled_tasks',
                    'configuration_status',
                    'usage_statistics',
                    'recent_activity',
                ],
            ]);
    }

    public function test_can_get_integration_summary()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/larautilx/integration-summary');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'package_info',
                    'integrated_components',
                    'game_integration',
                    'api_endpoints',
                    'configuration_files',
                ],
            ]);
    }

    public function test_can_test_components()
    {
        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/larautilx/test-components', [
                'components' => ['caching', 'filtering', 'logging'],
            ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'caching',
                    'filtering',
                    'logging',
                ],
            ]);
    }

    public function test_can_test_components_with_default()
    {
        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/larautilx/test-components', []);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ]);
    }

    public function test_validation_errors_for_test_components()
    {
        $response = $this
            ->actingAs($this->user)
            ->postJson('/game/larautilx/test-components', [
                'components' => ['invalid_component'],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    public function test_dashboard_data_contains_expected_structure()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/larautilx/dashboard');

        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'feature_toggles' => [
                        'features',
                        'enabled_count',
                        'total_count',
                        'enabled_percentage',
                    ],
                    'system_health' => [
                        'checks',
                        'overall_status',
                        'healthy_count',
                        'total_count',
                        'health_percentage',
                    ],
                ],
            ]);
    }

    public function test_integration_summary_contains_package_info()
    {
        $response = $this
            ->actingAs($this->user)
            ->getJson('/game/larautilx/integration-summary');

        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'package_info' => [
                        'name' => 'omarchouman/lara-util-x',
                        'version' => '1.1',
                        'installed' => true,
                    ],
                ],
            ]);
    }

    public function test_requires_authentication()
    {
        $response = $this->getJson('/game/larautilx/dashboard');
        $response->assertStatus(401);

        $response = $this->getJson('/game/larautilx/integration-summary');
        $response->assertStatus(401);

        $response = $this->postJson('/game/larautilx/test-components');
        $response->assertStatus(401);
    }
}
