<?php

namespace App\Services;

use Illuminate\Support\Collection;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\FilteringUtil;
use LaraUtilX\Utilities\PaginationUtil;

class LarautilxIntegrationService
{
    protected CachingUtil $cachingUtil;
    protected array $defaultCacheTags;
    protected int $defaultCacheExpiration;

    public function __construct()
    {
        $this->defaultCacheTags = ['game', 'larautilx'];
        $this->defaultCacheExpiration = 300;  // 5 minutes
        $this->cachingUtil = new CachingUtil($this->defaultCacheExpiration, $this->defaultCacheTags);
    }

    /**
     * Apply advanced filtering to a collection using FilteringUtil
     *
     * @param Collection $collection
     * @param array $filters
     * @return Collection
     */
    public function applyAdvancedFilters(Collection $collection, array $filters): Collection
    {
        foreach ($filters as $filter) {
            if (isset($filter['field'], $filter['operator'], $filter['value'])) {
                $collection = FilteringUtil::filter(
                    $collection,
                    $filter['field'],
                    $filter['operator'],
                    $filter['value']
                );
            }
        }

        return $collection;
    }

    /**
     * Create paginated response using PaginationUtil
     *
     * @param array $items
     * @param int $perPage
     * @param int $currentPage
     * @param array $options
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function createPaginatedResponse(array $items, int $perPage = 15, int $currentPage = 1, array $options = []): \Illuminate\Pagination\LengthAwarePaginator
    {
        return PaginationUtil::paginate($items, $perPage, $currentPage, $options);
    }

    /**
     * Cache data with game-specific tags
     *
     * @param string $key
     * @param callable $callback
     * @param int|null $expiration
     * @param array|null $tags
     * @return mixed
     */
    public function cacheGameData(string $key, callable $callback, ?int $expiration = null, ?array $tags = null)
    {
        $expiration = $expiration ?? $this->defaultCacheExpiration;
        $tags = $tags ?? $this->defaultCacheTags;

        return $this->cachingUtil->cache($key, $callback, $expiration, $tags);
    }

    /**
     * Cache player-specific data
     *
     * @param int $playerId
     * @param string $key
     * @param callable $callback
     * @param int|null $expiration
     * @return mixed
     */
    public function cachePlayerData(int $playerId, string $key, callable $callback, ?int $expiration = null)
    {
        $tags = array_merge($this->defaultCacheTags, ['player', "player_{$playerId}"]);
        return $this->cacheGameData("player_{$playerId}_{$key}", $callback, $expiration, $tags);
    }

    /**
     * Cache world-specific data
     *
     * @param int $worldId
     * @param string $key
     * @param callable $callback
     * @param int|null $expiration
     * @return mixed
     */
    public function cacheWorldData(int $worldId, string $key, callable $callback, ?int $expiration = null)
    {
        $tags = array_merge($this->defaultCacheTags, ['world', "world_{$worldId}"]);
        return $this->cacheGameData("world_{$worldId}_{$key}", $callback, $expiration, $tags);
    }

    /**
     * Cache village-specific data
     *
     * @param int $villageId
     * @param string $key
     * @param callable $callback
     * @param int|null $expiration
     * @return mixed
     */
    public function cacheVillageData(int $villageId, string $key, callable $callback, ?int $expiration = null)
    {
        $tags = array_merge($this->defaultCacheTags, ['village', "village_{$villageId}"]);
        return $this->cacheGameData("village_{$villageId}_{$key}", $callback, $expiration, $tags);
    }

    /**
     * Clear cache by tags
     *
     * @param array $tags
     * @return void
     */
    public function clearCacheByTags(array $tags): void
    {
        if (\Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            \Cache::tags($tags)->flush();
        }
    }

    /**
     * Clear player-specific cache
     *
     * @param int $playerId
     * @return void
     */
    public function clearPlayerCache(int $playerId): void
    {
        $this->clearCacheByTags(['player', "player_{$playerId}"]);
    }

    /**
     * Clear world-specific cache
     *
     * @param int $worldId
     * @return void
     */
    public function clearWorldCache(int $worldId): void
    {
        $this->clearCacheByTags(['world', "world_{$worldId}"]);
    }

    /**
     * Clear village-specific cache
     *
     * @param int $villageId
     * @return void
     */
    public function clearVillageCache(int $villageId): void
    {
        $this->clearCacheByTags(['village', "village_{$villageId}"]);
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getCacheStats(): array
    {
        return [
            'default_expiration' => $this->defaultCacheExpiration,
            'default_tags' => $this->defaultCacheTags,
            'cache_store' => get_class(\Cache::getStore()),
            'supports_tags' => \Cache::getStore() instanceof \Illuminate\Cache\TaggableStore,
        ];
    }

    /**
     * Create standardized API response
     *
     * @param mixed $data
     * @param string $message
     * @param int $statusCode
     * @param array $meta
     * @return array
     */
    public function createApiResponse(mixed $data = null, string $message = 'Success', int $statusCode = 200, array $meta = []): array
    {
        return [
            'success' => $statusCode >= 200 && $statusCode < 300,
            'message' => $message,
            'data' => $data,
            'meta' => array_merge($meta, [
                'timestamp' => now()->toISOString(),
                'larautilx_version' => '1.1.6',
            ]),
            'status_code' => $statusCode,
        ];
    }

    /**
     * Create paginated API response
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $paginator
     * @param string $message
     * @return array
     */
    public function createPaginatedApiResponse(\Illuminate\Pagination\LengthAwarePaginator $paginator, string $message = 'Data fetched successfully'): array
    {
        return $this->createApiResponse(
            $paginator->items(),
            $message,
            200,
            [
                'pagination' => [
                    'total' => $paginator->total(),
                    'count' => $paginator->count(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'total_pages' => $paginator->lastPage(),
                    'has_more_pages' => $paginator->hasMorePages(),
                ]
            ]
        );
    }

    /**
     * Create error API response
     *
     * @param string $message
     * @param int $statusCode
     * @param array $errors
     * @param mixed $debug
     * @return array
     */
    public function createErrorResponse(string $message = 'Error occurred', int $statusCode = 500, array $errors = [], mixed $debug = null): array
    {
        $response = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'meta' => [
                'timestamp' => now()->toISOString(),
                'larautilx_version' => '1.1.6',
            ],
            'status_code' => $statusCode,
        ];

        if (config('app.debug') && $debug !== null) {
            $response['debug'] = $debug;
        }

        return $response;
    }

    /**
     * Validate and sanitize filter parameters
     *
     * @param array $filters
     * @return array
     */
    public function validateFilters(array $filters): array
    {
        $validatedFilters = [];

        foreach ($filters as $filter) {
            if (is_array($filter) && isset($filter['field'], $filter['operator'])) {
                $validatedFilter = [
                    'field' => $filter['field'],
                    'operator' => $filter['operator'],
                    'value' => $filter['value'] ?? null,
                ];

                // Validate operator
                $validOperators = ['equals', 'not_equals', 'contains', 'not_contains', 'starts_with', 'ends_with'];
                if (in_array($validatedFilter['operator'], $validOperators)) {
                    $validatedFilters[] = $validatedFilter;
                }
            }
        }

        return $validatedFilters;
    }

    /**
     * Get integration status and statistics
     *
     * @return array
     */
    public function getIntegrationStatus(): array
    {
        return [
            'larautilx_version' => '1.1.6',
            'integrated_components' => [
                'ApiResponseTrait' => true,
                'FilteringUtil' => true,
                'PaginationUtil' => true,
                'CachingUtil' => true,
                'FileProcessingTrait' => true,
                'AccessLogMiddleware' => true,
                'CrudController' => true,
                'GameValidationTrait' => true,
            ],
            'cache_stats' => $this->getCacheStats(),
            'active_middleware' => [
                'access.log' => class_exists(\LaraUtilX\Http\Middleware\AccessLogMiddleware::class),
            ],
            'created_controllers' => [
                'PlayerController' => class_exists(\App\Http\Controllers\Game\PlayerController::class),
                'VillageController' => class_exists(\App\Http\Controllers\Game\VillageController::class),
                'TaskController' => class_exists(\App\Http\Controllers\Game\TaskController::class),
            ],
            'created_services' => [
                'LarautilxIntegrationService' => class_exists(self::class),
                'GeographicService' => class_exists(\App\Services\GeographicService::class),
            ],
        ];
    }
}
