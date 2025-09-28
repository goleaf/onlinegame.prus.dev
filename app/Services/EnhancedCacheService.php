<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use SmartCache\Facades\SmartCache;

/**
 * Enhanced Cache Service for Laravel 12.29.0+ features
 * Implements performance-boosting caching mechanisms
 */
class EnhancedCacheService
{
    protected string $prefix;

    protected int $defaultTtl;

    protected array $compressionOptions;

    public function __construct()
    {
        $this->prefix = config('cache.prefix', 'game-cache-');
        $this->defaultTtl = 3600;  // 1 hour default
        $this->compressionOptions = [
            'serializer' => 'igbinary',
            'compression' => function_exists('lzf_compress') ? 'lzf' : 'none',
        ];
    }

    /**
     * Enhanced cache with SmartCache optimization
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $startTime = microtime(true);

        ds('EnhancedCacheService: Cache remember operation', [
            'service' => 'EnhancedCacheService',
            'method' => 'remember',
            'key' => $key,
            'full_key' => $this->prefix.$key,
            'ttl_seconds' => $ttl,
            'cache_time' => now(),
        ]);

        $fullKey = $this->prefix.$key;

        $result = SmartCache::remember($fullKey, now()->addSeconds($ttl), $callback);

        $operationTime = round((microtime(true) - $startTime) * 1000, 2);

        ds('EnhancedCacheService: Cache remember completed', [
            'key' => $key,
            'operation_time_ms' => $operationTime,
            'result_type' => gettype($result),
            'result_size' => is_string($result) ? strlen($result) : (is_array($result) ? count($result) : 'N/A'),
        ]);

        return $result;
    }

    /**
     * Cache with tags for better invalidation using SmartCache
     */
    public function rememberWithTags(string $key, array $tags, int $ttl, callable $callback): mixed
    {
        $fullKey = $this->prefix.$key;

        // SmartCache handles optimization automatically, tags are for reference
        return SmartCache::remember($fullKey, now()->addSeconds($ttl), $callback);
    }

    /**
     * Invalidate cache by tags
     */
    public function invalidateByTags(array $tags): void
    {
        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            Cache::tags($tags)->flush();
        }
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $startTime = microtime(true);

        ds('EnhancedCacheService: Getting cache statistics', [
            'service' => 'EnhancedCacheService',
            'method' => 'getStats',
            'stats_time' => now(),
        ]);

        try {
            $redis = Redis::connection('cache');
            $info = $redis->info();

            $stats = [
                'memory_used' => $info['used_memory_human'] ?? 'N/A',
                'keys_count' => $redis->dbsize(),
                'hit_rate' => $this->calculateHitRate(),
                'compression_enabled' => true,
            ];

            $statsTime = round((microtime(true) - $startTime) * 1000, 2);

            ds('EnhancedCacheService: Cache statistics retrieved', [
                'memory_used' => $stats['memory_used'],
                'keys_count' => $stats['keys_count'],
                'hit_rate' => $stats['hit_rate'],
                'stats_time_ms' => $statsTime,
            ]);

            return $stats;
        } catch (\Exception $e) {
            $statsTime = round((microtime(true) - $startTime) * 1000, 2);

            ds('EnhancedCacheService: Cache statistics failed', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'stats_time_ms' => $statsTime,
            ]);

            return [
                'error' => 'Unable to retrieve cache statistics',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Warm up cache with frequently accessed data
     */
    public function warmUp(array $keys): void
    {
        foreach ($keys as $key => $callback) {
            if (! Cache::has($this->prefix.$key)) {
                $this->remember($key, $this->defaultTtl, $callback);
            }
        }
    }

    /**
     * Compress data for better memory usage
     */
    protected function compressData(mixed $data): mixed
    {
        if (function_exists('igbinary_serialize')) {
            $serialized = igbinary_serialize($data);

            if (function_exists('lzf_compress')) {
                return lzf_compress($serialized);
            }

            return $serialized;
        }

        return $data;
    }

    /**
     * Decompress data
     */
    protected function decompressData(mixed $data): mixed
    {
        if (function_exists('igbinary_unserialize')) {
            if (function_exists('lzf_decompress')) {
                try {
                    $decompressed = lzf_decompress($data);

                    return igbinary_unserialize($decompressed);
                } catch (\Exception $e) {
                    // Fallback to direct unserialize
                    return igbinary_unserialize($data);
                }
            }

            return igbinary_unserialize($data);
        }

        return $data;
    }

    /**
     * Calculate cache hit rate
     */
    protected function calculateHitRate(): float
    {
        try {
            $redis = Redis::connection('cache');
            $info = $redis->info();

            $hits = $info['keyspace_hits'] ?? 0;
            $misses = $info['keyspace_misses'] ?? 0;
            $total = $hits + $misses;

            return $total > 0 ? round(($hits / $total) * 100, 2) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
