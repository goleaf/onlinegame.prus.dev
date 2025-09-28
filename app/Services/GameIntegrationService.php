<?php

namespace App\Services;

use App\Models\Game\Alliance;
use App\Models\Game\Player;
use App\Models\Game\Report;
use App\Models\Game\Task;
use App\Models\Game\Village;
use App\Utilities\LoggingUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LaraUtilX\Utilities\CachingUtil;
use LaraUtilX\Utilities\ConfigUtil;
use LaraUtilX\Utilities\RateLimiterUtil;

class GameIntegrationService
{
    protected $realTimeService;

    protected $cacheService;

    protected $errorHandler;

    protected $notificationService;

    protected $performanceMonitor;

    protected CachingUtil $cachingUtil;

    protected LoggingUtil $loggingUtil;

    protected RateLimiterUtil $rateLimiterUtil;

    protected ConfigUtil $configUtil;

    public function __construct(
        RealTimeGameService $realTimeService,
        GameCacheService $cacheService,
        GameErrorHandler $errorHandler,
        GameNotificationService $notificationService,
        GamePerformanceMonitor $performanceMonitor,
        CachingUtil $cachingUtil,
        LoggingUtil $loggingUtil,
        RateLimiterUtil $rateLimiterUtil,
        ConfigUtil $configUtil
    ) {
        $this->realTimeService = $realTimeService;
        $this->cacheService = $cacheService;
        $this->errorHandler = $errorHandler;
        $this->notificationService = $notificationService;
        $this->performanceMonitor = $performanceMonitor;
        $this->cachingUtil = $cachingUtil;
        $this->loggingUtil = $loggingUtil;
        $this->rateLimiterUtil = $rateLimiterUtil;
        $this->configUtil = $configUtil;
    }

    /**
     * Initialize real-time features for a user
     */
    public function initializeUserRealTime(int $userId): array
    {
        $startTime = microtime(true);

        ds('GameIntegrationService: Initializing user real-time features', [
            'user_id' => $userId,
            'service' => 'GameIntegrationService',
            'method' => 'initializeUserRealTime',
            'timestamp' => now(),
        ])->label('GameIntegrationService User Initialization');

        try {
            $this->performanceMonitor->startTimer('user_initialization');

            // Mark user as online
            RealTimeGameService::markUserOnline($userId);

            // Initialize real-time features
            $player = Player::where('user_id', $userId)->first();
            if (! $player) {
                throw new \Exception('Player not found for user: '.$userId);
            }

            // Get player's villages
            $villages = $player->villages()->with(['buildings', 'resources', 'troops'])->get();

            // Send initial data
            $initialData = [
                'player' => $player,
                'villages' => $villages,
                'alliance' => $player->alliance,
                'notifications' => $this->notificationService->getUserNotifications($userId),
            ];

            // Send real-time update
            RealTimeGameService::sendUpdate($userId, 'user_initialized', $initialData);

            // Cache player data
            $this->cacheService->cachePlayerData($player);

            $this->performanceMonitor->endTimer('user_initialization');

            $totalTime = round((microtime(true) - $startTime) * 1000, 2);

            ds('GameIntegrationService: User real-time features initialized successfully', [
                'user_id' => $userId,
                'player_id' => $player->id,
                'villages_count' => $villages->count(),
                'total_time_ms' => $totalTime,
                'initialization_time' => $this->performanceMonitor->getTimer('user_initialization'),
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            ])->label('GameIntegrationService User Initialized');

            Log::info('User real-time features initialized', [
                'user_id' => $userId,
                'player_id' => $player->id,
                'villages_count' => $villages->count(),
            ]);

            return [
                'success' => true,
                'player_id' => $player->id,
                'villages_count' => $villages->count(),
                'initialization_time' => $this->performanceMonitor->getTimer('user_initialization'),
            ];

        } catch (\Exception $e) {
            $totalTime = round((microtime(true) - $startTime) * 1000, 2);

            ds('GameIntegrationService: User initialization failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'total_time_ms' => $totalTime,
            ])->label('GameIntegrationService User Initialization Error');

            $this->errorHandler->handleError('user_initialization', $e, [
                'user_id' => $userId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Deinitialize real-time features for a user
     */
    public function deinitializeUserRealTime(int $userId): array
    {
        try {
            // Mark user as offline
            RealTimeGameService::markUserOffline($userId);

            // Clear user cache
            $this->cacheService->clearPlayerCache($userId);

            // Clear user notifications
            $this->notificationService->clearUserNotifications($userId);

            Log::info('User real-time features deinitialized', [
                'user_id' => $userId,
            ]);

            return [
                'success' => true,
                'message' => 'User real-time features deinitialized',
            ];

        } catch (\Exception $e) {
            $this->errorHandler->handleError('user_deinitialization', $e, [
                'user_id' => $userId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create village with real-time updates
     */
    public function createVillageWithIntegration(array $villageData): array
    {
        try {
            $this->performanceMonitor->startTimer('village_creation');

            $village = DB::transaction(function () use ($villageData) {
                $village = Village::create($villageData);

                // Initialize village resources
                $village->resources()->createMany([
                    ['type' => 'wood', 'amount' => 750],
                    ['type' => 'clay', 'amount' => 750],
                    ['type' => 'iron', 'amount' => 750],
                    ['type' => 'crop', 'amount' => 750],
                ]);

                // Initialize village buildings
                $village->buildings()->createMany([
                    ['building_type_id' => 1, 'level' => 1], // Main Building
                    ['building_type_id' => 2, 'level' => 1], // Woodcutter
                    ['building_type_id' => 3, 'level' => 1], // Clay Pit
                    ['building_type_id' => 4, 'level' => 1], // Iron Mine
                    ['building_type_id' => 5, 'level' => 1], // Cropland
                ]);

                return $village;
            });

            // Send real-time update
            RealTimeGameService::sendVillageUpdate(
                $village->player_id,
                $village->id,
                'village_created',
                [
                    'village_name' => $village->name,
                    'coordinates' => $village->x.'|'.$village->y,
                ]
            );

            // Send notification
            $this->notificationService->sendUserNotification(
                $village->player_id,
                'Village Created',
                "Your new village '{$village->name}' has been created at coordinates {$village->x}|{$village->y}",
                'village_created'
            );

            // Cache village data
            $this->cacheService->cacheVillageData($village);

            $this->performanceMonitor->endTimer('village_creation');

            Log::info('Village created with integration', [
                'village_id' => $village->id,
                'player_id' => $village->player_id,
                'coordinates' => $village->x.'|'.$village->y,
            ]);

            return [
                'success' => true,
                'village_id' => $village->id,
                'creation_time' => $this->performanceMonitor->getTimer('village_creation'),
            ];

        } catch (\Exception $e) {
            $this->errorHandler->handleError('village_creation', $e, $villageData);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upgrade building with real-time updates
     */
    public function upgradeBuildingWithIntegration(int $villageId, int $buildingTypeId): array
    {
        try {
            $this->performanceMonitor->startTimer('building_upgrade');

            $village = Village::with(['player', 'buildings'])->findOrFail($villageId);
            $building = $village->buildings()
                ->where('building_type_id', $buildingTypeId)
                ->first();

            if (! $building) {
                throw new \Exception('Building not found');
            }

            // Start building upgrade
            $building->update([
                'is_under_construction' => true,
                'construction_started_at' => now(),
                'construction_completed_at' => now()->addHours($building->level + 1),
            ]);

            // Send real-time update
            RealTimeGameService::sendBuildingUpdate(
                $village->player_id,
                $villageId,
                $building->buildingType->key,
                $building->level + 1,
                [
                    'construction_time' => $building->construction_completed_at->diffInSeconds(now()),
                ]
            );

            // Send notification
            $this->notificationService->sendUserNotification(
                $village->player_id,
                'Building Upgrade Started',
                "Upgrading {$building->buildingType->name} to level ".($building->level + 1),
                'building_upgrade'
            );

            $this->performanceMonitor->endTimer('building_upgrade');

            Log::info('Building upgrade started with integration', [
                'village_id' => $villageId,
                'building_type_id' => $buildingTypeId,
                'new_level' => $building->level + 1,
            ]);

            return [
                'success' => true,
                'building_id' => $building->id,
                'new_level' => $building->level + 1,
                'completion_time' => $building->construction_completed_at,
            ];

        } catch (\Exception $e) {
            $this->errorHandler->handleError('building_upgrade', $e, [
                'village_id' => $villageId,
                'building_type_id' => $buildingTypeId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Join alliance with real-time updates
     */
    public function joinAllianceWithIntegration(int $playerId, int $allianceId): array
    {
        try {
            $this->performanceMonitor->startTimer('alliance_join');

            $player = Player::findOrFail($playerId);
            $alliance = Alliance::findOrFail($allianceId);

            // Update player alliance
            $player->update(['alliance_id' => $allianceId]);

            // Send real-time update to alliance members
            RealTimeGameService::sendAllianceUpdate(
                $allianceId,
                'member_joined',
                [
                    'player_name' => $player->name,
                    'player_id' => $playerId,
                ]
            );

            // Send notification to alliance members
            $allianceMembers = $alliance->members()->where('id', '!=', $playerId)->get();
            foreach ($allianceMembers as $member) {
                $this->notificationService->sendUserNotification(
                    $member->user_id,
                    'New Alliance Member',
                    "{$player->name} has joined the alliance",
                    'alliance_member_joined'
                );
            }

            // Send notification to joining player
            $this->notificationService->sendUserNotification(
                $player->user_id,
                'Alliance Joined',
                "You have joined the alliance '{$alliance->name}'",
                'alliance_joined'
            );

            $this->performanceMonitor->endTimer('alliance_join');

            Log::info('Player joined alliance with integration', [
                'player_id' => $playerId,
                'alliance_id' => $allianceId,
                'alliance_name' => $alliance->name,
            ]);

            return [
                'success' => true,
                'alliance_id' => $allianceId,
                'alliance_name' => $alliance->name,
            ];

        } catch (\Exception $e) {
            $this->errorHandler->handleError('alliance_join', $e, [
                'player_id' => $playerId,
                'alliance_id' => $allianceId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get comprehensive game statistics
     */
    public function getGameStatistics(): array
    {
        try {
            $this->performanceMonitor->startTimer('game_statistics');

            $stats = [
                'players' => [
                    'total' => Player::count(),
                    'online' => RealTimeGameService::getOnlineUsers(),
                    'active_today' => Player::where('last_activity', '>=', now()->subDay())->count(),
                ],
                'villages' => [
                    'total' => Village::count(),
                    'active' => Village::whereHas('player', function ($query): void {
                        $query->where('last_activity', '>=', now()->subDay());
                    })->count(),
                ],
                'alliances' => [
                    'total' => Alliance::count(),
                    'active' => Alliance::whereHas('members', function ($query): void {
                        $query->where('last_activity', '>=', now()->subDay());
                    })->count(),
                ],
                'tasks' => [
                    'total' => Task::count(),
                    'completed_today' => Task::where('status', 'completed')
                        ->where('completed_at', '>=', now()->subDay())
                        ->count(),
                ],
                'reports' => [
                    'total' => Report::count(),
                    'today' => Report::where('created_at', '>=', now()->subDay())->count(),
                ],
                'performance' => $this->performanceMonitor->getPerformanceStats(),
                'cache' => $this->cacheService->getCacheStats(),
                'notifications' => $this->notificationService->getNotificationStats(),
                'errors' => $this->errorHandler->getErrorStats(),
            ];

            $this->performanceMonitor->endTimer('game_statistics');

            return [
                'success' => true,
                'statistics' => $stats,
                'generation_time' => $this->performanceMonitor->getTimer('game_statistics'),
            ];

        } catch (\Exception $e) {
            $this->errorHandler->handleError('game_statistics', $e);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send system announcement
     */
    public function sendSystemAnnouncement(string $title, string $message, string $priority = 'normal'): array
    {
        try {
            $this->performanceMonitor->startTimer('system_announcement');

            // Send to all online users
            RealTimeGameService::sendSystemAnnouncement($title, $message, $priority);

            // Send notifications to all users
            $this->notificationService->sendSystemNotification($title, $message, $priority);

            $this->performanceMonitor->endTimer('system_announcement');

            Log::info('System announcement sent', [
                'title' => $title,
                'priority' => $priority,
            ]);

            return [
                'success' => true,
                'message' => 'System announcement sent successfully',
            ];

        } catch (\Exception $e) {
            $this->errorHandler->handleError('system_announcement', $e, [
                'title' => $title,
                'priority' => $priority,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Perform system maintenance
     */
    public function performMaintenance(): array
    {
        try {
            $this->performanceMonitor->startTimer('system_maintenance');

            $maintenance = [
                'cache_cleanup' => $this->cacheService->cleanup(),
                'notification_cleanup' => $this->notificationService->cleanup(),
                'error_cleanup' => $this->errorHandler->cleanup(),
                'realtime_cleanup' => RealTimeGameService::cleanup(),
            ];

            $this->performanceMonitor->endTimer('system_maintenance');

            Log::info('System maintenance completed', $maintenance);

            return [
                'success' => true,
                'maintenance' => $maintenance,
                'maintenance_time' => $this->performanceMonitor->getTimer('system_maintenance'),
            ];

        } catch (\Exception $e) {
            $this->errorHandler->handleError('system_maintenance', $e);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
