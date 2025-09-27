<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class GameCacheService
{
    /**
     * Cache keys for different game data types
     */
    const CACHE_KEYS = [
        'player_data' => 'game:player:',
        'village_data' => 'game:village:',
        'alliance_data' => 'game:alliance:',
        'map_data' => 'game:map:',
        'battle_data' => 'game:battle:',
        'resource_data' => 'game:resource:',
        'statistics' => 'game:stats:',
        'leaderboard' => 'game:leaderboard:',
    ];

    /**
     * Cache durations in seconds
     */
    const CACHE_DURATIONS = [
        'player_data' => 300,  // 5 minutes
        'village_data' => 60,  // 1 minute
        'alliance_data' => 600,  // 10 minutes
        'map_data' => 1800,  // 30 minutes
        'battle_data' => 120,  // 2 minutes
        'resource_data' => 30,  // 30 seconds
        'statistics' => 3600,  // 1 hour
        'leaderboard' => 1800,  // 30 minutes
    ];

    /**
     * Get player data with caching
     */
    public static function getPlayerData(int $playerId, callable $callback = null)
    {
        $cacheKey = self::CACHE_KEYS['player_data'] . $playerId;

        return Cache::remember($cacheKey, self::CACHE_DURATIONS['player_data'], function () use ($playerId, $callback) {
            if ($callback) {
                return $callback($playerId);
            }

            return \App\Models\Game\Player::with(['villages', 'alliance'])
                ->find($playerId);
        });
    }

    /**
     * Get village data with caching
     */
    public static function getVillageData(int $villageId, callable $callback = null)
    {
        $cacheKey = self::CACHE_KEYS['village_data'] . $villageId;

        return Cache::remember($cacheKey, self::CACHE_DURATIONS['village_data'], function () use ($villageId, $callback) {
            if ($callback) {
                return $callback($villageId);
            }

            return \App\Models\Game\Village::with(['buildings', 'units', 'player'])
                ->find($villageId);
        });
    }

    /**
     * Get alliance data with caching
     */
    public static function getAllianceData(int $allianceId, callable $callback = null)
    {
        $cacheKey = self::CACHE_KEYS['alliance_data'] . $allianceId;

        return Cache::remember($cacheKey, self::CACHE_DURATIONS['alliance_data'], function () use ($allianceId, $callback) {
            if ($callback) {
                return $callback($allianceId);
            }

            return \App\Models\Game\Alliance::with(['members'])
                ->find($allianceId);
        });
    }

    /**
     * Get map data with caching
     */
    public static function getMapData(float $lat, float $lon, int $radius = 10)
    {
        $cacheKey = self::CACHE_KEYS['map_data'] . round($lat, 2) . '_' . round($lon, 2) . '_' . $radius;

        return Cache::remember($cacheKey, self::CACHE_DURATIONS['map_data'], function () use ($lat, $lon, $radius) {
            return \App\Models\Game\Village::whereBetween('lat', [$lat - $radius, $lat + $radius])
                ->whereBetween('lon', [$lon - $radius, $lon + $radius])
                ->with(['player'])
                ->get();
        });
    }

    /**
     * Get battle data with caching
     */
    public static function getBattleData(int $battleId, callable $callback = null)
    {
        $cacheKey = self::CACHE_KEYS['battle_data'] . $battleId;

        return Cache::remember($cacheKey, self::CACHE_DURATIONS['battle_data'], function () use ($battleId, $callback) {
            if ($callback) {
                return $callback($battleId);
            }

            return \App\Models\Game\Battle::with(['attacker', 'defender', 'attackerVillage', 'defenderVillage'])
                ->find($battleId);
        });
    }

    /**
     * Get resource data with caching
     */
    public static function getResourceData(int $villageId)
    {
        $cacheKey = self::CACHE_KEYS['resource_data'] . $villageId;

        return Cache::remember($cacheKey, self::CACHE_DURATIONS['resource_data'], function () use ($villageId) {
            $village = \App\Models\Game\Village::find($villageId);

            if (!$village) {
                return null;
            }

            return [
                'wood' => $village->wood,
                'clay' => $village->clay,
                'iron' => $village->iron,
                'crop' => $village->crop,
                'storage_capacity' => $village->storage_capacity,
                'production_rate' => $village->production_rate,
                'last_updated' => $village->updated_at,
            ];
        });
    }

    /**
     * Get game statistics with caching
     */
    public static function getGameStatistics(string $type = 'general')
    {
        $cacheKey = self::CACHE_KEYS['statistics'] . $type;

        return Cache::remember($cacheKey, self::CACHE_DURATIONS['statistics'], function () use ($type) {
            switch ($type) {
                case 'players':
                    return [
                        'total_players' => \App\Models\Game\Player::count(),
                        'active_players' => \App\Models\Game\Player::where('last_activity_at', '>=', now()->subDays(7))->count(),
                        'new_players_today' => \App\Models\Game\Player::whereDate('created_at', today())->count(),
                    ];

                case 'villages':
                    return [
                        'total_villages' => \App\Models\Game\Village::count(),
                        'active_villages' => \App\Models\Game\Village::where('updated_at', '>=', now()->subDays(1))->count(),
                        'average_villages_per_player' => \App\Models\Game\Player::withCount('villages')->avg('villages_count'),
                    ];

                case 'alliances':
                    return [
                        'total_alliances' => \App\Models\Game\Alliance::count(),
                        'active_alliances' => \App\Models\Game\Alliance::where('updated_at', '>=', now()->subDays(7))->count(),
                        'average_members_per_alliance' => \App\Models\Game\Alliance::withCount('members')->avg('members_count'),
                    ];

                case 'battles':
                    return [
                        'total_battles' => \App\Models\Game\Battle::count(),
                        'battles_today' => \App\Models\Game\Battle::whereDate('created_at', today())->count(),
                        'battles_this_week' => \App\Models\Game\Battle::where('created_at', '>=', now()->subWeek())->count(),
                    ];

                default:
                    return [
                        'players' => self::getGameStatistics('players'),
                        'villages' => self::getGameStatistics('villages'),
                        'alliances' => self::getGameStatistics('alliances'),
                        'battles' => self::getGameStatistics('battles'),
                    ];
            }
        });
    }

    /**
     * Get leaderboard data with caching
     */
    public static function getLeaderboard(string $type = 'points', int $limit = 100)
    {
        $cacheKey = self::CACHE_KEYS['leaderboard'] . $type . '_' . $limit;

        return Cache::remember($cacheKey, self::CACHE_DURATIONS['leaderboard'], function () use ($type, $limit) {
            switch ($type) {
                case 'points':
                    return \App\Models\Game\Player::orderBy('points', 'desc')
                        ->limit($limit)
                        ->get(['id', 'name', 'points', 'rank']);

                case 'villages':
                    return \App\Models\Game\Player::withCount('villages')
                        ->orderBy('villages_count', 'desc')
                        ->limit($limit)
                        ->get(['id', 'name', 'villages_count']);

                case 'alliances':
                    return \App\Models\Game\Alliance::withCount('members')
                        ->orderBy('members_count', 'desc')
                        ->limit($limit)
                        ->get(['id', 'name', 'members_count']);

                default:
                    return [];
            }
        });
    }

    /**
     * Invalidate player cache
     */
    public static function invalidatePlayerCache(int $playerId): void
    {
        $cacheKey = self::CACHE_KEYS['player_data'] . $playerId;
        Cache::forget($cacheKey);

        // Also invalidate related caches
        self::invalidateVillageCachesForPlayer($playerId);
        self::invalidateStatisticsCache();
    }

    /**
     * Invalidate village cache
     */
    public static function invalidateVillageCache(int $villageId): void
    {
        $cacheKey = self::CACHE_KEYS['village_data'] . $villageId;
        Cache::forget($cacheKey);

        $resourceCacheKey = self::CACHE_KEYS['resource_data'] . $villageId;
        Cache::forget($resourceCacheKey);

        self::invalidateStatisticsCache();
    }

    /**
     * Invalidate alliance cache
     */
    public static function invalidateAllianceCache(int $allianceId): void
    {
        $cacheKey = self::CACHE_KEYS['alliance_data'] . $allianceId;
        Cache::forget($cacheKey);

        self::invalidateStatisticsCache();
    }

    /**
     * Invalidate battle cache
     */
    public static function invalidateBattleCache(int $battleId): void
    {
        $cacheKey = self::CACHE_KEYS['battle_data'] . $battleId;
        Cache::forget($cacheKey);

        self::invalidateStatisticsCache();
    }

    /**
     * Invalidate village caches for a specific player
     */
    private static function invalidateVillageCachesForPlayer(int $playerId): void
    {
        $villages = \App\Models\Game\Village::where('player_id', $playerId)->pluck('id');

        foreach ($villages as $villageId) {
            self::invalidateVillageCache($villageId);
        }
    }

    /**
     * Invalidate statistics cache
     */
    public static function invalidateStatisticsCache(): void
    {
        $patterns = [
            self::CACHE_KEYS['statistics'] . '*',
            self::CACHE_KEYS['leaderboard'] . '*',
        ];

        foreach ($patterns as $pattern) {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                Redis::del(Redis::keys($pattern));
            } else {
                // For other cache stores, we'll need to clear all stats
                Cache::forget(self::CACHE_KEYS['statistics'] . 'general');
                Cache::forget(self::CACHE_KEYS['statistics'] . 'players');
                Cache::forget(self::CACHE_KEYS['statistics'] . 'villages');
                Cache::forget(self::CACHE_KEYS['statistics'] . 'alliances');
                Cache::forget(self::CACHE_KEYS['statistics'] . 'battles');
            }
        }
    }

    /**
     * Warm up cache for frequently accessed data
     */
    public static function warmUpCache(): void
    {
        // Warm up top players
        self::getLeaderboard('points', 50);

        // Warm up top alliances
        self::getLeaderboard('alliances', 20);

        // Warm up general statistics
        self::getGameStatistics('general');

        // Warm up active players
        $activePlayers = \App\Models\Game\Player::where('last_activity_at', '>=', now()->subHours(1))
            ->limit(100)
            ->pluck('id');

        foreach ($activePlayers as $playerId) {
            self::getPlayerData($playerId);
        }
    }

    /**
     * Get cache statistics
     */
    public static function getCacheStatistics(): array
    {
        $stats = [];

        foreach (self::CACHE_KEYS as $type => $prefix) {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $keys = Redis::keys($prefix . '*');
                $stats[$type] = count($keys);
            } else {
                $stats[$type] = 'N/A (not Redis)';
            }
        }

        return [
            'cache_stats' => $stats,
            'cache_store' => get_class(Cache::getStore()),
            'redis_memory' => Cache::getStore() instanceof \Illuminate\Cache\RedisStore
                ? Redis::info('memory')
                : null,
        ];
    }

    /**
     * Clear all game cache
     */
    public static function clearAllGameCache(): void
    {
        foreach (self::CACHE_KEYS as $type => $prefix) {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $keys = Redis::keys($prefix . '*');
                if (!empty($keys)) {
                    Redis::del($keys);
                }
            }
        }

        \Log::info('All game cache cleared');
    }
}
