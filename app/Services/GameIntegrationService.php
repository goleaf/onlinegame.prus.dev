<?php

namespace App\Services;

use App\Models\User;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\Alliance;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class GameIntegrationService
{
    /**
     * Initialize real-time features for a user
     */
    public static function initializeUserRealTime(int $userId): void
    {
        try {
            // Mark user as online
            RealTimeGameService::markUserOnline($userId);
            
            // Send welcome update
            RealTimeGameService::sendUpdate($userId, 'user_online', [
                'message' => 'Welcome back to the game!',
                'timestamp' => now()->toISOString(),
            ]);

            Log::info('User real-time features initialized', ['user_id' => $userId]);

        } catch (\Exception $e) {
            Log::error('Failed to initialize user real-time features', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Deinitialize real-time features for a user
     */
    public static function deinitializeUserRealTime(int $userId): void
    {
        try {
            // Mark user as offline
            RealTimeGameService::markUserOffline($userId);
            
            Log::info('User real-time features deinitialized', ['user_id' => $userId]);

        } catch (\Exception $e) {
            Log::error('Failed to deinitialize user real-time features', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle village creation with real-time updates
     */
    public static function createVillageWithRealTime(int $userId, array $villageData): Village
    {
        try {
            DB::beginTransaction();

            $player = Player::where('user_id', $userId)->first();
            if (!$player) {
                throw new \Exception('Player not found');
            }

            $village = new Village();
            $village->player_id = $player->id;
            $village->name = $villageData['name'];
            $village->lat = $villageData['lat'];
            $village->lon = $villageData['lon'];
            $village->wood = config('game.resources.starting_wood', 1000);
            $village->clay = config('game.resources.starting_clay', 1000);
            $village->iron = config('game.resources.starting_iron', 1000);
            $village->crop = config('game.resources.starting_crop', 1000);
            $village->population = 100;
            $village->save();

            // Create initial buildings
            self::createInitialBuildings($village);

            DB::commit();

            // Send real-time update
            RealTimeGameService::sendVillageUpdate($userId, $village->id, 'village_created', [
                'village_name' => $village->name,
                'coordinates' => ['lat' => $village->lat, 'lon' => $village->lon],
                'resources' => [
                    'wood' => $village->wood,
                    'clay' => $village->clay,
                    'iron' => $village->iron,
                    'crop' => $village->crop,
                ],
            ]);

            // Invalidate cache
            GameCacheService::invalidatePlayerCache($userId);

            // Log action
            GameErrorHandler::logGameAction('village_created', [
                'user_id' => $userId,
                'village_id' => $village->id,
                'village_name' => $village->name,
            ]);

            return $village;

        } catch (\Exception $e) {
            DB::rollBack();
            
            GameErrorHandler::handleGameError($e, [
                'action' => 'create_village',
                'user_id' => $userId,
                'village_data' => $villageData,
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle building upgrade with real-time updates
     */
    public static function upgradeBuildingWithRealTime(int $userId, int $villageId, string $buildingType, int $newLevel): void
    {
        try {
            DB::beginTransaction();

            $player = Player::where('user_id', $userId)->first();
            if (!$player) {
                throw new \Exception('Player not found');
            }

            $village = Village::where('id', $villageId)
                ->where('player_id', $player->id)
                ->first();
            
            if (!$village) {
                throw new \Exception('Village not found');
            }

            // Update building level (simplified logic)
            $village->increment('population', $newLevel);
            $player->increment('population', $newLevel);

            DB::commit();

            // Send real-time update
            RealTimeGameService::sendBuildingUpdate($userId, $villageId, $buildingType, $newLevel, [
                'village_name' => $village->name,
                'building_type' => $buildingType,
                'new_level' => $newLevel,
                'new_population' => $village->fresh()->population,
            ]);

            // Send resource update if applicable
            if (in_array($buildingType, ['woodcutter', 'clay_pit', 'iron_mine', 'crop_field'])) {
                RealTimeGameService::sendResourceUpdate($userId, $villageId, [
                    'wood' => $village->wood,
                    'clay' => $village->clay,
                    'iron' => $village->iron,
                    'crop' => $village->crop,
                ]);
            }

            // Invalidate cache
            GameCacheService::invalidateVillageCache($villageId);
            GameCacheService::invalidatePlayerCache($userId);

            // Log action
            GameErrorHandler::logGameAction('building_upgraded', [
                'user_id' => $userId,
                'village_id' => $villageId,
                'building_type' => $buildingType,
                'new_level' => $newLevel,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            GameErrorHandler::handleGameError($e, [
                'action' => 'upgrade_building',
                'user_id' => $userId,
                'village_id' => $villageId,
                'building_type' => $buildingType,
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle alliance join with real-time updates
     */
    public static function joinAllianceWithRealTime(int $userId, int $allianceId): void
    {
        try {
            $player = Player::where('user_id', $userId)->first();
            if (!$player) {
                throw new \Exception('Player not found');
            }

            $alliance = Alliance::find($allianceId);
            if (!$alliance) {
                throw new \Exception('Alliance not found');
            }

            // Add player to alliance (simplified logic)
            $player->alliance_id = $allianceId;
            $player->save();

            // Send alliance update
            RealTimeGameService::sendAllianceUpdate($allianceId, 'member_joined', [
                'player_name' => $player->name,
                'player_id' => $player->id,
                'alliance_name' => $alliance->name,
            ]);

            // Send personal update
            RealTimeGameService::sendUpdate($userId, 'alliance_joined', [
                'alliance_name' => $alliance->name,
                'alliance_id' => $allianceId,
            ]);

            // Invalidate cache
            GameCacheService::invalidatePlayerCache($userId);
            GameCacheService::invalidateAllianceCache($allianceId);

            // Log action
            GameErrorHandler::logGameAction('alliance_joined', [
                'user_id' => $userId,
                'player_id' => $player->id,
                'alliance_id' => $allianceId,
                'alliance_name' => $alliance->name,
            ]);

        } catch (\Exception $e) {
            GameErrorHandler::handleGameError($e, [
                'action' => 'join_alliance',
                'user_id' => $userId,
                'alliance_id' => $allianceId,
            ]);
            
            throw $e;
        }
    }

    /**
     * Handle resource update with real-time notifications
     */
    public static function updateResourcesWithRealTime(int $userId, int $villageId, array $resources): void
    {
        try {
            $player = Player::where('user_id', $userId)->first();
            if (!$player) {
                throw new \Exception('Player not found');
            }

            $village = Village::where('id', $villageId)
                ->where('player_id', $player->id)
                ->first();
            
            if (!$village) {
                throw new \Exception('Village not found');
            }

            // Update resources
            foreach ($resources as $resourceType => $amount) {
                if (in_array($resourceType, ['wood', 'clay', 'iron', 'crop'])) {
                    $village->$resourceType = $amount;
                }
            }
            $village->save();

            // Send real-time resource update
            RealTimeGameService::sendResourceUpdate($userId, $villageId, [
                'wood' => $village->wood,
                'clay' => $village->clay,
                'iron' => $village->iron,
                'crop' => $village->crop,
            ]);

            // Check for resource storage full notification
            $storageCapacity = config('game.resources.storage_capacity_base', 10000);
            foreach ($resources as $resourceType => $amount) {
                if ($amount >= $storageCapacity * 0.9) { // 90% full
                    RealTimeGameService::sendUpdate($userId, 'resource_storage_full', [
                        'village_id' => $villageId,
                        'resource_type' => $resourceType,
                        'current_amount' => $amount,
                        'storage_capacity' => $storageCapacity,
                    ]);
                }
            }

            // Invalidate cache
            GameCacheService::invalidateVillageCache($villageId);

        } catch (\Exception $e) {
            GameErrorHandler::handleGameError($e, [
                'action' => 'update_resources',
                'user_id' => $userId,
                'village_id' => $villageId,
                'resources' => $resources,
            ]);
            
            throw $e;
        }
    }

    /**
     * Send system-wide announcement with real-time delivery
     */
    public static function sendSystemAnnouncement(string $title, string $message, string $priority = 'normal'): void
    {
        try {
            // Send via real-time service
            RealTimeGameService::sendSystemAnnouncement($title, $message, $priority);

            // Also send via notification service if available
            if (class_exists('App\Services\GameNotificationService')) {
                $activeUsers = User::where('last_activity_at', '>=', now()->subHours(1))
                    ->pluck('id')
                    ->toArray();

                GameNotificationService::broadcastNotification($activeUsers, 'system_message', [
                    'title' => $title,
                    'message' => $message,
                    'system_announcement' => true,
                ], $priority);
            }

            Log::info('System announcement sent', [
                'title' => $title,
                'priority' => $priority,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send system announcement', [
                'title' => $title,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get comprehensive game statistics with real-time data
     */
    public static function getGameStatisticsWithRealTime(): array
    {
        try {
            $stats = GameCacheService::getGameStatistics('general');
            $realTimeStats = RealTimeGameService::getRealTimeStats();
            $performanceStats = GamePerformanceMonitor::getPerformanceStats();

            return [
                'game_stats' => $stats,
                'realtime_stats' => $realTimeStats,
                'performance_stats' => $performanceStats,
                'timestamp' => now()->toISOString(),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get comprehensive game statistics', [
                'error' => $e->getMessage(),
            ]);
            
            return [
                'error' => 'Failed to retrieve statistics',
                'timestamp' => now()->toISOString(),
            ];
        }
    }

    /**
     * Create initial buildings for a new village
     */
    private static function createInitialBuildings(Village $village): void
    {
        $initialBuildings = [
            ['type' => 'woodcutter', 'level' => 1],
            ['type' => 'clay_pit', 'level' => 1],
            ['type' => 'iron_mine', 'level' => 1],
            ['type' => 'crop_field', 'level' => 1],
            ['type' => 'warehouse', 'level' => 1],
            ['type' => 'barracks', 'level' => 1],
        ];
        
        foreach ($initialBuildings as $buildingData) {
            // This would create building records if you have a buildings table
            // For now, we'll just log the creation
            Log::debug('Initial building created', [
                'village_id' => $village->id,
                'building_type' => $buildingData['type'],
                'level' => $buildingData['level'],
            ]);
        }
    }

    /**
     * Cleanup and maintenance for real-time features
     */
    public static function performMaintenance(): void
    {
        try {
            // Cleanup real-time data
            RealTimeGameService::cleanup();
            
            // Cleanup old notifications if service exists
            if (class_exists('App\Services\GameNotificationService')) {
                GameNotificationService::cleanupOldNotifications();
            }

            Log::info('Real-time maintenance completed');

        } catch (\Exception $e) {
            Log::error('Real-time maintenance failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
