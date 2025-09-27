<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Services\LarautilxIntegrationService;
use Illuminate\Http\Request;
use LaraUtilX\Traits\ApiResponseTrait;

class LarautilxController extends Controller
{
    use ApiResponseTrait;

    protected LarautilxIntegrationService $integrationService;

    public function __construct(LarautilxIntegrationService $integrationService)
    {
        $this->integrationService = $integrationService;
    }

    /**
     * Get Larautilx integration status and statistics
     */
    public function getStatus()
    {
        $status = $this->integrationService->getIntegrationStatus();
        return $this->successResponse($status, 'Larautilx integration status retrieved successfully.');
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats()
    {
        $stats = $this->integrationService->getCacheStats();
        return $this->successResponse($stats, 'Cache statistics retrieved successfully.');
    }

    /**
     * Clear cache by tags
     */
    public function clearCache(Request $request)
    {
        $validated = $request->validate([
            'tags' => 'required|array',
            'tags.*' => 'string',
        ]);

        $this->integrationService->clearCacheByTags($validated['tags']);

        return $this->successResponse(null, 'Cache cleared successfully.');
    }

    /**
     * Clear player cache
     */
    public function clearPlayerCache(Request $request)
    {
        $validated = $request->validate([
            'player_id' => 'required|integer|exists:players,id',
        ]);

        $this->integrationService->clearPlayerCache($validated['player_id']);

        return $this->successResponse(null, 'Player cache cleared successfully.');
    }

    /**
     * Clear world cache
     */
    public function clearWorldCache(Request $request)
    {
        $validated = $request->validate([
            'world_id' => 'required|integer|exists:worlds,id',
        ]);

        $this->integrationService->clearWorldCache($validated['world_id']);

        return $this->successResponse(null, 'World cache cleared successfully.');
    }

    /**
     * Clear village cache
     */
    public function clearVillageCache(Request $request)
    {
        $validated = $request->validate([
            'village_id' => 'required|integer|exists:villages,id',
        ]);

        $this->integrationService->clearVillageCache($validated['village_id']);

        return $this->successResponse(null, 'Village cache cleared successfully.');
    }

    /**
     * Test filtering functionality
     */
    public function testFiltering(Request $request)
    {
        $validated = $request->validate([
            'filters' => 'required|array',
            'filters.*.field' => 'required|string',
            'filters.*.operator' => 'required|string|in:equals,not_equals,contains,not_contains,starts_with,ends_with',
            'filters.*.value' => 'nullable',
        ]);

        $validatedFilters = $this->integrationService->validateFilters($validated['filters']);

        return $this->successResponse([
            'original_filters' => $validated['filters'],
            'validated_filters' => $validatedFilters,
            'filter_count' => count($validatedFilters),
        ], 'Filtering test completed successfully.');
    }

    /**
     * Test pagination functionality
     */
    public function testPagination(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'per_page' => 'integer|min:1|max:100',
            'current_page' => 'integer|min:1',
        ]);

        $perPage = $validated['per_page'] ?? 10;
        $currentPage = $validated['current_page'] ?? 1;

        $paginator = $this->integrationService->createPaginatedResponse(
            $validated['items'],
            $perPage,
            $currentPage,
            ['path' => $request->url()]
        );

        return $this->successResponse([
            'paginator' => $paginator,
            'pagination_info' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ], 'Pagination test completed successfully.');
    }

    /**
     * Test caching functionality
     */
    public function testCaching(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'data' => 'required',
            'expiration' => 'integer|min:1|max:3600',
            'tags' => 'array',
            'tags.*' => 'string',
        ]);

        $key = $validated['key'];
        $data = $validated['data'];
        $expiration = $validated['expiration'] ?? 300;
        $tags = $validated['tags'] ?? ['test'];

        $cachedData = $this->integrationService->cacheGameData(
            $key,
            fn() => $data,
            $expiration,
            $tags
        );

        return $this->successResponse([
            'key' => $key,
            'cached_data' => $cachedData,
            'expiration' => $expiration,
            'tags' => $tags,
            'cache_stats' => $this->integrationService->getCacheStats(),
        ], 'Caching test completed successfully.');
    }

    /**
     * Get API documentation for Larautilx integration
     */
    public function getApiDocumentation()
    {
        $documentation = [
            'title' => 'Larautilx Integration API',
            'version' => '1.1.6',
            'description' => 'API endpoints for Larautilx utility toolkit integration',
            'endpoints' => [
                'GET /game/api/larautilx/status' => [
                    'description' => 'Get integration status and statistics',
                    'response' => 'Integration status object',
                ],
                'GET /game/api/larautilx/cache/stats' => [
                    'description' => 'Get cache statistics',
                    'response' => 'Cache statistics object',
                ],
                'POST /game/api/larautilx/cache/clear' => [
                    'description' => 'Clear cache by tags',
                    'parameters' => ['tags' => 'array of cache tags'],
                    'response' => 'Success message',
                ],
                'POST /game/api/larautilx/cache/player/clear' => [
                    'description' => 'Clear player-specific cache',
                    'parameters' => ['player_id' => 'integer'],
                    'response' => 'Success message',
                ],
                'POST /game/api/larautilx/cache/world/clear' => [
                    'description' => 'Clear world-specific cache',
                    'parameters' => ['world_id' => 'integer'],
                    'response' => 'Success message',
                ],
                'POST /game/api/larautilx/cache/village/clear' => [
                    'description' => 'Clear village-specific cache',
                    'parameters' => ['village_id' => 'integer'],
                    'response' => 'Success message',
                ],
                'POST /game/api/larautilx/test/filtering' => [
                    'description' => 'Test filtering functionality',
                    'parameters' => ['filters' => 'array of filter objects'],
                    'response' => 'Filtering test results',
                ],
                'POST /game/api/larautilx/test/pagination' => [
                    'description' => 'Test pagination functionality',
                    'parameters' => ['items' => 'array', 'per_page' => 'integer', 'current_page' => 'integer'],
                    'response' => 'Pagination test results',
                ],
                'POST /game/api/larautilx/test/caching' => [
                    'description' => 'Test caching functionality',
                    'parameters' => ['key' => 'string', 'data' => 'mixed', 'expiration' => 'integer', 'tags' => 'array'],
                    'response' => 'Caching test results',
                ],
                'GET /game/api/larautilx/docs' => [
                    'description' => 'Get API documentation',
                    'response' => 'API documentation object',
                ],
            ],
            'integrated_features' => [
                'ApiResponseTrait' => 'Standardized JSON responses',
                'FilteringUtil' => 'Declarative filtering for collections',
                'PaginationUtil' => 'Consistent pagination',
                'CachingUtil' => 'Tag-aware caching',
                'FileProcessingTrait' => 'File upload and management',
                'AccessLogMiddleware' => 'Request logging',
                'CrudController' => 'Standardized CRUD operations',
                'GameValidationTrait' => 'Game-specific validation',
            ],
        ];

        return $this->successResponse($documentation, 'API documentation retrieved successfully.');
    }
}
