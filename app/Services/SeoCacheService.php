<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\LoggingUtil;
use SmartCache\Facades\SmartCache;

class SeoCacheService
{
    protected string $cachePrefix = 'seo_metadata_';
    protected int $cacheTtl = 3600;  // 1 hour

    /**
     * Get cached SEO metadata with SmartCache optimization
     */
    public function get(string $key): ?array
    {
        try {
            return SmartCache::remember($this->cachePrefix . $key, now()->addHour(), function () use ($key) {
                return Cache::get($this->cachePrefix . $key);
            });
        } catch (\Exception $e) {
            Log::warning("SEO cache get failed for key: {$key}", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Cache SEO metadata with SmartCache optimization
     */
    public function put(string $key, array $data, int $ttl = null): bool
    {
        try {
            $ttl = $ttl ?? $this->cacheTtl;
            SmartCache::remember($this->cachePrefix . $key, now()->addSeconds($ttl), function () use ($data) {
                return $data;
            });
            return true;
        } catch (\Exception $e) {
            Log::warning("SEO cache put failed for key: {$key}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Generate cache key for SEO metadata
     */
    public function generateKey(string $type, array $params = []): string
    {
        $key = $type;

        if (!empty($params)) {
            $key .= '_' . md5(serialize($params));
        }

        return $key;
    }

    /**
     * Cache game page SEO metadata
     */
    public function cacheGamePageSeo(string $page, array $data): bool
    {
        $key = $this->generateKey('game_page', ['page' => $page, 'data' => $data]);

        $seoData = [
            'page' => $page,
            'data' => $data,
            'cached_at' => now(),
            'expires_at' => now()->addSeconds($this->cacheTtl)
        ];

        return $this->put($key, $seoData);
    }

    /**
     * Get cached game page SEO metadata
     */
    public function getCachedGamePageSeo(string $page, array $data): ?array
    {
        $key = $this->generateKey('game_page', ['page' => $page, 'data' => $data]);
        return $this->get($key);
    }

    /**
     * Cache sitemap data
     */
    public function cacheSitemap(array $urls): bool
    {
        $sitemapData = [
            'urls' => $urls,
            'generated_at' => now(),
            'count' => count($urls)
        ];

        return $this->put('sitemap', $sitemapData, 86400);  // 24 hours
    }

    /**
     * Get cached sitemap data
     */
    public function getCachedSitemap(): ?array
    {
        return $this->get('sitemap');
    }

    /**
     * Clear all SEO cache
     */
    public function clearAll(): bool
    {
        try {
            Cache::flush();
            Log::info('SEO cache cleared successfully');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to clear SEO cache', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Clear specific SEO cache
     */
    public function clear(string $key): bool
    {
        try {
            Cache::forget($this->cachePrefix . $key);
            return true;
        } catch (\Exception $e) {
            Log::warning("Failed to clear SEO cache for key: {$key}", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        try {
            $keys = Cache::get('seo_cache_keys', []);

            return [
                'total_keys' => count($keys),
                'cache_prefix' => $this->cachePrefix,
                'default_ttl' => $this->cacheTtl,
                'last_cleared' => Cache::get('seo_cache_last_cleared'),
                'memory_usage' => memory_get_usage(true),
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to get SEO cache stats', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Warm up SEO cache
     */
    public function warmUp(): bool
    {
        try {
            // Cache common SEO metadata
            $this->cacheGamePageSeo('index', []);
            $this->cacheGamePageSeo('features', []);

            // Cache sitemap
            $this->cacheSitemap([]);

            Log::info('SEO cache warmed up successfully');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to warm up SEO cache', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
