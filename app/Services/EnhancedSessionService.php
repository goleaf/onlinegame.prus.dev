<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;

/**
 * Enhanced Session Service for Laravel 12.29.0+ features
 * Implements performance-boosting session drivers
 */
class EnhancedSessionService
{
    protected string $prefix;
    protected int $defaultLifetime;
    protected array $compressionOptions;

    public function __construct()
    {
        $this->prefix = config('session.cookie', 'game-session-');
        $this->defaultLifetime = config('session.lifetime', 240);
        $this->compressionOptions = [
            'serializer' => 'igbinary',
            'compression' => function_exists('lzf_compress') ? 'lzf' : 'none',
        ];
    }

    /**
     * Enhanced session storage with compression
     */
    public function put(string $key, mixed $value): void
    {
        $compressedValue = $this->compressData($value);
        Session::put($key, $compressedValue);
    }

    /**
     * Get session data with decompression
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = Session::get($key, $default);

        if ($value !== $default && $this->isCompressed($value)) {
            return $this->decompressData($value);
        }

        return $value;
    }

    /**
     * Store session data with tags for better organization
     */
    public function putWithTags(string $key, mixed $value, array $tags = []): void
    {
        $this->put($key, $value);

        // Store tags for this session key
        if (!empty($tags)) {
            $tagKey = "session_tags:{$key}";
            Session::put($tagKey, $tags);
        }
    }

    /**
     * Get session statistics
     */
    public function getStats(): array
    {
        try {
            $redis = Redis::connection('session');
            $info = $redis->info();

            return [
                'session_count' => $this->getSessionCount(),
                'memory_used' => $info['used_memory_human'] ?? 'N/A',
                'lifetime' => $this->defaultLifetime,
                'compression_enabled' => true,
                'driver' => config('session.driver'),
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Unable to retrieve session statistics',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions(): int
    {
        try {
            $redis = Redis::connection('session');
            $pattern = $this->prefix . '*';
            $keys = $redis->keys($pattern);
            $cleaned = 0;

            foreach ($keys as $key) {
                $ttl = $redis->ttl($key);
                if ($ttl === -1 || $ttl === -2) {
                    $redis->del($key);
                    $cleaned++;
                }
            }

            return $cleaned;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Regenerate session ID with enhanced security
     */
    public function regenerateId(): void
    {
        Session::regenerate();

        // Add security headers
        $this->addSecurityHeaders();
    }

    /**
     * Get session data by tags
     */
    public function getByTags(array $tags): array
    {
        $results = [];

        foreach ($tags as $tag) {
            $tagKey = "session_tags:{$tag}";
            $taggedKeys = Session::get($tagKey, []);

            foreach ($taggedKeys as $key) {
                $results[$key] = $this->get($key);
            }
        }

        return $results;
    }

    /**
     * Compress session data
     */
    protected function compressData(mixed $data): mixed
    {
        if (function_exists('igbinary_serialize')) {
            $serialized = igbinary_serialize($data);

            if (function_exists('lzf_compress')) {
                return base64_encode(lzf_compress($serialized));
            }

            return base64_encode($serialized);
        }

        return $data;
    }

    /**
     * Decompress session data
     */
    protected function decompressData(mixed $data): mixed
    {
        if (function_exists('igbinary_unserialize')) {
            try {
                $decoded = base64_decode($data);

                if (function_exists('lzf_decompress')) {
                    $decompressed = lzf_decompress($decoded);
                    return igbinary_unserialize($decompressed);
                }

                return igbinary_unserialize($decoded);
            } catch (\Exception $e) {
                return $data;
            }
        }

        return $data;
    }

    /**
     * Check if data is compressed
     */
    protected function isCompressed(mixed $data): bool
    {
        return is_string($data) && base64_encode(base64_decode($data, true)) === $data;
    }

    /**
     * Get session count
     */
    protected function getSessionCount(): int
    {
        try {
            $redis = Redis::connection('session');
            $pattern = $this->prefix . '*';
            $keys = $redis->keys($pattern);
            return count($keys);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Add security headers for session
     */
    protected function addSecurityHeaders(): void
    {
        if (!headers_sent()) {
            header('X-Session-Security: Enhanced');
            header('X-Session-Compression: Enabled');
        }
    }
}
