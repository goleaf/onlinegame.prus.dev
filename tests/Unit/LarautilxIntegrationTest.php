<?php

namespace Tests\Unit;

use App\Services\LarautilxIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LarautilxIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected LarautilxIntegrationService $integrationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->integrationService = app(LarautilxIntegrationService::class);
    }

    /** @test */
    public function it_can_get_integration_status()
    {
        $status = $this->integrationService->getIntegrationStatus();

        $this->assertIsArray($status);
        $this->assertArrayHasKey('larautilx_version', $status);
        $this->assertArrayHasKey('integrated_components', $status);
        $this->assertArrayHasKey('cache_stats', $status);
        $this->assertArrayHasKey('active_middleware', $status);
        $this->assertArrayHasKey('created_controllers', $status);
        $this->assertArrayHasKey('created_services', $status);
    }

    /** @test */
    public function it_can_get_cache_stats()
    {
        $stats = $this->integrationService->getCacheStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('default_expiration', $stats);
        $this->assertArrayHasKey('default_tags', $stats);
        $this->assertArrayHasKey('cache_store', $stats);
        $this->assertArrayHasKey('supports_tags', $stats);
    }

    /** @test */
    public function it_can_clear_cache_by_tags()
    {
        $tags = ['test', 'game'];

        // This should not throw an exception
        $this->integrationService->clearCacheByTags($tags);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_clear_player_cache()
    {
        $playerId = 1;

        // This should not throw an exception
        $this->integrationService->clearPlayerCache($playerId);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_clear_world_cache()
    {
        $worldId = 1;

        // This should not throw an exception
        $this->integrationService->clearWorldCache($worldId);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_clear_village_cache()
    {
        $villageId = 1;

        // This should not throw an exception
        $this->integrationService->clearVillageCache($villageId);

        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_create_api_response()
    {
        $data = ['test' => 'data'];
        $message = 'Test message';
        $statusCode = 200;
        $meta = ['version' => '1.0'];

        $response = $this->integrationService->createApiResponse($data, $message, $statusCode, $meta);

        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertEquals($message, $response['message']);
        $this->assertEquals($data, $response['data']);
        $this->assertEquals($statusCode, $response['status_code']);
        $this->assertArrayHasKey('meta', $response);
        $this->assertArrayHasKey('timestamp', $response['meta']);
        $this->assertArrayHasKey('larautilx_version', $response['meta']);
    }

    /** @test */
    public function it_can_create_error_response()
    {
        $message = 'Error message';
        $statusCode = 400;
        $errors = ['field' => ['Error details']];
        $debug = 'Debug info';

        $response = $this->integrationService->createErrorResponse($message, $statusCode, $errors, $debug);

        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertEquals($message, $response['message']);
        $this->assertEquals($errors, $response['errors']);
        $this->assertEquals($statusCode, $response['status_code']);
        $this->assertArrayHasKey('meta', $response);
        $this->assertArrayHasKey('timestamp', $response['meta']);
        $this->assertArrayHasKey('larautilx_version', $response['meta']);
    }

    /** @test */
    public function it_can_validate_filters()
    {
        $filters = [
            [
                'field' => 'name',
                'operator' => 'equals',
                'value' => 'test',
            ],
            [
                'field' => 'status',
                'operator' => 'contains',
                'value' => 'active',
            ],
            [
                'field' => 'invalid',
                'operator' => 'invalid_operator',
                'value' => 'test',
            ],
        ];

        $validatedFilters = $this->integrationService->validateFilters($filters);

        $this->assertIsArray($validatedFilters);
        $this->assertCount(2, $validatedFilters); // Only valid filters should be kept
    }

    /** @test */
    public function it_can_clear_all_caches()
    {
        // This should not throw an exception
        $result = $this->integrationService->clearAllCaches();

        $this->assertTrue($result);
    }

    /** @test */
    public function it_can_cache_game_data()
    {
        $key = 'test_cache_key';
        $callback = function () {
            return ['test' => 'data'];
        };
        $expiration = 300;

        $result = $this->integrationService->cacheGameData($key, $callback, $expiration);

        $this->assertEquals(['test' => 'data'], $result);
    }

    /** @test */
    public function it_can_cache_player_data()
    {
        $playerId = 1;
        $key = 'test_player_data';
        $callback = function () {
            return ['player' => 'data'];
        };
        $expiration = 300;

        $result = $this->integrationService->cachePlayerData($playerId, $key, $callback, $expiration);

        $this->assertEquals(['player' => 'data'], $result);
    }

    /** @test */
    public function it_can_cache_world_data()
    {
        $worldId = 1;
        $key = 'test_world_data';
        $callback = function () {
            return ['world' => 'data'];
        };
        $expiration = 300;

        $result = $this->integrationService->cacheWorldData($worldId, $key, $callback, $expiration);

        $this->assertEquals(['world' => 'data'], $result);
    }

    /** @test */
    public function it_can_cache_village_data()
    {
        $villageId = 1;
        $key = 'test_village_data';
        $callback = function () {
            return ['village' => 'data'];
        };
        $expiration = 300;

        $result = $this->integrationService->cacheVillageData($villageId, $key, $callback, $expiration);

        $this->assertEquals(['village' => 'data'], $result);
    }
}
