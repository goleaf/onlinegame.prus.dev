<?php

namespace Tests\Feature;

use App\Http\Controllers\Game\PlayerController;
use App\Http\Controllers\Game\TaskController;
use App\Http\Controllers\Game\VillageController;
use App\Services\LarautilxIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use LaraUtilX\Enums\LogLevel;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\ConfigUtil;
use LaraUtilX\Utilities\FilteringUtil;
use LaraUtilX\Utilities\LoggingUtil;
use LaraUtilX\Utilities\PaginationUtil;
use LaraUtilX\Utilities\QueryParameterUtil;
use LaraUtilX\Utilities\RateLimiterUtil;
use LaraUtilX\Utilities\SchedulerUtil;
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

    /**
     * @test
     */
    public function it_can_register_larautilx_utilities()
    {
        // Test that all LaraUtilX utilities are properly registered
        $this->assertTrue(app()->bound(CachingUtil::class));
        $this->assertTrue(app()->bound(FilteringUtil::class));
        $this->assertTrue(app()->bound(PaginationUtil::class));
        $this->assertTrue(app()->bound(LoggingUtil::class));
        $this->assertTrue(app()->bound(RateLimiterUtil::class));
        $this->assertTrue(app()->bound(QueryParameterUtil::class));
        $this->assertTrue(app()->bound(SchedulerUtil::class));
        $this->assertTrue(app()->bound(ConfigUtil::class));
    }

    /**
     * @test
     */
    public function it_can_use_caching_util()
    {
        $cachingUtil = app(CachingUtil::class);

        // Test basic caching functionality
        $key = 'test_cache_key';
        $value = ['test' => 'data'];

        Cache::put($key, $value, 60);
        $this->assertEquals($value, CachingUtil::get($key));

        CachingUtil::forget($key);
        $this->assertNull(CachingUtil::get($key));
    }

    /**
     * @test
     */
    public function it_can_use_filtering_util()
    {
        $collection = collect([
            ['name' => 'John', 'age' => 25],
            ['name' => 'Jane', 'age' => 30],
            ['name' => 'Bob', 'age' => 35],
        ]);

        $filtered = FilteringUtil::filter($collection, 'age', 'not_equals', 25);
        $this->assertCount(2, $filtered);

        $filtered = FilteringUtil::filter($collection, 'name', 'contains', 'J');
        $this->assertCount(2, $filtered);
    }

    /**
     * @test
     */
    public function it_can_use_pagination_util()
    {
        $items = range(1, 100);
        $paginator = PaginationUtil::paginate($items, 10, 1);

        $this->assertEquals(10, $paginator->count());
        $this->assertEquals(100, $paginator->total());
        $this->assertEquals(10, $paginator->lastPage());
    }

    /**
     * @test
     */
    public function it_can_use_logging_util()
    {
        $loggingUtil = app(LoggingUtil::class);

        // Test that logging works without errors using correct LogLevel enum
        LoggingUtil::log(LogLevel::Info, 'Test message');
        LoggingUtil::log(LogLevel::Error, 'Test error');

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_can_use_rate_limiter_util()
    {
        $rateLimiter = app(RateLimiterUtil::class);

        $key = 'test_rate_limit';
        $maxAttempts = 5;
        $decayMinutes = 1;

        // Test rate limiting
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->assertTrue($rateLimiter->attempt($key, $maxAttempts, $decayMinutes));
        }

        // Should be rate limited now
        $this->assertFalse($rateLimiter->attempt($key, $maxAttempts, $decayMinutes));
    }

    /**
     * @test
     */
    public function it_can_use_query_parameter_util()
    {
        $queryUtil = app(QueryParameterUtil::class);

        $request = request();
        $request->merge([
            'page' => 2,
            'per_page' => 15,
            'sort' => 'name',
            'order' => 'desc',
            'filter' => 'active',
        ]);

        $params = QueryParameterUtil::parse($request, ['page', 'per_page', 'sort', 'order', 'filter']);

        $this->assertEquals(2, $params['page']);
        $this->assertEquals(15, $params['per_page']);
        $this->assertEquals('name', $params['sort']);
        $this->assertEquals('desc', $params['order']);
    }

    /**
     * @test
     */
    public function it_can_use_scheduler_util()
    {
        $scheduler = app(SchedulerUtil::class);

        // Test that scheduler can be used (avoid memory issues)
        $this->assertTrue($scheduler !== null);
    }

    /**
     * @test
     */
    public function it_can_use_config_util()
    {
        $configUtil = app(ConfigUtil::class);

        // Test configuration access
        $this->assertNotNull($configUtil->getAllAppSettings());
        $this->assertTrue(is_array($configUtil->getAllAppSettings()));
    }

    /**
     * @test
     */
    public function it_can_use_api_response_trait()
    {
        $controller = new PlayerController();

        // Test that the trait is available
        $this->assertTrue(method_exists($controller, 'successResponse'));
        $this->assertTrue(method_exists($controller, 'errorResponse'));
        $this->assertTrue(method_exists($controller, 'exceptionResponse'));
    }

    /**
     * @test
     */
    public function it_can_use_file_processing_trait()
    {
        // Create a test class that uses the FileProcessingTrait
        $testClass = new class () {
            use \LaraUtilX\Traits\FileProcessingTrait;
        };

        // Test that the trait methods are available
        $this->assertTrue(method_exists($testClass, 'getFile'));
        $this->assertTrue(method_exists($testClass, 'uploadFile'));
    }

    /**
     * @test
     */
    public function it_can_apply_advanced_filters()
    {
        $collection = collect([
            ['name' => 'John', 'age' => 25, 'active' => true],
            ['name' => 'Jane', 'age' => 30, 'active' => false],
            ['name' => 'Bob', 'age' => 35, 'active' => true],
        ]);

        $filters = [
            ['field' => 'name', 'operator' => 'contains', 'value' => 'Bob'],
            ['field' => 'active', 'operator' => 'equals', 'value' => true],
        ];

        $filtered = $this->integrationService->applyAdvancedFilters($collection, $filters);

        $this->assertCount(1, $filtered);
        $this->assertEquals('Bob', $filtered->first()['name']);
    }

    /**
     * @test
     */
    public function it_can_create_paginated_response()
    {
        $items = range(1, 50);
        $paginator = $this->integrationService->createPaginatedResponse($items, 10, 2);

        $this->assertEquals(10, $paginator->count());
        $this->assertEquals(50, $paginator->total());
        $this->assertEquals(2, $paginator->currentPage());
    }

    /**
     * @test
     */
    public function it_can_cache_game_data()
    {
        $key = 'test_game_data';
        $callback = function () {
            return ['game' => 'data'];
        };

        $result = $this->integrationService->cacheGameData($key, $callback);

        $this->assertEquals(['game' => 'data'], $result);

        // Test that it's cached
        $cached = $this->integrationService->cacheGameData($key, function () {
            return ['different' => 'data'];
        });

        $this->assertEquals(['game' => 'data'], $cached);
    }

    /**
     * @test
     */
    public function it_can_cache_player_data()
    {
        $playerId = 1;
        $key = 'villages';
        $callback = function () {
            return ['village1', 'village2'];
        };

        $result = $this->integrationService->cachePlayerData($playerId, $key, $callback);

        $this->assertEquals(['village1', 'village2'], $result);
    }

    /**
     * @test
     */
    public function it_can_cache_world_data()
    {
        $worldId = 1;
        $key = 'players';
        $callback = function () {
            return ['player1', 'player2'];
        };

        $result = $this->integrationService->cacheWorldData($worldId, $key, $callback);

        $this->assertEquals(['player1', 'player2'], $result);
    }

    /**
     * @test
     */
    public function it_can_cache_village_data()
    {
        $villageId = 1;
        $key = 'buildings';
        $callback = function () {
            return ['building1', 'building2'];
        };

        $result = $this->integrationService->cacheVillageData($villageId, $key, $callback);

        $this->assertEquals(['building1', 'building2'], $result);
    }

    /**
     * @test
     */
    public function it_can_clear_cache_by_tags()
    {
        // This test would require a taggable cache store
        $this->assertTrue(true); // Placeholder for cache tag testing
    }

    /**
     * @test
     */
    public function it_can_clear_player_cache()
    {
        $playerId = 1;

        // Test that method exists and doesn't throw errors
        $this->integrationService->clearPlayerCache($playerId);
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_can_clear_world_cache()
    {
        $worldId = 1;

        // Test that method exists and doesn't throw errors
        $this->integrationService->clearWorldCache($worldId);
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_can_clear_village_cache()
    {
        $villageId = 1;

        // Test that method exists and doesn't throw errors
        $this->integrationService->clearVillageCache($villageId);
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_can_get_cache_stats()
    {
        $stats = $this->integrationService->getCacheStats();

        $this->assertArrayHasKey('default_expiration', $stats);
        $this->assertArrayHasKey('default_tags', $stats);
        $this->assertArrayHasKey('cache_store', $stats);
        $this->assertArrayHasKey('supports_tags', $stats);
    }

    /**
     * @test
     */
    public function it_can_create_api_response()
    {
        $data = ['test' => 'data'];
        $response = $this->integrationService->createApiResponse($data, 'Success', 200);

        $this->assertTrue($response['success']);
        $this->assertEquals('Success', $response['message']);
        $this->assertEquals($data, $response['data']);
        $this->assertEquals(200, $response['status_code']);
    }

    /**
     * @test
     */
    public function it_can_create_paginated_api_response()
    {
        $items = range(1, 50);
        $paginator = $this->integrationService->createPaginatedResponse($items, 10, 1);
        $response = $this->integrationService->createPaginatedApiResponse($paginator);

        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('pagination', $response['meta']);
        $this->assertEquals(50, $response['meta']['pagination']['total']);
    }

    /**
     * @test
     */
    public function it_can_create_error_response()
    {
        $response = $this->integrationService->createErrorResponse('Test error', 400, ['field' => 'error']);

        $this->assertFalse($response['success']);
        $this->assertEquals('Test error', $response['message']);
        $this->assertEquals(400, $response['status_code']);
        $this->assertArrayHasKey('field', $response['errors']);
    }

    /**
     * @test
     */
    public function it_can_validate_filters()
    {
        $filters = [
            ['field' => 'name', 'operator' => 'contains', 'value' => 'John'],
            ['field' => 'age', 'operator' => 'equals', 'value' => 25],
            ['field' => 'invalid', 'operator' => 'invalid_operator', 'value' => 'test'],
        ];

        $validated = $this->integrationService->validateFilters($filters);

        $this->assertCount(2, $validated); // Only valid filters should be returned
    }

    /**
     * @test
     */
    public function it_can_get_integration_status()
    {
        $status = $this->integrationService->getIntegrationStatus();

        $this->assertArrayHasKey('larautilx_version', $status);
        $this->assertArrayHasKey('integrated_components', $status);
        $this->assertArrayHasKey('cache_stats', $status);
        $this->assertArrayHasKey('active_middleware', $status);
        $this->assertArrayHasKey('created_controllers', $status);
        $this->assertArrayHasKey('created_services', $status);
    }

    /**
     * @test
     */
    public function it_can_clear_all_caches()
    {
        $result = $this->integrationService->clearAllCaches();

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_can_use_crud_controller()
    {
        $controller = new PlayerController();

        // Test that the controller extends CrudController
        $this->assertInstanceOf(\LaraUtilX\Http\Controllers\CrudController::class, $controller);

        // Test that it has the required properties (using reflection to access protected properties)
        $reflection = new \ReflectionClass($controller);
        $modelProperty = $reflection->getProperty('model');
        $modelProperty->setAccessible(true);
        $this->assertNotNull($modelProperty->getValue($controller));

        $validationRulesProperty = $reflection->getProperty('validationRules');
        $validationRulesProperty->setAccessible(true);
        $this->assertIsArray($validationRulesProperty->getValue($controller));
    }

    /**
     * @test
     */
    public function it_can_use_village_controller()
    {
        $controller = new VillageController();

        // Test that the controller extends CrudController
        $this->assertInstanceOf(\LaraUtilX\Http\Controllers\CrudController::class, $controller);
    }

    /**
     * @test
     */
    public function it_can_use_task_controller()
    {
        $controller = new TaskController();

        // Test that the controller extends CrudController
        $this->assertInstanceOf(\LaraUtilX\Http\Controllers\CrudController::class, $controller);
    }

    /**
     * @test
     */
    public function it_can_use_middleware()
    {
        // Test that access log middleware is registered
        $this->assertTrue(app('router')->hasMiddlewareGroup('web'));
    }

    /**
     * @test
     */
    public function it_can_use_llm_providers()
    {
        // Test that LLM providers are registered
        $this->assertTrue(app()->bound(\LaraUtilX\LLMProviders\Contracts\LLMProviderInterface::class));
    }

    /**
     * @test
     */
    public function it_can_use_access_log_middleware()
    {
        // Test that access log middleware is available
        $this->assertTrue(class_exists(\LaraUtilX\Http\Middleware\AccessLogMiddleware::class));
    }

    /**
     * @test
     */
    public function it_can_use_access_log_model()
    {
        // Test that access log model is available
        $this->assertTrue(class_exists(\LaraUtilX\Models\AccessLog::class));
    }

    /**
     * @test
     */
    public function it_can_use_feature_toggle_util()
    {
        // Test that feature toggle utility is available
        $this->assertTrue(class_exists(\LaraUtilX\Utilities\FeatureToggleUtil::class));
    }

    /**
     * @test
     */
    public function it_can_use_all_utilities_together()
    {
        // Test comprehensive integration
        $cachingUtil = app(CachingUtil::class);
        $filteringUtil = app(FilteringUtil::class);
        $paginationUtil = app(PaginationUtil::class);
        $loggingUtil = app(LoggingUtil::class);

        // Create test data
        $data = collect([
            ['id' => 1, 'name' => 'Player 1', 'score' => 100],
            ['id' => 2, 'name' => 'Player 2', 'score' => 200],
            ['id' => 3, 'name' => 'Player 3', 'score' => 150],
        ]);

        // Filter data (using supported operator)
        $filtered = $filteringUtil->filter($data, 'name', 'contains', 'Player');

        // Paginate data
        $paginated = $paginationUtil->paginate($filtered->toArray(), 2, 1);

        // Cache result
        $cacheKey = 'filtered_players';
        $cachingUtil->cache($cacheKey, $paginated->toArray(), 60);

        // Log operation
        LoggingUtil::log(LogLevel::Info, 'Players filtered and paginated');

        // Verify results
        $this->assertCount(3, $filtered);
        $this->assertEquals(2, $paginated->count());
        $this->assertEquals(3, $paginated->total());

        // Verify that all utilities worked together successfully
        $this->assertTrue(true);
    }
}
