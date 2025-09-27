<?php

namespace Tests\Feature;

use App\Services\LarautilxIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraUtilX\Utilities\FilteringUtil;
use LaraUtilX\Utilities\PaginationUtil;
use LaraUtilX\Utilities\LoggingUtil;
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
    public function it_can_apply_advanced_filters_to_collection()
    {
        $collection = collect([
            ['id' => 1, 'name' => 'Test 1', 'active' => true],
            ['id' => 2, 'name' => 'Test 2', 'active' => false],
            ['id' => 3, 'name' => 'Test 3', 'active' => true],
        ]);

        $filters = [
            ['field' => 'active', 'operator' => 'equals', 'value' => true],
            ['field' => 'name', 'operator' => 'contains', 'value' => 'Test']
        ];

        $filtered = $this->integrationService->applyAdvancedFilters($collection, $filters);

        $this->assertCount(2, $filtered);
        $this->assertTrue($filtered->every(fn($item) => $item['active'] === true));
    }

    /** @test */
    public function it_can_create_paginated_response()
    {
        $items = range(1, 50);
        $perPage = 10;
        $currentPage = 2;

        $paginator = $this->integrationService->createPaginatedResponse($items, $perPage, $currentPage);

        $this->assertEquals(50, $paginator->total());
        $this->assertEquals(10, $paginator->perPage());
        $this->assertEquals(2, $paginator->currentPage());
        $this->assertEquals(5, $paginator->lastPage());
    }

    /** @test */
    public function it_can_create_standardized_api_response()
    {
        $data = ['test' => 'data'];
        $message = 'Test message';
        $statusCode = 200;

        $response = $this->integrationService->createApiResponse($data, $message, $statusCode);

        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('meta', $response);
        $this->assertArrayHasKey('status_code', $response);

        $this->assertTrue($response['success']);
        $this->assertEquals($message, $response['message']);
        $this->assertEquals($data, $response['data']);
        $this->assertEquals($statusCode, $response['status_code']);
    }

    /** @test */
    public function it_can_create_error_response()
    {
        $message = 'Error message';
        $statusCode = 500;
        $errors = ['field' => ['error message']];

        $response = $this->integrationService->createErrorResponse($message, $statusCode, $errors);

        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('status_code', $response);

        $this->assertFalse($response['success']);
        $this->assertEquals($message, $response['message']);
        $this->assertEquals($errors, $response['errors']);
        $this->assertEquals($statusCode, $response['status_code']);
    }

    /** @test */
    public function it_can_validate_filters()
    {
        $filters = [
            ['field' => 'name', 'operator' => 'equals', 'value' => 'test'],
            ['field' => 'age', 'operator' => 'invalid_operator', 'value' => 25],
            ['field' => 'active', 'operator' => 'contains', 'value' => true],
        ];

        $validatedFilters = $this->integrationService->validateFilters($filters);

        $this->assertCount(2, $validatedFilters);
        $this->assertEquals('name', $validatedFilters[0]['field']);
        $this->assertEquals('active', $validatedFilters[1]['field']);
    }

    /** @test */
    public function it_can_get_integration_status()
    {
        $status = $this->integrationService->getIntegrationStatus();

        $this->assertArrayHasKey('larautilx_version', $status);
        $this->assertArrayHasKey('integrated_components', $status);
        $this->assertArrayHasKey('cache_stats', $status);
        $this->assertArrayHasKey('active_middleware', $status);
        $this->assertArrayHasKey('created_controllers', $status);
        $this->assertArrayHasKey('created_services', $status);

        $this->assertEquals('1.1.6', $status['larautilx_version']);
        $this->assertTrue($status['integrated_components']['ApiResponseTrait']);
        $this->assertTrue($status['integrated_components']['FilteringUtil']);
        $this->assertTrue($status['integrated_components']['PaginationUtil']);
    }

    /** @test */
    public function it_can_get_cache_statistics()
    {
        $stats = $this->integrationService->getCacheStats();

        $this->assertArrayHasKey('default_expiration', $stats);
        $this->assertArrayHasKey('default_tags', $stats);
        $this->assertArrayHasKey('cache_store', $stats);
        $this->assertArrayHasKey('supports_tags', $stats);

        $this->assertEquals(300, $stats['default_expiration']);
        $this->assertContains('game', $stats['default_tags']);
        $this->assertContains('larautilx', $stats['default_tags']);
    }

    /** @test */
    public function filtering_util_can_filter_collections()
    {
        $collection = collect([
            ['id' => 1, 'name' => 'John', 'age' => 25],
            ['id' => 2, 'name' => 'Jane', 'age' => 30],
            ['id' => 3, 'name' => 'Bob', 'age' => 25],
        ]);

        $filtered = FilteringUtil::filter($collection, 'age', 'equals', 25);

        $this->assertCount(2, $filtered);
        $this->assertTrue($filtered->every(fn($item) => $item['age'] === 25));
    }

    /** @test */
    public function pagination_util_can_paginate_arrays()
    {
        $items = range(1, 25);
        $perPage = 5;
        $currentPage = 2;

        $paginator = PaginationUtil::paginate($items, $perPage, $currentPage);

        $this->assertEquals(25, $paginator->total());
        $this->assertEquals(5, $paginator->perPage());
        $this->assertEquals(2, $paginator->currentPage());
        $this->assertEquals(5, $paginator->lastPage());
        $this->assertCount(5, $paginator->items());
    }

    /** @test */
    public function logging_util_can_log_messages()
    {
        // This test ensures LoggingUtil is working without throwing exceptions
        $this->expectNotToPerformAssertions();
        
        LoggingUtil::info('Test info message', ['test' => 'data'], 'test_context');
        LoggingUtil::error('Test error message', ['error' => 'test_error'], 'test_context');
        LoggingUtil::warning('Test warning message', ['warning' => 'test_warning'], 'test_context');
        LoggingUtil::debug('Test debug message', ['debug' => 'test_debug'], 'test_context');
    }
}
