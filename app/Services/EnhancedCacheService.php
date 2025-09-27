<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

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
     * Enhanced cache with compression and serialization
     */
    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $fullKey = $this->prefix . $key;

        return Cache::store('redis')->remember($fullKey, $ttl, function () use ($callback) {
            $data = $callback();

            // Apply compression for large data
            if (is_array($data) && count($data) > 100) {
                $data = $this->compressData($data);
            }

            return $data;
        });
    }

    /**
     * Cache with tags for better invalidation
     */
    public function rememberWithTags(string $key, array $tags, int $ttl, callable $callback): mixed
    {
        $fullKey = $this->prefix . $key;

        if (Cache::getStore() instanceof \Illuminate\Cache\TaggableStore) {
            return Cache::tags($tags)->remember($fullKey, $ttl, $callback);
        }

        return $this->remember($key, $ttl, $callback);
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
        try {
            $redis = Redis::connection('cache');
            $info = $redis->info();

            return [
                'memory_used' => $info['used_memory_human'] ?? 'N/A',
                'keys_count' => $redis->dbsize(),
                'hit_rate' => $this->calculateHitRate(),
                'compression_enabled' => true,
            ];
        } catch (\Exception $e) {
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
            if (!Cache::has($this->prefix . $key)) {
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
        if (function_exists('lzf_decompress') && function_exists('igbinary_unserialize')) {
            $decompressed = lzf_decompress($data);
            return igbinary_unserialize($decompressed);
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
