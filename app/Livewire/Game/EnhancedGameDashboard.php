<?php

namespace App\Livewire\Game;

use App\Models\Game\GameEvent;
use App\Models\Game\Player;
use App\Services\GameTickService;
use App\Services\GeographicService;
use App\Services\ResourceProductionService;
use Illuminate\Support\Facades\Auth;
use LaraUtilX\Traits\ApiResponseTrait;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use SmartCache\Facades\SmartCache;

class EnhancedGameDashboard extends Component
{
    use WithPagination, ApiResponseTrait;

    #[Locked]
    public $player;

    #[Url]
    public $selectedVillageId = null;

    public $currentVillage;
    public $villages = [];
    public $recentEvents = [];
    public $gameStats = [];

    #[Url]
    public $autoRefresh = true;

    #[Url]
    public $refreshInterval = 5;

    public $notifications = [];
    public $isLoading = false;
    public $realTimeUpdates = true;
    public $showNotifications = true;
    public $gameSpeed = 1;
    public $worldTime;
    public $resourceProductionRates = [];
    public $buildingQueues = [];
    public $trainingQueues = [];
    public $activeQuests = [];
    public $allianceInfo = null;
    public $worldInfo = null;
    // Enhanced Livewire features
    public $pollingEnabled = true;
    public $lastUpdateTime;
    public $connectionStatus = 'connected';
    public $gameEvents = [];
    public $playerStatistics = [];
    public $worldStatistics = [];
    public $allianceStatistics = [];
    public $achievements = [];
    public $recentBattles = [];
    public $marketOffers = [];
    public $diplomaticEvents = [];

    protected $listeners = [
        'refreshGameData',
        'gameTickProcessed',
        'gameTickError',
        'buildingCompleted',
        'resourceUpdated',
        'villageUpdated',
        'questCompleted',
        'allianceUpdated',
        'battleReportReceived',
        'marketOfferUpdated',
        'diplomaticEventOccurred',
        'achievementUnlocked',
        'connectionStatusChanged',
    ];

    #[Computed]
    public function totalResources()
    {
        if (!$this->currentVillage) {
            return [];
        }

        return [
            'wood' => $this->currentVillage->wood ?? 0,
            'clay' => $this->currentVillage->clay ?? 0,
            'iron' => $this->currentVillage->iron ?? 0,
            'crop' => $this->currentVillage->crop ?? 0,
        ];
    }

    #[Computed]
    public function resourceCapacities()
    {
        if (!$this->currentVillage) {
            return [];
        }

        return [
            'wood' => $this->currentVillage->wood_capacity ?? 0,
            'clay' => $this->currentVillage->clay_capacity ?? 0,
            'iron' => $this->currentVillage->iron_capacity ?? 0,
            'crop' => $this->currentVillage->crop_capacity ?? 0,
        ];
    }

    #[Computed]
    public function playerRanking()
    {
        if (!$this->player) {
            return null;
        }

        return [
            'points' => $this->player->points ?? 0,
            'population' => $this->player->population ?? 0,
            'villages_count' => $this->player->villages_count ?? 0,
            'alliance_rank' => $this->allianceInfo['rank'] ?? null,
        ];
    }

    #[Computed]
    public function gameTimeRemaining()
    {
        if (!$this->worldInfo) {
            return null;
        }

        $endDate = $this->worldInfo['end_date'] ?? null;
        if (!$endDate) {
            return null;
        }

        return now()->diffInDays($endDate, false);
    }

    public function mount()
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        $this->player = Auth::user()->player;
        if (!$this->player) {
            return redirect('/game/no-player');
        }

        // Laradumps debugging
        ds('EnhancedGameDashboard mounted', [
            'user_id' => Auth::id(),
            'player_id' => $this->player->id,
            'world_id' => $this->player->world_id,
            'villages_count' => $this->player->villages->count() ?? 0,
            'player_coordinates' => $this->player->villages->map(function ($village) {
                return [
                    'village_name' => $village->name,
                    'coordinates' => "({$village->x_coordinate}|{$village->y_coordinate})",
                    'real_world_coords' => $village->getRealWorldCoordinates()
                ];
            })->toArray()
        ])->label('EnhancedGameDashboard Mount');

        $this->loadGameData();
        $this->initializeRealTimeFeatures();
        $this->startPolling();
        $this->initializeConnectionMonitoring();
    }

    public function boot()
    {
        // Ensure player is loaded in test environment
        if (app()->environment('testing') && Auth::check() && !$this->player) {
            $this->player = Auth::user()->player;
        }

        // If player is null after boot, redirect to no-player page
        if (!$this->player && Auth::check()) {
            return redirect('/game/no-player');
        }
    }

    public function initializeConnectionMonitoring()
    {
        $this->dispatch('initialize-connection-monitoring', [
            'player_id' => $this->player->id,
            'world_id' => $this->player->world_id,
        ]);
    }

    public function startPolling()
    {
        if ($this->pollingEnabled && $this->autoRefresh) {
            $this->dispatch('start-game-polling', [
                'interval' => $this->refreshInterval * 1000,
                'player_id' => $this->player->id,
                'world_id' => $this->player->world_id,
            ]);
        }
    }

    public function stopPolling()
    {
        $this->dispatch('stop-game-polling');
    }

    public function togglePolling()
    {
        $this->pollingEnabled = !$this->pollingEnabled;

        if ($this->pollingEnabled) {
            $this->startPolling();
        } else {
            $this->stopPolling();
        }

        $this->addNotification(
            $this->pollingEnabled ? 'Real-time updates enabled' : 'Real-time updates disabled',
            'info'
        );
    }

    public function initializeRealTimeFeatures()
    {
        $this->worldTime = now();
        $this->calculateResourceProductionRates();
        $this->loadBuildingQueues();
        $this->loadTrainingQueues();
        $this->loadActiveQuests();
        $this->loadAllianceInfo();
        $this->loadWorldInfo();
        $this->loadRecentEvents();
    }

    public function loadGameData()
    {
        // Use SmartCache for performance optimization with automatic compression
        $cacheKey = "player_{$this->player->id}_villages_data";

        $this->villages = SmartCache::remember($cacheKey, now()->addMinutes(5), function () {
            return $this
                ->player
                ->villages()
                ->selectRaw('
                    villages.*,
                    (SELECT COUNT(*) FROM buildings WHERE village_id = villages.id) as building_count,
                    (SELECT COUNT(*) FROM troops WHERE village_id = villages.id) as troop_count,
                    (SELECT SUM(wood + clay + iron + crop) FROM resources WHERE village_id = villages.id) as total_resources
                ')
                ->get();
        });

        $this->currentVillage = $this->villages->first();
        $this->calculateGameStats();
    }

    public function loadRecentEvents()
    {
        // Use SmartCache for recent events with automatic optimization
        $cacheKey = "player_{$this->player->id}_recent_events";

        $this->recentEvents = SmartCache::remember($cacheKey, now()->addMinutes(1), function () {
            return GameEvent::byPlayer($this->player->id)
                ->withStats()
                ->withPlayerInfo()
                ->recent(7)
                ->orderBy('occurred_at', 'desc')
                ->limit(5)
                ->get();
        });
    }

    public function calculateGameStats()
    {
        $this->gameStats = [
            'total_population' => $this->villages->sum('population'),
            'total_culture_points' => $this->villages->sum('culture_points'),
            'villages_count' => $this->villages->count(),
            'total_villages' => $this->villages->count(),  // Add this for the view
            'world_rank' => $this->getWorldRank(),
            'total_points' => $this->player->points ?? 0,
            'online_status' => $this->player->is_online ? 'Online' : 'Offline',
        ];
    }

    public function getWorldRank()
    {
        return Player::where('world_id', $this->player->world_id)
            ->where('points', '>', $this->player->points)
            ->count() + 1;
    }

    public function calculateResourceProductionRates()
    {
        if (!$this->currentVillage) {
            return;
        }

        $productionService = app(ResourceProductionService::class);
        $this->resourceProductionRates = $productionService->calculateResourceProduction($this->currentVillage);
    }

    public function loadBuildingQueues()
    {
        if (!$this->currentVillage) {
            return;
        }

        $this->buildingQueues = $this
            ->currentVillage
            ->buildingQueues()
            ->with('buildingType:id,name,description')
            ->where('is_active', true)
            ->selectRaw('
                building_queues.*,
                (SELECT COUNT(*) FROM building_queues bq2 WHERE bq2.village_id = building_queues.village_id AND bq2.is_active = 1) as total_active_queues
            ')
            ->get();
    }

    public function loadTrainingQueues()
    {
        if (!$this->currentVillage) {
            return;
        }

        $this->trainingQueues = $this
            ->currentVillage
            ->trainingQueues()
            ->with('unitType:id,name,attack_power,defense_power')
            ->where('is_active', true)
            ->selectRaw('
                training_queues.*,
                (SELECT COUNT(*) FROM training_queues tq2 WHERE tq2.village_id = training_queues.village_id AND tq2.is_active = 1) as total_active_queues
            ')
            ->get();
    }

    public function loadActiveQuests()
    {
        $this->activeQuests = $this
            ->player
            ->playerQuests()
            ->with('quest:id,name,description,category,difficulty')
            ->where('is_completed', false)
            ->selectRaw('
                player_quests.*,
                (SELECT COUNT(*) FROM player_quests pq2 WHERE pq2.player_id = player_quests.player_id AND pq2.is_completed = 0) as total_active_quests
            ')
            ->get();
    }

    public function loadAllianceInfo()
    {
        if (!$this->player || !$this->player->alliance) {
            return;
        }

        $this->allianceInfo = [
            'name' => $this->player->alliance->name,
            'tag' => $this->player->alliance->tag,
            'rank' => $this->player->alliance->rank,
            'members_count' => $this->player->alliance->members_count,
            'points' => $this->player->alliance->points,
        ];
    }

    public function loadWorldInfo()
    {
        $world = $this->player->world;
        $this->worldInfo = [
            'name' => $world->name,
            'speed' => $world->speed,
            'start_date' => $world->start_date,
            'end_date' => $world->end_date,
        ];
    }

    public function loadNotifications()
    {
        $this->notifications = GameEvent::byPlayer($this->player->id)
            ->unread()
            ->withStats()
            ->withPlayerInfo()
            ->orderBy('occurred_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function processGameTick()
    {
        try {
            // Laradumps debugging
            ds('Processing game tick', [
                'player_id' => $this->player->id,
                'current_village' => $this->currentVillage?->name,
                'last_update_time' => $this->lastUpdateTime
            ])->label('EnhancedGameDashboard Game Tick Start');

            $gameTickService = app(GameTickService::class);
            $gameTickService->processGameTick();

            $this->lastUpdateTime = now();
            $this->loadGameData();
            $this->dispatch('gameTickProcessed');

            ds('Game tick processed successfully', [
                'player_id' => $this->player->id,
                'new_update_time' => $this->lastUpdateTime,
                'villages_count' => $this->villages->count()
            ])->label('EnhancedGameDashboard Game Tick Success');
        } catch (\Exception $e) {
            ds('Game tick error', [
                'player_id' => $this->player->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ])->label('EnhancedGameDashboard Game Tick Error');
            $this->addNotification('Game tick error: ' . $e->getMessage(), 'error');
            $this->dispatch('gameTickError', ['message' => $e->getMessage()]);
        }
    }

    public function refreshGameData()
    {
        $this->isLoading = true;

        try {
            $this->loadGameData();
            $this->calculateResourceProductionRates();
            $this->loadBuildingQueues();
            $this->loadTrainingQueues();
            $this->loadActiveQuests();
            $this->loadAllianceInfo();
            $this->loadWorldInfo();
            $this->loadRecentEvents();

            $this->addNotification('Game data refreshed', 'info');
        } catch (\Exception $e) {
            $this->addNotification('Failed to refresh game data: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectVillage($villageId)
    {
        $this->selectedVillageId = $villageId;
        $this->currentVillage = $this->villages->find($villageId);

        // Laradumps debugging
        ds('Village selected', [
            'village_id' => $villageId,
            'village_name' => $this->currentVillage?->name,
            'player_id' => $this->player->id,
            'total_villages' => $this->villages->count(),
            'village_coordinates' => $this->currentVillage ? "({$this->currentVillage->x_coordinate}|{$this->currentVillage->y_coordinate})" : null,
            'real_world_coords' => $this->currentVillage?->getRealWorldCoordinates(),
            'geohash' => $this->currentVillage?->getGeohash()
        ])->label('EnhancedGameDashboard Village Selection');

        if ($this->currentVillage) {
            $this->calculateResourceProductionRates();
            $this->loadBuildingQueues();
            $this->loadTrainingQueues();
            $this->addNotification('Village selected: ' . $this->currentVillage->name, 'info');

            ds('Village data loaded', [
                'village_id' => $villageId,
                'resource_production_rates' => $this->resourceProductionRates,
                'building_queues_count' => $this->buildingQueues->count(),
                'training_queues_count' => $this->trainingQueues->count()
            ])->label('EnhancedGameDashboard Village Data Loaded');
        }
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;

        if ($this->autoRefresh) {
            $this->startPolling();
        } else {
            $this->stopPolling();
        }

        $this->addNotification(
            $this->autoRefresh ? 'Auto-refresh enabled' : 'Auto-refresh disabled',
            'info'
        );
    }

    public function updateRefreshInterval($interval)
    {
        $this->refreshInterval = max(1, min(60, $interval));

        if ($this->autoRefresh) {
            $this->stopPolling();
            $this->startPolling();
        }

        $this->addNotification("Refresh interval set to {$this->refreshInterval} seconds", 'info');
    }

    public function markNotificationAsRead($eventId)
    {
        GameEvent::where('id', $eventId)->update(['is_read' => true]);
        $this->loadNotifications();
    }

    public function markAllNotificationsAsRead()
    {
        GameEvent::where('player_id', $this->player->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);
        $this->loadNotifications();
    }

    public function addNotification($message, $type = 'info')
    {
        $this->notifications[] = [
            'id' => uniqid(),
            'message' => $message,
            'type' => $type,
            'timestamp' => now(),
        ];
        $this->notifications = array_slice($this->notifications, -10);
    }

    public function getResourceIcon($type)
    {
        return match ($type) {
            'wood' => '🌲',
            'clay' => '🏺',
            'iron' => '⚒️',
            'crop' => '🌾',
            default => '📦'
        };
    }

    public function getBuildingIcon($buildingType)
    {
        return match ($buildingType) {
            'main_building' => '🏛️',
            'barracks' => '🏰',
            'stable' => '🐎',
            'workshop' => '🔨',
            'warehouse' => '📦',
            'granary' => '🌾',
            'woodcutter' => '🌲',
            'clay_pit' => '🏺',
            'iron_mine' => '⚒️',
            'crop_field' => '🌾',
            default => '🏗️'
        };
    }

    public function getQuestIcon($category)
    {
        return match ($category) {
            'tutorial' => '📚',
            'building' => '🏗️',
            'combat' => '⚔️',
            'exploration' => '🗺️',
            'trade' => '💰',
            'alliance' => '🤝',
            'special' => '⭐',
            default => '📋'
        };
    }

    #[On('battleReportReceived')]
    public function handleBattleReportReceived($data)
    {
        $this->recentBattles = array_slice(array_merge([$data], $this->recentBattles), 0, 10);
        $this->addNotification('New battle report received', 'warning');
        $this->dispatch('battle-report-notification', $data);
    }

    #[On('marketOfferUpdated')]
    public function handleMarketOfferUpdated($data)
    {
        $this->marketOffers = array_slice(array_merge([$data], $this->marketOffers), 0, 20);
        $this->addNotification('Market offer updated', 'info');
    }

    #[On('diplomaticEventOccurred')]
    public function handleDiplomaticEventOccurred($data)
    {
        $this->diplomaticEvents = array_slice(array_merge([$data], $this->diplomaticEvents), 0, 10);
        $this->addNotification('Diplomatic event occurred', 'info');
    }

    #[On('achievementUnlocked')]
    public function handleAchievementUnlocked($data)
    {
        $this->achievements = array_slice(array_merge([$data], $this->achievements), 0, 10);
        $this->addNotification('Achievement unlocked: ' . $data['name'], 'success');
        $this->dispatch('achievement-notification', $data);
    }

    #[On('connectionStatusChanged')]
    public function handleConnectionStatusChanged($status)
    {
        $this->connectionStatus = $status;

        if ($status === 'disconnected') {
            $this->addNotification('Connection lost. Attempting to reconnect...', 'error');
        } elseif ($status === 'connected') {
            $this->addNotification('Connection restored', 'success');
            $this->refreshGameData();
        }
    }

    public function render()
    {
        return view('livewire.game.enhanced-game-dashboard', [
            'player' => $this->player,
            'currentVillage' => $this->currentVillage,
            'villages' => $this->villages,
            'recentEvents' => $this->recentEvents,
            'gameStats' => $this->gameStats,
            'notifications' => $this->notifications,
            'resourceProductionRates' => $this->resourceProductionRates,
            'buildingQueues' => $this->buildingQueues,
            'trainingQueues' => $this->trainingQueues,
            'activeQuests' => $this->activeQuests,
            'allianceInfo' => $this->allianceInfo,
            'worldInfo' => $this->worldInfo,
        ]);
    }
}
