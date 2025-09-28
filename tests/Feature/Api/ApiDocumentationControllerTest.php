<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiDocumentationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_api_info()
    {
        $response = $this->getJson('/api/documentation/info');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'name',
                    'version',
                    'description',
                    'features',
                    'endpoints',
                    'authentication',
                    'documentation',
                ],
            ]);
    }

    public function test_can_get_health_status()
    {
        $response = $this->getJson('/api/documentation/health');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'status',
                    'timestamp',
                    'version',
                    'services' => [
                        'database',
                        'cache',
                        'queue',
                    ],
                    'uptime',
                    'response_time',
                ],
            ]);
    }

    public function test_can_get_endpoints_list()
    {
        $response = $this->getJson('/api/documentation/endpoints');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'endpoints',
                    'total',
                    'tags',
                ],
            ]);
    }

    public function test_api_info_contains_expected_data()
    {
        $response = $this->getJson('/api/documentation/info');

        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Online Game API',
                    'version' => '1.0.0',
                    'authentication' => [
                        'type' => 'Bearer Token',
                        'provider' => 'Laravel Sanctum',
                    ],
                ],
            ]);
    }

    public function test_health_status_contains_expected_services()
    {
        $response = $this->getJson('/api/documentation/health');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'services' => [
                        'database',
                        'cache',
                        'queue',
                    ],
                ],
            ]);
    }

    public function test_endpoints_list_contains_expected_endpoints()
    {
        $response = $this->getJson('/api/documentation/endpoints');

        $response
            ->assertStatus(200)
            ->assertJson([
                'data' => [
                    'total' => 6,
                    'tags' => [
                        'Authentication',
                        'Player Management',
                        'Village Management',
                        'Documentation',
                    ],
                ],
            ]);
    }
}
