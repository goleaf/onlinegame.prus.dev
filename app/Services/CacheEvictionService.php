<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class CacheEvictionService
{
    /**
     * Evict expired cache items from all configured stores
     *
     * @return array
     */
    public function evictAllStores(): array
    {
        $results = [];
        $stores = config('cache.stores', []);
        
        foreach ($stores as $storeName => $storeConfig) {
            if (in_array($storeName, ['array', 'null'])) {
                continue; // Skip non-persistent stores
            }
            
            try {
                $results[$storeName] = $this->evictStore($storeName);
            } catch (\Exception $e) {
                Log::error("Failed to evict cache store '{$storeName}': " . $e->getMessage());
                $results[$storeName] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'items_removed' => 0,
                    'size_freed' => '0 B'
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Evict expired cache items from a specific store
     *
     * @param string $storeName
     * @return array
     */
    public function evictStore(string $storeName): array
    {
        $startTime = microtime(true);
        
        switch ($storeName) {
            case 'database':
                return $this->evictDatabaseStore($startTime);
            case 'file':
                return $this->evictFileStore($startTime);
            case 'redis':
                return $this->evictRedisStore($startTime);
            default:
                // Use the package's built-in command for other stores
                return $this->evictViaCommand($storeName, $startTime);
        }
    }
    
    /**
     * Evict expired items from database cache store
     *
     * @param float $startTime
     * @return array
     */
    private function evictDatabaseStore(float $startTime): array
    {
        $table = config('cache.stores.database.table', 'cache');
        $prefix = config('cache.prefix', '');
        
        // Count total items before eviction
        $totalBefore = DB::table($table)->count();
        
        // Remove expired items
        $expiredCount = DB::table($table)
            ->where('expiration', '<', time())
            ->delete();
        
        // Count remaining items
        $totalAfter = DB::table($table)->count();
        
        $duration = microtime(true) - $startTime;
        
        return [
            'success' => true,
            'items_removed' => $expiredCount,
            'items_before' => $totalBefore,
            'items_after' => $totalAfter,
            'duration' => round($duration, 6),
            'size_freed' => $this->estimateSizeFreed($expiredCount)
        ];
    }
    
    /**
     * Evict expired items from file cache store
     *
     * @param float $startTime
     * @return array
     */
    private function evictFileStore(float $startTime): array
    {
        $path = config('cache.stores.file.path', storage_path('framework/cache/data'));
        $itemsRemoved = 0;
        $sizeFreed = 0;
        
        if (!is_dir($path)) {
            return [
                'success' => true,
                'items_removed' => 0,
                'size_freed' => '0 B',
                'duration' => round(microtime(true) - $startTime, 6)
            ];
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $content = file_get_contents($file->getPathname());
                
                try {
                    $data = unserialize($content);
                    
                    // Check if the cache item is expired
                    if (isset($data['expires']) && $data['expires'] < time()) {
                        $sizeFreed += $file->getSize();
                        unlink($file->getPathname());
                        $itemsRemoved++;
                    }
                } catch (\Exception $e) {
                    // Skip files that can't be unserialized (corrupted or invalid format)
                    continue;
                }
            }
        }
        
        $duration = microtime(true) - $startTime;
        
        return [
            'success' => true,
            'items_removed' => $itemsRemoved,
            'size_freed' => $this->formatBytes($sizeFreed),
            'duration' => round($duration, 6)
        ];
    }
    
    /**
     * Evict expired items from Redis cache store
     *
     * @param float $startTime
     * @return array
     */
    private function evictRedisStore(float $startTime): array
    {
        $redis = Cache::store('redis');
        $prefix = config('cache.prefix', '');
        
        // Redis automatically handles expiration, but we can clean up manually
        $keys = $redis->getRedis()->keys($prefix . '*');
        $itemsRemoved = 0;
        
        foreach ($keys as $key) {
            $ttl = $redis->getRedis()->ttl($key);
            if ($ttl === -1) { // Key exists but has no expiration
                // Skip keys without expiration
                continue;
            } elseif ($ttl === -2) { // Key doesn't exist (expired)
                $itemsRemoved++;
            }
        }
        
        $duration = microtime(true) - $startTime;
        
        return [
            'success' => true,
            'items_removed' => $itemsRemoved,
            'size_freed' => $this->estimateSizeFreed($itemsRemoved),
            'duration' => round($duration, 6)
        ];
    }
    
    /**
     * Use the package's built-in command for eviction
     *
     * @param string $storeName
     * @param float $startTime
     * @return array
     */
    private function evictViaCommand(string $storeName, float $startTime): array
    {
        try {
            Artisan::call('cache:evict', ['target' => $storeName]);
            $output = Artisan::output();
            
            $duration = microtime(true) - $startTime;
            
            return [
                'success' => true,
                'items_removed' => $this->parseItemsRemoved($output),
                'size_freed' => $this->parseSizeFreed($output),
                'duration' => round($duration, 6),
                'output' => trim($output)
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'duration' => round(microtime(true) - $startTime, 6)
            ];
        }
    }
    
    /**
     * Get cache statistics for all stores
     *
     * @return array
     */
    public function getCacheStats(): array
    {
        $stats = [];
        $stores = config('cache.stores', []);
        
        foreach ($stores as $storeName => $storeConfig) {
            if (in_array($storeName, ['array', 'null'])) {
                continue;
            }
            
            try {
                $stats[$storeName] = $this->getStoreStats($storeName);
            } catch (\Exception $e) {
                $stats[$storeName] = [
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $stats;
    }
    
    /**
     * Get statistics for a specific store
     *
     * @param string $storeName
     * @return array
     */
    private function getStoreStats(string $storeName): array
    {
        switch ($storeName) {
            case 'database':
                $table = config('cache.stores.database.table', 'cache');
                $total = DB::table($table)->count();
                $expired = DB::table($table)->where('expiration', '<', time())->count();
                
                return [
                    'total_items' => $total,
                    'expired_items' => $expired,
                    'active_items' => $total - $expired,
                    'driver' => 'database'
                ];
                
            case 'file':
                $path = config('cache.stores.file.path', storage_path('framework/cache/data'));
                $total = 0;
                $size = 0;
                
                if (is_dir($path)) {
                    $iterator = new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
                    );
                    
                    foreach ($iterator as $file) {
                        if ($file->isFile()) {
                            $total++;
                            $size += $file->getSize();
                        }
                    }
                }
                
                return [
                    'total_items' => $total,
                    'total_size' => $this->formatBytes($size),
                    'driver' => 'file'
                ];
                
            case 'redis':
                $redis = Cache::store('redis');
                $prefix = config('cache.prefix', '');
                $keys = $redis->getRedis()->keys($prefix . '*');
                
                return [
                    'total_items' => count($keys),
                    'driver' => 'redis'
                ];
                
            default:
                return [
                    'driver' => $storeName,
                    'note' => 'Statistics not available for this driver'
                ];
        }
    }
    
    /**
     * Estimate size freed based on number of items
     *
     * @param int $itemCount
     * @return string
     */
    private function estimateSizeFreed(int $itemCount): string
    {
        // Rough estimate: 1KB per cache item
        $estimatedBytes = $itemCount * 1024;
        return $this->formatBytes($estimatedBytes);
    }
    
    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
    
    /**
     * Parse items removed from command output
     *
     * @param string $output
     * @return int
     */
    private function parseItemsRemoved(string $output): int
    {
        if (preg_match('/Removed (\d+) expired/', $output, $matches)) {
            return (int) $matches[1];
        }
        return 0;
    }
    
    /**
     * Parse size freed from command output
     *
     * @param string $output
     * @return string
     */
    private function parseSizeFreed(string $output): string
    {
        if (preg_match('/Estimated total size: ([^\s]+)/', $output, $matches)) {
            return $matches[1];
        }
        return '0 B';
    }
}
