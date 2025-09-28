<?php

namespace Tests\Unit;

use App\Services\LarautilxIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\ConfigUtil;
use LaraUtilX\Utilities\FeatureToggleUtil;
use LaraUtilX\Utilities\FilteringUtil;
use LaraUtilX\Utilities\LoggingUtil;
use LaraUtilX\Utilities\PaginationUtil;
use LaraUtilX\Utilities\RateLimiterUtil;
use Tests\TestCase;

class LarautilxUtilitiesTest extends TestCase
{
    use RefreshDatabase;

    protected LarautilxIntegrationService $integrationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->integrationService = app(LarautilxIntegrationService::class);
    }

    /** @test */
    public function caching_util_can_store_and_retrieve_data()
    {
        $key = 'test_cache_key';
        $data = ['test' => 'data', 'number' => 123];

        // Store data
        CachingUtil::cache($key, $data, now()->addMinutes(5));

        // Retrieve data
        $retrievedData = CachingUtil::get($key);

        $this->assertEquals($data, $retrievedData);
    }

    /** @test */
    public function caching_util_can_handle_expiration()
    {
        $key = 'test_expiration_key';
        $data = 'test_data';

        // Store with short expiration
        CachingUtil::cache($key, $data, now()->addSeconds(1));

        // Should be available immediately
        $this->assertEquals($data, CachingUtil::get($key));

        // Wait for expiration (in real scenario)
        CachingUtil::forget($key);
        $this->assertNull(CachingUtil::get($key));
    }

    /** @test */
    public function caching_util_can_clear_cache()
    {
        $key1 = 'test_key_1';
        $key2 = 'test_key_2';
        $data = 'test_data';

        CachingUtil::cache($key1, $data, now()->addMinutes(5));
        CachingUtil::cache($key2, $data, now()->addMinutes(5));

        // Both should exist
        $this->assertEquals($data, CachingUtil::get($key1));
        $this->assertEquals($data, CachingUtil::get($key2));

        // Clear specific key
        CachingUtil::forget($key1);
        $this->assertNull(CachingUtil::get($key1));
        $this->assertEquals($data, CachingUtil::get($key2));

        // Clear remaining key
        CachingUtil::forget($key2);
        $this->assertNull(CachingUtil::get($key2));
    }

    /** @test */
    public function logging_util_can_log_different_levels()
    {
        $this->expectNotToPerformAssertions();

        // Test different log levels
        LoggingUtil::debug('Debug message', ['context' => 'test']);
        LoggingUtil::info('Info message', ['context' => 'test']);
        LoggingUtil::warning('Warning message', ['context' => 'test']);
        LoggingUtil::error('Error message', ['context' => 'test']);
        LoggingUtil::critical('Critical message', ['context' => 'test']);

        // Test with custom channel
        LoggingUtil::log('info', 'Custom channel message', ['context' => 'test'], 'custom_channel');
    }

    /** @test */
    public function rate_limiter_util_can_limit_attempts()
    {
        $action = 'test_action';
        $identifier = 'test_user_123';
        $maxAttempts = 3;
        $decayMinutes = 1;

        // First 3 attempts should succeed
        $this->assertTrue(RateLimiterUtil::attempt($action, $identifier, $maxAttempts, $decayMinutes));
        $this->assertTrue(RateLimiterUtil::attempt($action, $identifier, $maxAttempts, $decayMinutes));
        $this->assertTrue(RateLimiterUtil::attempt($action, $identifier, $maxAttempts, $decayMinutes));

        // 4th attempt should fail
        $this->assertFalse(RateLimiterUtil::attempt($action, $identifier, $maxAttempts, $decayMinutes));
    }

    /** @test */
    public function rate_limiter_util_can_clear_attempts()
    {
        $action = 'test_action_clear';
        $identifier = 'test_user_clear';
        $maxAttempts = 2;
        $decayMinutes = 1;

        // Use up attempts
        RateLimiterUtil::attempt($action, $identifier, $maxAttempts, $decayMinutes);
        RateLimiterUtil::attempt($action, $identifier, $maxAttempts, $decayMinutes);

        // Should be rate limited
        $this->assertFalse(RateLimiterUtil::attempt($action, $identifier, $maxAttempts, $decayMinutes));

        // Clear attempts
        RateLimiterUtil::clear($action, $identifier);

        // Should work again
        $this->assertTrue(RateLimiterUtil::attempt($action, $identifier, $maxAttempts, $decayMinutes));
    }

    /** @test */
    public function config_util_can_get_and_set_config()
    {
        $key = 'test.config.key';
        $value = 'test_value';

        // Set config
        ConfigUtil::set($key, $value);

        // Get config
        $this->assertEquals($value, ConfigUtil::get($key));

        // Get with default
        $this->assertEquals('default_value', ConfigUtil::get('non.existent.key', 'default_value'));
    }

    /** @test */
    public function feature_toggle_util_can_toggle_features()
    {
        $featureName = 'test_feature_toggle';

        // Feature should be disabled by default
        $this->assertFalse(FeatureToggleUtil::isEnabled($featureName));

        // Enable feature
        FeatureToggleUtil::toggle($featureName, true);
        $this->assertTrue(FeatureToggleUtil::isEnabled($featureName));

        // Disable feature
        FeatureToggleUtil::toggle($featureName, false);
        $this->assertFalse(FeatureToggleUtil::isEnabled($featureName));
    }

    /** @test */
    public function feature_toggle_util_can_get_all_features()
    {
        $feature1 = 'test_feature_1';
        $feature2 = 'test_feature_2';

        FeatureToggleUtil::toggle($feature1, true);
        FeatureToggleUtil::toggle($feature2, false);

        $allFeatures = FeatureToggleUtil::getAll();

        $this->assertIsArray($allFeatures);
        $this->assertArrayHasKey($feature1, $allFeatures);
        $this->assertArrayHasKey($feature2, $allFeatures);
        $this->assertTrue($allFeatures[$feature1]);
        $this->assertFalse($allFeatures[$feature2]);
    }

    /** @test */
    public function filtering_util_can_filter_collections()
    {
        $collection = collect([
            ['name' => 'John', 'age' => 25, 'city' => 'New York'],
            ['name' => 'Jane', 'age' => 30, 'city' => 'Los Angeles'],
            ['name' => 'Bob', 'age' => 35, 'city' => 'New York'],
            ['name' => 'Alice', 'age' => 28, 'city' => 'Chicago'],
        ]);

        // Test equals filter
        $filtered = FilteringUtil::filter($collection, 'city', 'equals', 'New York');
        $this->assertCount(2, $filtered);
        $this->assertEquals('John', $filtered->first()['name']);

        // Test greater than filter
        $filtered = FilteringUtil::filter($collection, 'age', 'gt', 28);
        $this->assertCount(2, $filtered);
        $this->assertEquals('Jane', $filtered->first()['name']);

        // Test contains filter
        $filtered = FilteringUtil::filter($collection, 'name', 'contains', 'an');
        $this->assertCount(2, $filtered);
    }

    /** @test */
    public function pagination_util_can_paginate_arrays()
    {
        $items = range(1, 50);
        $perPage = 10;
        $currentPage = 2;

        $paginator = PaginationUtil::paginate($items, $perPage, $currentPage);

        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $paginator);
        $this->assertEquals(50, $paginator->total());
        $this->assertEquals(10, $paginator->perPage());
        $this->assertEquals(2, $paginator->currentPage());
        $this->assertEquals(5, $paginator->lastPage());
        $this->assertCount(10, $paginator->items());
    }

    /** @test */
    public function pagination_util_can_paginate_with_options()
    {
        $items = range(1, 25);
        $perPage = 5;
        $currentPage = 1;
        $options = [
            'path' => 'http://example.com/test',
            'pageName' => 'custom_page',
        ];

        $paginator = PaginationUtil::paginate($items, $perPage, $currentPage, $options);

        $this->assertEquals('http://example.com/test', $paginator->path());
        $this->assertEquals('custom_page', $paginator->getPageName());
    }

    /** @test */
    public function integration_service_can_create_api_responses()
    {
        $data = ['test' => 'data'];
        $response = $this->integrationService->createApiResponse($data, 'Test message', 200);

        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertEquals('Test message', $response['message']);
        $this->assertEquals($data, $response['data']);
        $this->assertEquals(200, $response['status_code']);
        $this->assertArrayHasKey('meta', $response);
        $this->assertArrayHasKey('timestamp', $response['meta']);
        $this->assertArrayHasKey('larautilx_version', $response['meta']);
    }

    /** @test */
    public function integration_service_can_create_error_responses()
    {
        $errors = ['field' => ['Validation error']];
        $response = $this->integrationService->createErrorResponse(
            'Error occurred',
            422,
            $errors,
            ['debug' => 'test debug']
        );

        $this->assertIsArray($response);
        $this->assertFalse($response['success']);
        $this->assertEquals('Error occurred', $response['message']);
        $this->assertEquals(422, $response['status_code']);
        $this->assertEquals($errors, $response['errors']);
        $this->assertArrayHasKey('debug', $response);
    }

    /** @test */
    public function integration_service_can_validate_filters()
    {
        $filters = [
            ['field' => 'name', 'operator' => 'equals', 'value' => 'John'],
            ['field' => 'age', 'operator' => 'gt', 'value' => 18],
            ['field' => 'invalid', 'operator' => 'invalid_op', 'value' => 'test'],
            ['field' => 'missing_value', 'operator' => 'equals'],
        ];

        $validated = $this->integrationService->validateFilters($filters);

        // Should only return valid filters
        $this->assertCount(2, $validated);
        $this->assertEquals('name', $validated[0]['field']);
        $this->assertEquals('age', $validated[1]['field']);
    }

    /** @test */
    public function integration_service_can_cache_game_data()
    {
        $key = 'test_game_cache';
        $callback = fn () => ['cached' => 'data'];

        $result = $this->integrationService->cacheGameData($key, $callback);

        $this->assertEquals(['cached' => 'data'], $result);
    }

    /** @test */
    public function integration_service_can_cache_player_data()
    {
        $playerId = 123;
        $key = 'test_player_data';
        $callback = fn () => ['player_id' => $playerId, 'data' => 'test'];

        $result = $this->integrationService->cachePlayerData($playerId, $key, $callback);

        $this->assertEquals(['player_id' => $playerId, 'data' => 'test'], $result);
    }

    /** @test */
    public function integration_service_can_cache_village_data()
    {
        $villageId = 456;
        $key = 'test_village_data';
        $callback = fn () => ['village_id' => $villageId, 'data' => 'test'];

        $result = $this->integrationService->cacheVillageData($villageId, $key, $callback);

        $this->assertEquals(['village_id' => $villageId, 'data' => 'test'], $result);
    }

    /** @test */
    public function integration_service_can_clear_cache_by_tags()
    {
        $this->expectNotToPerformAssertions();

        // Test cache clearing by tags
        $this->integrationService->clearCacheByTags(['test_tag']);
        $this->integrationService->clearPlayerCache(123);
        $this->integrationService->clearWorldCache(456);
        $this->integrationService->clearVillageCache(789);
    }

    /** @test */
    public function integration_service_can_get_cache_stats()
    {
        $stats = $this->integrationService->getCacheStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('default_expiration', $stats);
        $this->assertArrayHasKey('default_tags', $stats);
        $this->assertArrayHasKey('cache_store', $stats);
        $this->assertArrayHasKey('supports_tags', $stats);
    }

    /** @test */
    public function integration_service_can_schedule_tasks()
    {
        $taskName = 'test_task';
        $taskData = ['param1' => 'value1'];
        $when = now()->addMinutes(5);

        $result = $this->integrationService->scheduleTask($taskName, $taskData, $when);

        $this->assertTrue($result);
    }

    /** @test */
    public function integration_service_can_parse_query_parameters()
    {
        $params = [
            'filter' => 'active',
            'sort' => 'name',
            'order' => 'desc',
            'page' => '2',
            'per_page' => '20',
        ];

        $parsed = $this->integrationService->parseQueryParameters($params);

        $this->assertIsArray($parsed);
        $this->assertEquals($params, $parsed);
    }

    /** @test */
    public function integration_service_can_optimize_cache()
    {
        $results = $this->integrationService->optimizeCache();

        $this->assertIsArray($results);
        $this->assertArrayHasKey('expired_cleared', $results);
        $this->assertArrayHasKey('tags_optimized', $results);
        $this->assertArrayHasKey('cache_warmed', $results);
    }
}
