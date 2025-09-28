<?php

namespace App\Services;

use App\Events\GameEvent;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use SmartCache\Facades\SmartCache;

class RealTimeGameService
{
    /**
     * Send real-time update to user
     */
    public static function sendUpdate(int $userId, string $eventType, array $data = []): void
    {
        try {
            // Broadcast via Laravel WebSockets
            broadcast(new GameEvent($userId, $eventType, $data));

            // Store in Redis for WebSocket server
            $update = [
                'user_id' => $userId,
                'event_type' => $eventType,
                'data' => $data,
                'timestamp' => now()->toISOString(),
            ];

            Redis::lpush("user_updates:{$userId}", json_encode($update));
            Redis::ltrim("user_updates:{$userId}", 0, 99);  // Keep last 100 updates
            Redis::expire("user_updates:{$userId}", 3600);  // Expire after 1 hour

            Log::channel('realtime')->info('Real-time update sent', [
                'user_id' => $userId,
                'event_type' => $eventType,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send real-time update', [
                'user_id' => $userId,
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send update to multiple users
     */
    public static function broadcastUpdate(array $userIds, string $eventType, array $data = []): void
    {
        foreach ($userIds as $userId) {
            self::sendUpdate($userId, $eventType, $data);
        }
    }

    /**
     * Send update to all online users
     */
    public static function broadcastToAllOnline(string $eventType, array $data = []): void
    {
        try {
            $onlineUsers = self::getOnlineUsers();
            self::broadcastUpdate($onlineUsers, $eventType, $data);

            Log::info('Broadcasted to all online users', [
                'event_type' => $eventType,
                'user_count' => count($onlineUsers),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to broadcast to all online users', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send village update
     */
    public static function sendVillageUpdate(int $userId, int $villageId, string $updateType, array $data = []): void
    {
        $data['village_id'] = $villageId;
        $data['update_type'] = $updateType;

        self::sendUpdate($userId, 'village_update', $data);
    }

    /**
     * Send resource update
     */
    public static function sendResourceUpdate(int $userId, int $villageId, array $resources): void
    {
        self::sendUpdate($userId, 'resource_update', [
            'village_id' => $villageId,
            'resources' => $resources,
        ]);
    }

    /**
     * Send battle update
     */
    public static function sendBattleUpdate(int $userId, int $battleId, string $status, array $data = []): void
    {
        $data['battle_id'] = $battleId;
        $data['status'] = $status;

        self::sendUpdate($userId, 'battle_update', $data);
    }

    /**
     * Send movement update
     */
    public static function sendMovementUpdate(int $userId, int $movementId, string $status, array $data = []): void
    {
        $data['movement_id'] = $movementId;
        $data['status'] = $status;

        self::sendUpdate($userId, 'movement_update', $data);
    }

    /**
     * Send building update
     */
    public static function sendBuildingUpdate(int $userId, int $villageId, string $buildingType, int $newLevel, array $data = []): void
    {
        $data['village_id'] = $villageId;
        $data['building_type'] = $buildingType;
        $data['new_level'] = $newLevel;

        self::sendUpdate($userId, 'building_update', $data);
    }

    /**
     * Send alliance update
     */
    public static function sendAllianceUpdate(int $allianceId, string $updateType, array $data = []): void
    {
        try {
            $alliance = \App\Models\Game\Alliance::with('members')->find($allianceId);

            if (! $alliance) {
                return;
            }

            $userIds = $alliance->members->pluck('user_id')->toArray();
            $data['alliance_id'] = $allianceId;
            $data['update_type'] = $updateType;

            self::broadcastUpdate($userIds, 'alliance_update', $data);
        } catch (\Exception $e) {
            Log::error('Failed to send alliance update', [
                'alliance_id' => $allianceId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mark user as online
     */
    public static function markUserOnline(int $userId): void
    {
        try {
            Redis::sadd('online_users', $userId);
            Redis::expire('online_users', 3600);  // Expire after 1 hour

            // Update last activity
            Cache::put("user_activity:{$userId}", now()->timestamp, 300);  // 5 minutes

            Log::debug('User marked as online', ['user_id' => $userId]);
        } catch (\Exception $e) {
            Log::error('Failed to mark user as online', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Mark user as offline
     */
    public static function markUserOffline(int $userId): void
    {
        try {
            Redis::srem('online_users', $userId);
            Cache::forget("user_activity:{$userId}");

            Log::debug('User marked as offline', ['user_id' => $userId]);
        } catch (\Exception $e) {
            Log::error('Failed to mark user as offline', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get online users with SmartCache optimization
     */
    public static function getOnlineUsers(): array
    {
        try {
            $cacheKey = 'online_users_'.now()->format('Y-m-d-H-i');

            return SmartCache::remember($cacheKey, now()->addMinutes(2), function () {
                $onlineUsers = Redis::smembers('online_users');

                // Filter out inactive users
                $activeUsers = [];
                foreach ($onlineUsers as $userId) {
                    $lastActivity = Cache::get("user_activity:{$userId}");
                    if ($lastActivity && (now()->timestamp - $lastActivity) < 300) {  // 5 minutes
                        $activeUsers[] = (int) $userId;
                    }
                }

                return $activeUsers;
            });
        } catch (\Exception $e) {
            Log::error('Failed to get online users', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get user's pending updates
     */
    public static function getUserUpdates(int $userId, int $limit = 50): array
    {
        try {
            $updates = Redis::lrange("user_updates:{$userId}", 0, $limit - 1);

            return array_map(function ($update) {
                return json_decode($update, true);
            }, $updates);
        } catch (\Exception $e) {
            Log::error('Failed to get user updates', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Clear user's pending updates
     */
    public static function clearUserUpdates(int $userId): void
    {
        try {
            Redis::del("user_updates:{$userId}");

            Log::debug('User updates cleared', ['user_id' => $userId]);
        } catch (\Exception $e) {
            Log::error('Failed to clear user updates', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get real-time statistics
     */
    public static function getRealTimeStats(): array
    {
        try {
            $onlineUsers = self::getOnlineUsers();

            $stats = [
                'online_users_count' => count($onlineUsers),
                'total_updates_sent' => 0,
                'active_channels' => 0,
            ];

            // Get total updates sent (approximate)
            $updateKeys = Redis::keys('user_updates:*');
            $stats['active_channels'] = count($updateKeys);

            foreach ($updateKeys as $key) {
                $count = Redis::llen($key);
                $stats['total_updates_sent'] += $count;
            }

            return $stats;
        } catch (\Exception $e) {
            Log::error('Failed to get real-time stats', [
                'error' => $e->getMessage(),
            ]);

            return [
                'online_users_count' => 0,
                'total_updates_sent' => 0,
                'active_channels' => 0,
            ];
        }
    }

    /**
     * Send system announcement
     */
    public static function sendSystemAnnouncement(string $title, string $message, string $priority = 'normal'): void
    {
        $onlineUsers = self::getOnlineUsers();

        $data = [
            'title' => $title,
            'message' => $message,
            'priority' => $priority,
            'system_announcement' => true,
        ];

        self::broadcastUpdate($onlineUsers, 'system_announcement', $data);

        Log::info('System announcement sent', [
            'title' => $title,
            'recipients' => count($onlineUsers),
            'priority' => $priority,
        ]);
    }

    /**
     * Clean up old data
     */
    public static function cleanup(): void
    {
        try {
            $cleaned = 0;

            // Clean up old user updates (older than 1 hour)
            $updateKeys = Redis::keys('user_updates:*');
            foreach ($updateKeys as $key) {
                $userId = str_replace('user_updates:', '', $key);
                $lastActivity = Cache::get("user_activity:{$userId}");

                if (! $lastActivity || (now()->timestamp - $lastActivity) > 3600) {
                    Redis::del($key);
                    $cleaned++;
                }
            }

            // Clean up inactive users from online list
            $onlineUsers = Redis::smembers('online_users');
            foreach ($onlineUsers as $userId) {
                $lastActivity = Cache::get("user_activity:{$userId}");
                if (! $lastActivity || (now()->timestamp - $lastActivity) > 300) {
                    Redis::srem('online_users', $userId);
                    $cleaned++;
                }
            }

            Log::info('Real-time cleanup completed', ['cleaned_entries' => $cleaned]);
        } catch (\Exception $e) {
            Log::error('Failed to cleanup real-time data', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send geographic update to nearby players
     */
    public static function sendGeographicUpdate(int $villageId, string $eventType, array $data = []): void
    {
        try {
            $geoService = app(GeographicService::class);

            // Get village with geographic data
            $village = \App\Models\Game\Village::with('player')
                ->where('id', $villageId)
                ->first();

            if (! $village || ! $village->latitude || ! $village->longitude) {
                return;
            }

            // Find nearby villages within 50km
            $nearbyVillages = \App\Models\Game\Village::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->where('id', '!=', $villageId)
                ->get()
                ->filter(function ($nearbyVillage) use ($village, $geoService) {
                    $distance = $geoService->calculateDistance(
                        $village->latitude,
                        $village->longitude,
                        $nearbyVillage->latitude,
                        $nearbyVillage->longitude
                    );

                    return $distance <= 50; // Within 50km
                });

            // Send updates to players of nearby villages
            foreach ($nearbyVillages as $nearbyVillage) {
                if ($nearbyVillage->player && $nearbyVillage->player->user_id) {
                    $geographicData = [
                        'village_id' => $villageId,
                        'village_name' => $village->name,
                        'distance_km' => $geoService->calculateDistance(
                            $village->latitude,
                            $village->longitude,
                            $nearbyVillage->latitude,
                            $nearbyVillage->longitude
                        ),
                        'bearing' => $geoService->calculateBearing(
                            $nearbyVillage->latitude,
                            $nearbyVillage->longitude,
                            $village->latitude,
                            $village->longitude
                        ),
                        'event_type' => $eventType,
                        'data' => $data,
                    ];

                    self::sendUpdate($nearbyVillage->player->user_id, 'geographic_event', $geographicData);
                }
            }

            Log::info('Geographic update sent', [
                'village_id' => $villageId,
                'nearby_villages' => $nearbyVillages->count(),
                'event_type' => $eventType,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send geographic update', [
                'village_id' => $villageId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send movement update with geographic context
     */
    public static function sendMovementUpdateWithGeo(int $movementId, string $eventType, array $data = []): void
    {
        try {
            $movement = \App\Models\Game\Movement::with(['fromVillage', 'toVillage', 'player'])
                ->find($movementId);

            if (! $movement) {
                return;
            }

            $geoService = app(GeographicService::class);

            // Add geographic context to movement data
            $geographicContext = [
                'movement_id' => $movementId,
                'from_village' => $movement->fromVillage->name ?? 'Unknown',
                'to_village' => $movement->toVillage->name ?? 'Unknown',
                'distance_km' => $movement->distance ?? 0,
                'bearing' => $movement->bearing ?? 0,
                'travel_time' => $movement->travel_time ?? 0,
                'event_type' => $eventType,
                'data' => $data,
            ];

            // Send to movement owner
            if ($movement->player && $movement->player->user_id) {
                self::sendUpdate($movement->player->user_id, 'movement_update', $geographicContext);
            }

            // Send to nearby players if it's an attack
            if ($eventType === 'attack_launched' && $movement->toVillage) {
                self::sendGeographicUpdate($movement->toVillage->id, 'incoming_attack', $geographicContext);
            }

            Log::info('Movement update sent', [
                'movement_id' => $movementId,
                'event_type' => $eventType,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send movement update', [
                'movement_id' => $movementId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
