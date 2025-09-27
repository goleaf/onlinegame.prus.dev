<?php

namespace App\Services;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\Alliance;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GameCacheService
{
    protected $defaultTtl = 3600; // 1 hour
    protected $shortTtl = 300; // 5 minutes
    protected $longTtl = 86400; // 24 hours

    /**
     * Cache player data
     */
    public function cachePlayerData(Player $player): bool
    {
        try {
            $cacheKey = "player_data_{$player->id}";
            $data = [
                'player' => $player->toArray(),
                'villages' => $player->villages()->with(['buildings', 'resources', 'troops'])->get()->toArray(),
                'alliance' => $player->alliance ? $player->alliance->toArray() : null,
                'cached_at' => now()->toISOString(),
            ];

            Cache::put($cacheKey, $data, $this->defaultTtl);

            Log::info('Player data cached', [
                'player_id' => $player->id,
                'cache_key' => $cacheKey,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to cache player data', [
                'player_id' => $player->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Cache village data
     */
    public function cacheVillageData(Village $village): bool
    {
        try {
            $cacheKey = "village_data_{$village->id}";
            $data = [
                'village' => $village->toArray(),
                'buildings' => $village->buildings()->with('buildingType')->get()->toArray(),
                'resources' => $village->resources()->get()->toArray(),
                'troops' => $village->troops()->with('unitType')->get()->toArray(),
                'cached_at' => now()->toISOString(),
            ];

            Cache::put($cacheKey, $data, $this->defaultTtl);

            Log::info('Village data cached', [
                'village_id' => $village->id,
                'cache_key' => $cacheKey,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to cache village data', [
                'village_id' => $village->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Cache alliance data
     */
    public function cacheAllianceData(Alliance $alliance): bool
    {
        try {
            $cacheKey = "alliance_data_{$alliance->id}";
            $data = [
                'alliance' => $alliance->toArray(),
                'members' => $alliance->members()->with('user')->get()->toArray(),
                'statistics' => $this->getAllianceStatistics($alliance),
                'cached_at' => now()->toISOString(),
            ];

            Cache::put($cacheKey, $data, $this->defaultTtl);

            Log::info('Alliance data cached', [
                'alliance_id' => $alliance->id,
                'cache_key' => $cacheKey,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to cache alliance data', [
                'alliance_id' => $alliance->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get cached player data
     */
    public function getCachedPlayerData(int $playerId): ?array
    {
        try {
            $cacheKey = "player_data_{$playerId}";
            $data = Cache::get($cacheKey);

            if ($data) {
                Log::info('Player data retrieved from cache', [
                    'player_id' => $playerId,
                    'cache_key' => $cacheKey,
                ]);
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Failed to get cached player data', [
                'player_id' => $playerId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get cached village data
     */
    public function getCachedVillageData(int $villageId): ?array
    {
        try {
            $cacheKey = "village_data_{$villageId}";
            $data = Cache::get($cacheKey);

            if ($data) {
                Log::info('Village data retrieved from cache', [
                    'village_id' => $villageId,
                    'cache_key' => $cacheKey,
                ]);
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Failed to get cached village data', [
                'village_id' => $villageId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get cached alliance data
     */
    public function getCachedAllianceData(int $allianceId): ?array
    {
        try {
            $cacheKey = "alliance_data_{$allianceId}";
            $data = Cache::get($cacheKey);

            if ($data) {
                Log::info('Alliance data retrieved from cache', [
                    'alliance_id' => $allianceId,
                    'cache_key' => $cacheKey,
                ]);
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Failed to get cached alliance data', [
                'alliance_id' => $allianceId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Clear player cache
     */
    public function clearPlayerCache(int $userId): bool
    {
        try {
            $player = Player::where('user_id', $userId)->first();
            if (!$player) {
                return false;
            }

            $cacheKey = "player_data_{$player->id}";
            Cache::forget($cacheKey);

            Log::info('Player cache cleared', [
                'user_id' => $userId,
                'player_id' => $player->id,
                'cache_key' => $cacheKey,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to clear player cache', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Clear village cache
     */
    public function clearVillageCache(int $villageId): bool
    {
        try {
            $cacheKey = "village_data_{$villageId}";
            Cache::forget($cacheKey);

            Log::info('Village cache cleared', [
                'village_id' => $villageId,
                'cache_key' => $cacheKey,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to clear village cache', [
                'village_id' => $villageId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Clear alliance cache
     */
    public function clearAllianceCache(int $allianceId): bool
    {
        try {
            $cacheKey = "alliance_data_{$allianceId}";
            Cache::forget($cacheKey);

            Log::info('Alliance cache cleared', [
                'alliance_id' => $allianceId,
                'cache_key' => $cacheKey,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to clear alliance cache', [
                'alliance_id' => $allianceId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Cache game statistics
     */
    public function cacheGameStatistics(array $statistics): bool
    {
        try {
            $cacheKey = 'game_statistics';
            $data = [
                'statistics' => $statistics,
                'cached_at' => now()->toISOString(),
            ];

            Cache::put($cacheKey, $data, $this->shortTtl);

            Log::info('Game statistics cached', [
                'cache_key' => $cacheKey,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to cache game statistics', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get cached game statistics
     */
    public function getCachedGameStatistics(): ?array
    {
        try {
            $cacheKey = 'game_statistics';
            $data = Cache::get($cacheKey);

            if ($data) {
                Log::info('Game statistics retrieved from cache', [
                    'cache_key' => $cacheKey,
                ]);
            }

            return $data;

        } catch (\Exception $e) {
            Log::error('Failed to get cached game statistics', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        try {
            $stats = [
                'cache_driver' => config('cache.default'),
                'cache_prefix' => config('cache.prefix'),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'cache_hits' => Cache::getStore()->getStats()['hits'] ?? 0,
                'cache_misses' => Cache::getStore()->getStats()['misses'] ?? 0,
            ];

            return $stats;

        } catch (\Exception $e) {
            Log::error('Failed to get cache statistics', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Cleanup cache
     */
    public function cleanup(): array
    {
        try {
            $cleaned = [
                'player_cache_cleared' => 0,
                'village_cache_cleared' => 0,
                'alliance_cache_cleared' => 0,
                'statistics_cache_cleared' => 0,
            ];

            // Clear old player caches
            $playerKeys = Cache::getStore()->getStats()['keys'] ?? [];
            foreach ($playerKeys as $key) {
                if (str_starts_with($key, 'player_data_')) {
                    Cache::forget($key);
                    $cleaned['player_cache_cleared']++;
                }
            }

            // Clear old village caches
            foreach ($playerKeys as $key) {
                if (str_starts_with($key, 'village_data_')) {
                    Cache::forget($key);
                    $cleaned['village_cache_cleared']++;
                }
            }

            // Clear old alliance caches
            foreach ($playerKeys as $key) {
                if (str_starts_with($key, 'alliance_data_')) {
                    Cache::forget($key);
                    $cleaned['alliance_cache_cleared']++;
                }
            }

            // Clear statistics cache
            Cache::forget('game_statistics');
            $cleaned['statistics_cache_cleared'] = 1;

            Log::info('Cache cleanup completed', $cleaned);

            return $cleaned;

        } catch (\Exception $e) {
            Log::error('Failed to cleanup cache', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get alliance statistics
     */
    private function getAllianceStatistics(Alliance $alliance): array
    {
        try {
            return [
                'member_count' => $alliance->members()->count(),
                'total_population' => $alliance->members()->sum('population'),
                'total_points' => $alliance->members()->sum('points'),
                'average_points' => $alliance->members()->avg('points'),
                'created_at' => $alliance->created_at,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get alliance statistics', [
                'alliance_id' => $alliance->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}