<?php

namespace App\Livewire\Game;

use App\Livewire\BaseSessionComponent;
use App\Models\Game\GameEvent;
use App\Models\Game\Player;
use App\Services\GameIntegrationService;
use App\Services\GameSeoService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\WithPagination;

class GameDashboard extends BaseSessionComponent
{
    use WithPagination;

    protected GameSeoService $seoService;

    public $player;

    public $currentVillage;

    public $villages = [];

    public $recentEvents = [];

    public $gameStats = [];

    // Game-specific session properties
    #[Session]
    public $selectedVillageId = null;

    #[Session]
    public $gameSpeed = 1;

    #[Session]
    public $showNotifications = true;

    #[Session]
    public $realTimeUpdates = true;

    #[Session]
    public $notificationTypes = ['success', 'info', 'warning', 'error'];

    #[Session]
    public $dashboardLayout = 'grid';

    #[Session]
    public $resourceViewMode = 'detailed';

    #[Session]
    public $buildingViewMode = 'list';

    #[Session]
    public $eventFilters = [];

    #[Session]
    public $villageFilters = [];

    public $notifications = [];

    public $isLoading = false;

    public $worldTime;

    public $resourceProductionRates = [];

    public function __construct()
    {
        parent::__construct();
        $this->seoService = app(GameSeoService::class);
    }

    protected $listeners = [
        'refreshGameData',
        'gameTickProcessed',
        'gameTickError',
        'buildingCompleted',
        'resourceUpdated',
        'villageUpdated',
    ];

    public function mount()
    {
        if (! Auth::check()) {
            return redirect('/login');
        }

        // Initialize session properties
        $this->initializeSessionProperties();

        // Override base refresh settings with game-specific defaults
        $this->refreshInterval = $this->refreshInterval ?: 5;

        $this->loadGameData();
        $this->initializeRealTimeFeatures();

        // Start real-time polling for game updates
        $this->startPolling();
    }

    public function initializeRealTimeFeatures()
    {
        try {
            // Initialize real-time features for the user
            GameIntegrationService::initializeUserRealTime(Auth::id());

            $this->worldTime = now();
            $this->calculateResourceProductionRates();

            // Dispatch initial real-time setup
            $this->dispatch('initializeRealTime', [
                'interval' => $this->refreshInterval * 1000,
                'autoRefresh' => $this->autoRefresh,
                'realTimeUpdates' => $this->realTimeUpdates,
            ]);

            $this->dispatch('game-dashboard-initialized', [
                'message' => 'Game dashboard real-time features activated',
                'user_id' => Auth::id(),
            ]);

        } catch (\Exception $e) {
            $this->dispatch('error', [
                'message' => 'Failed to initialize game dashboard real-time features: '.$e->getMessage(),
            ]);
        }
    }

    public function loadGameData()
    {
        $this->isLoading = true;

        try {
            $user = Auth::user();
            if (! $user) {
                $this->loadGameStats();  // Load default stats even when no user
                $this->isLoading = false;

                return;
            }
            $this->player = Player::where('user_id', $user->id)
                ->with(['villages' => function ($query): void {
                    $query->with(['resources', 'buildings', 'buildingQueues']);
                }])
                ->first();

            if ($this->player) {
                $this->villages = $this->player->villages;

                // Restore selected village from session or use first village
                if ($this->selectedVillageId) {
                    $this->currentVillage = $this->villages->find($this->selectedVillageId) ?? $this->villages->first();
                } else {
                    $this->currentVillage = $this->villages->first();
                }

                // Set SEO metadata for dashboard
                $this->seoService->setDashboardSeo($this->player);

                $this->loadRecentEvents();
                $this->loadGameStats();
            } else {
                $this->loadGameStats();  // Load default stats when no player
            }
        } finally {
            $this->isLoading = false;
        }
    }

    public function loadRecentEvents()
    {
        if ($this->player) {
            $this->recentEvents = GameEvent::byPlayer($this->player->id)
                ->withStats()
                ->withPlayerInfo()
                ->recent(7)
                ->orderBy('occurred_at', 'desc')
                ->limit(10)
                ->get();
        }
    }

    public function loadGameStats()
    {
        if ($this->player) {
            $this->gameStats = [
                'total_villages' => $this->player->villages()->count(),
                'total_points' => $this->player->points ?? 0,
                'alliance_name' => $this->player->alliance?->name ?? 'No Alliance',
                'online_status' => $this->player->is_online ? 'Online' : 'Offline',
                'last_active' => $this->player->last_active_at ? \Carbon\Carbon::parse($this->player->last_active_at)->diffForHumans() : 'Never',
                'total_population' => $this->player->villages()->sum('population'),
                'total_attack_points' => $this->player->total_attack_points ?? 0,
                'total_defense_points' => $this->player->total_defense_points ?? 0,
            ];
        } else {
            $this->gameStats = [
                'total_villages' => 0,
                'total_points' => 0,
                'alliance_name' => 'No Alliance',
                'online_status' => 'Offline',
                'last_active' => 'Never',
                'total_population' => 0,
                'total_attack_points' => 0,
                'total_defense_points' => 0,
            ];
        }
    }

    #[On('tick')]
    public function processGameTick()
    {
        if (! $this->autoRefresh) {
            return;
        }

        try {
            $gameTickService = app(\App\Services\GameTickService::class);
            $gameTickService->processGameTick();

            $this->loadGameData();
            $this->dispatch('gameTickProcessed');

            // Add notification
            $this->addNotification('Game tick processed successfully', 'success');
        } catch (\Exception $e) {
            $this->dispatch('gameTickError', ['message' => $e->getMessage()]);
            $this->addNotification('Game tick error: '.$e->getMessage(), 'error');
        }
    }

    public function refreshGameData()
    {
        $this->loadGameData();
        $this->addNotification('Game data refreshed', 'info');
        $this->dispatch('gameTickProcessed');
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = ! $this->autoRefresh;
        $this->addNotification(
            $this->autoRefresh ? 'Auto-refresh enabled' : 'Auto-refresh disabled',
            'info'
        );
    }

    public function setRefreshInterval($interval)
    {
        $this->refreshInterval = max(1, min(60, $interval));
        $this->addNotification("Refresh interval set to {$this->refreshInterval} seconds", 'info');
    }

    public function selectVillage($villageId)
    {
        $this->currentVillage = $this->villages->find($villageId);
        $this->selectedVillageId = $villageId; // Persist selection in session
        $this->dispatch('villageSelected', ['villageId' => $villageId]);
    }

    public function addNotification($message, $type = 'info')
    {
        $this->notifications[] = [
            'id' => uniqid(),
            'message' => $message,
            'type' => $type,
            'timestamp' => now(),
        ];

        // Keep only last 10 notifications
        $this->notifications = array_slice($this->notifications, -10);
    }

    public function removeNotification($notificationId)
    {
        $this->notifications = array_filter($this->notifications, function ($notification) use ($notificationId) {
            return $notification['id'] !== $notificationId;
        });
    }

    public function clearNotifications()
    {
        $this->notifications = [];
    }

    public function startPolling()
    {
        // Start polling for real-time updates every 30 seconds
        $this->dispatch('start-polling', ['interval' => 30000]);
    }

    public function stopPolling()
    {
        $this->dispatch('stop-polling');
    }

    public function handleRealTimeUpdate($data)
    {
        // Handle real-time updates from WebSocket or polling
        if (isset($data['resources'])) {
            $this->loadGameData();
            $this->addNotification('Resources updated in real-time', 'success');
        }

        if (isset($data['buildings'])) {
            $this->loadGameData();
            $this->addNotification('Building progress updated', 'info');
        }
    }

    #[On('buildingCompleted')]
    public function handleBuildingCompleted($data)
    {
        $this->addNotification("Building completed: {$data['building_name']}", 'success');
        $this->loadGameData();
    }

    #[On('resourceUpdated')]
    public function handleResourceUpdated($data)
    {
        $this->loadGameData();
    }

    #[On('villageUpdated')]
    public function handleVillageUpdated($data)
    {
        $this->loadGameData();
    }

    public function calculateResourceProductionRates()
    {
        if (! $this->currentVillage) {
            return;
        }

        $this->resourceProductionRates = [];
        foreach ($this->currentVillage->resources as $resource) {
            $this->resourceProductionRates[$resource->type] = [
                'current' => $resource->amount,
                'production' => $resource->production_rate,
                'capacity' => $resource->storage_capacity,
                'percentage' => min(100, ($resource->amount / $resource->storage_capacity) * 100),
            ];
        }
    }

    public function toggleRealTimeUpdates()
    {
        $this->realTimeUpdates = ! $this->realTimeUpdates;
        $this->addNotification(
            $this->realTimeUpdates ? 'Real-time updates enabled' : 'Real-time updates disabled',
            'info'
        );
    }

    public function toggleGameNotifications(): void
    {
        $this->showNotifications = ! $this->showNotifications;
        $this->addNotification(
            $this->showNotifications ? 'Notifications enabled' : 'Notifications disabled',
            'info'
        );
    }

    public function setGameSpeed($speed)
    {
        $this->gameSpeed = max(0.5, min(3.0, $speed));
        $this->addNotification("Game speed set to {$this->gameSpeed}x", 'info');
    }

    /**
     * Update dashboard layout preference
     */
    public function setDashboardLayout($layout)
    {
        $this->dashboardLayout = in_array($layout, ['grid', 'list', 'compact']) ? $layout : 'grid';
        $this->addNotification("Dashboard layout set to {$this->dashboardLayout}", 'info');
    }

    /**
     * Update resource view mode
     */
    public function setResourceViewMode($mode)
    {
        $this->resourceViewMode = in_array($mode, ['detailed', 'compact', 'minimal']) ? $mode : 'detailed';
        $this->addNotification("Resource view mode set to {$this->resourceViewMode}", 'info');
    }

    /**
     * Update building view mode
     */
    public function setBuildingViewMode($mode)
    {
        $this->buildingViewMode = in_array($mode, ['list', 'grid', 'detailed']) ? $mode : 'list';
        $this->addNotification("Building view mode set to {$this->buildingViewMode}", 'info');
    }

    /**
     * Update event filters
     */
    public function updateEventFilters(array $filters)
    {
        $this->eventFilters = array_filter($filters, fn ($value) => ! empty($value));
        $this->loadRecentEvents();
        $this->addNotification('Event filters updated', 'info');
    }

    /**
     * Update village filters
     */
    public function updateVillageFilters(array $filters)
    {
        $this->villageFilters = array_filter($filters, fn ($value) => ! empty($value));
        $this->addNotification('Village filters updated', 'info');
    }

    /**
     * Toggle notification type visibility
     */
    public function toggleNotificationType($type)
    {
        if (in_array($type, $this->notificationTypes)) {
            $this->notificationTypes = array_filter($this->notificationTypes, fn ($t) => $t !== $type);
        } else {
            $this->notificationTypes[] = $type;
        }
        $this->addNotification("Notification type {$type} " . (in_array($type, $this->notificationTypes) ? 'enabled' : 'disabled'), 'info');
    }

    /**
     * Reset all game preferences to defaults
     */
    public function resetGamePreferences()
    {
        $this->selectedVillageId = null;
        $this->gameSpeed = 1;
        $this->showNotifications = true;
        $this->realTimeUpdates = true;
        $this->notificationTypes = ['success', 'info', 'warning', 'error'];
        $this->dashboardLayout = 'grid';
        $this->resourceViewMode = 'detailed';
        $this->buildingViewMode = 'list';
        $this->eventFilters = [];
        $this->villageFilters = [];

        // Reset base session properties
        $this->resetSessionProperties();

        $this->addNotification('All preferences reset to defaults', 'info');
    }

    public function getResourceIcon($type)
    {
        $icons = [
            'wood' => '🌲',
            'clay' => '🏺',
            'iron' => '⚒️',
            'crop' => '🌾',
        ];

        return $icons[$type] ?? '📦';
    }

    public function getBuildingIcon($buildingType)
    {
        $icons = [
            'main_building' => '🏛️',
            'barracks' => '🏰',
            'stable' => '🐎',
            'workshop' => '🔨',
            'academy' => '🎓',
            'smithy' => '⚒️',
            'rally_point' => '🚩',
            'marketplace' => '🏪',
            'residence' => '🏠',
            'palace' => '👑',
            'treasury' => '💰',
            'trade_office' => '📊',
            'great_barracks' => '🏰',
            'great_stable' => '🐎',
            'city_wall' => '🧱',
            'earth_wall' => '🌍',
            'palisade' => '🪵',
            'stonemason' => '🗿',
            'brewery' => '🍺',
            'trapper' => '🪤',
            'great_warehouse' => '📦',
            'great_granary' => '🌾',
            'wonder_of_the_world' => '🏛️',
            'horse_drinking_trough' => '🐎',
            'brewery' => '🍺',
            'bakery' => '🍞',
            'brickworks' => '🧱',
            'iron_foundry' => '⚒️',
            'armoury' => '🛡️',
            'grain_mill' => '🌾',
            'sawmill' => '🌲',
            'clay_pit' => '🏺',
            'iron_mine' => '⛏️',
            'crop_field' => '🌾',
            'warehouse' => '📦',
            'granary' => '🌾',
        ];

        return $icons[$buildingType] ?? '🏗️';
    }

    public function render()
    {
        return view('livewire.game.game-dashboard', [
            'player' => $this->player,
            'currentVillage' => $this->currentVillage,
            'villages' => $this->villages,
            'recentEvents' => $this->recentEvents,
            'gameStats' => $this->gameStats,
            'notifications' => $this->notifications,
            'isLoading' => $this->isLoading,
            'resourceProductionRates' => $this->resourceProductionRates,
            'worldTime' => $this->worldTime,
            'gameSpeed' => $this->gameSpeed,
        ]);
    }
}
