<?php

namespace App\Livewire\Game;

use App\Models\Game\Building;
use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Services\QueryOptimizationService;
use Illuminate\Support\Facades\Auth;
use SmartCache\Facades\SmartCache;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class BuildingManager extends Component
{
    #[Reactive]
    public $village;

    public $buildings = [];
    public $availableBuildings = [];
    public $selectedBuilding = null;
    public $upgradeCosts = [];
    public $constructionQueue = [];
    public $showUpgradeModal = false;
    public $realTimeUpdates = true;
    public $autoRefresh = true;
    public $refreshInterval = 5;
    public $gameSpeed = 1;
    public $notifications = [];
    public $buildingProgress = [];
    public $constructionHistory = [];
    public $isLoading = false;
    public $showDetails = false;
    public $selectedBuildingType = null;
    public $filterByType = null;
    public $sortBy = 'level';
    public $sortOrder = 'desc';
    public $searchQuery = '';
    public $showOnlyUpgradeable = false;
    public $showOnlyMaxLevel = false;

    public function mount($villageId = null)
    {
        if ($villageId) {
            $this->village = Village::withStats()
                ->withPlayerInfo()
                ->findOrFail($villageId);
        } else {
            $player = Player::where('user_id', Auth::id())
                ->with(['villages' => function ($query) {
                    $query->withStats()->withPlayerInfo();
                }])
                ->first();
            $this->village = $player?->villages->first();
        }

        $this->loadBuildings();
        $this->loadAvailableBuildings();
        $this->initializeBuildingFeatures();
    }

    public function initializeBuildingFeatures()
    {
        $this->calculateBuildingProgress();
        $this->initializeConstructionHistory();

        $this->dispatch('initializeBuildingRealTime', [
            'interval' => $this->refreshInterval * 1000,
            'autoRefresh' => $this->autoRefresh,
            'realTimeUpdates' => $this->realTimeUpdates,
        ]);
    }

    public function loadBuildings()
    {
        if (!$this->village) {
            return;
        }

        // Use SmartCache for building data with automatic optimization
        $buildingsCacheKey = "village_{$this->village->id}_buildings_data";
        $this->buildings = SmartCache::remember($buildingsCacheKey, now()->addMinutes(3), function () {
            return $this
                ->village
                ->buildings()
                ->with(['buildingType:id,name,description,costs,production_bonus'])
                ->selectRaw('
                    buildings.*,
                    (SELECT COUNT(*) FROM buildings b2 WHERE b2.village_id = buildings.village_id AND b2.is_active = 1) as total_buildings,
                    (SELECT AVG(level) FROM buildings b3 WHERE b3.village_id = buildings.village_id AND b3.is_active = 1) as avg_level,
                    (SELECT MAX(level) FROM buildings b4 WHERE b4.village_id = buildings.village_id AND b4.is_active = 1) as max_level
                ')
                ->where('is_active', true)
                ->get()
                ->keyBy('type')
                ->toArray();
        });
    }

    public function loadAvailableBuildings()
    {
        $this->availableBuildings = [
            'wood' => [
                'name' => 'Woodcutter',
                'description' => 'Produces wood',
                'base_cost' => ['wood' => 50, 'clay' => 30, 'iron' => 20, 'crop' => 10],
                'production_bonus' => 10,
            ],
            'clay' => [
                'name' => 'Clay Pit',
                'description' => 'Produces clay',
                'base_cost' => ['wood' => 30, 'clay' => 50, 'iron' => 20, 'crop' => 10],
                'production_bonus' => 10,
            ],
            'iron' => [
                'name' => 'Iron Mine',
                'description' => 'Produces iron',
                'base_cost' => ['wood' => 20, 'clay' => 30, 'iron' => 50, 'crop' => 10],
                'production_bonus' => 10,
            ],
            'crop' => [
                'name' => 'Cropland',
                'description' => 'Produces crop',
                'base_cost' => ['wood' => 10, 'clay' => 20, 'iron' => 10, 'crop' => 50],
                'production_bonus' => 10,
            ],
            'warehouse' => [
                'name' => 'Warehouse',
                'description' => 'Increases storage capacity',
                'base_cost' => ['wood' => 100, 'clay' => 80, 'iron' => 60, 'crop' => 40],
                'storage_bonus' => 1000,
            ],
            'granary' => [
                'name' => 'Granary',
                'description' => 'Increases crop storage',
                'base_cost' => ['wood' => 80, 'clay' => 100, 'iron' => 40, 'crop' => 60],
                'storage_bonus' => 1000,
            ],
        ];
    }

    public function selectBuilding($buildingType)
    {
        $this->selectedBuilding = $buildingType;
        $this->calculateUpgradeCosts();
        $this->showUpgradeModal = true;
    }

    public function calculateUpgradeCosts()
    {
        if (!$this->selectedBuilding || !$this->village) {
            return;
        }

        $currentLevel = $this->buildings[$this->selectedBuilding]['level'] ?? 0;
        $baseCost = $this->availableBuildings[$this->selectedBuilding]['base_cost'] ?? [];

        // Calculate costs based on current level (exponential growth)
        $this->upgradeCosts = [];
        foreach ($baseCost as $resource => $baseAmount) {
            $this->upgradeCosts[$resource] = $baseAmount * pow(1.5, $currentLevel);
        }
    }

    public function upgradeBuilding()
    {
        if (!$this->selectedBuilding || !$this->village) {
            return;
        }

        // Check if player has enough resources
        $canAfford = true;
        foreach ($this->upgradeCosts as $resource => $cost) {
            if ($this->village->{$resource} < $cost) {
                $canAfford = false;

                break;
            }
        }

        if (!$canAfford) {
            $this->dispatch('insufficient-resources', [
                'message' => 'Not enough resources to upgrade this building!',
            ]);

            return;
        }

        // Deduct resources
        foreach ($this->upgradeCosts as $resource => $cost) {
            $this->village->decrement($resource, $cost);
        }

        // Update or create building
        $building = Building::updateOrCreate(
            [
                'village_id' => $this->village->id,
                'type' => $this->selectedBuilding,
            ],
            [
                'level' => ($this->buildings[$this->selectedBuilding]['level'] ?? 0) + 1,
                'name' => $this->availableBuildings[$this->selectedBuilding]['name'],
                'is_active' => true,
            ]
        );

        // Update village stats
        $this->updateVillageStats($building);

        $this->loadBuildings();
        $this->showUpgradeModal = false;

        $this->dispatch('building-upgraded', [
            'building' => $building->type,
            'level' => $building->level,
        ]);

        $this->dispatch('resources-updated');
    }

    public function updateVillageStats($building)
    {
        if (!$this->village) {
            return;
        }

        $buildingType = $building->type;
        $level = $building->level;

        // Update production or storage based on building type
        if (in_array($buildingType, ['wood', 'clay', 'iron', 'crop'])) {
            // Resource production buildings
            $productionBonus = $this->availableBuildings[$buildingType]['production_bonus'] ?? 10;
            $this->village->increment('population', 1);
        } elseif (in_array($buildingType, ['warehouse', 'granary'])) {
            // Storage buildings
            $storageBonus = $this->availableBuildings[$buildingType]['storage_bonus'] ?? 1000;

            if ($buildingType === 'warehouse') {
                $this->village->increment('wood_capacity', $storageBonus);
                $this->village->increment('clay_capacity', $storageBonus);
                $this->village->increment('iron_capacity', $storageBonus);
            } else {
                $this->village->increment('crop_capacity', $storageBonus);
            }
        }

        // Update player population
        $player = Player::where('user_id', Auth::id())->first();
        if ($player) {
            $player->increment('population', 1);
        }
    }

    public function cancelUpgrade()
    {
        $this->showUpgradeModal = false;
        $this->selectedBuilding = null;
    }

    #[On('building-upgraded')]
    public function refreshBuildings()
    {
        $this->loadBuildings();
    }

    public function calculateBuildingProgress()
    {
        $this->buildingProgress = [];

        foreach ($this->constructionQueue as $queue) {
            $startTime = $queue['started_at'];
            $endTime = $queue['completed_at'];
            $now = now();

            if ($now->lt($endTime)) {
                $totalDuration = $endTime->diffInSeconds($startTime);
                $elapsed = $now->diffInSeconds($startTime);
                $progress = min(100, ($elapsed / $totalDuration) * 100);

                $this->buildingProgress[$queue['id']] = [
                    'progress' => $progress,
                    'remaining' => $endTime->diffInSeconds($now),
                    'building_name' => $queue['building_type'],
                    'target_level' => $queue['target_level'],
                ];
            }
        }
    }

    public function initializeConstructionHistory()
    {
        $this->constructionHistory = [];
    }

    public function toggleRealTimeUpdates()
    {
        $this->realTimeUpdates = !$this->realTimeUpdates;
        $this->addNotification(
            $this->realTimeUpdates ? 'Real-time updates enabled' : 'Real-time updates disabled',
            'info'
        );
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
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

    public function setGameSpeed($speed)
    {
        $this->gameSpeed = max(0.5, min(3.0, $speed));
        $this->addNotification("Game speed set to {$this->gameSpeed}x", 'info');
    }

    public function selectBuildingType($type)
    {
        $this->selectedBuildingType = $type;
        $this->showDetails = true;
    }

    public function toggleDetails()
    {
        $this->showDetails = !$this->showDetails;
    }

    public function filterByType($type)
    {
        $this->filterByType = $type;
        $this->addNotification("Filtering by type: {$type}", 'info');
    }

    public function clearFilters()
    {
        $this->filterByType = null;
        $this->searchQuery = '';
        $this->showOnlyUpgradeable = false;
        $this->showOnlyMaxLevel = false;
        $this->addNotification('All filters cleared', 'info');
    }

    public function sortBuildings($sortBy)
    {
        if ($this->sortBy === $sortBy) {
            $this->sortOrder = $this->sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $sortBy;
            $this->sortOrder = 'desc';
        }

        $this->addNotification("Sorted by {$sortBy} ({$this->sortOrder})", 'info');
    }

    public function searchBuildings()
    {
        if (empty($this->searchQuery)) {
            $this->addNotification('Search cleared', 'info');

            return;
        }

        $this->addNotification("Searching for: {$this->searchQuery}", 'info');
    }

    public function toggleUpgradeableFilter()
    {
        $this->showOnlyUpgradeable = !$this->showOnlyUpgradeable;
        $this->addNotification(
            $this->showOnlyUpgradeable ? 'Showing only upgradeable buildings' : 'Showing all buildings',
            'info'
        );
    }

    public function toggleMaxLevelFilter()
    {
        $this->showOnlyMaxLevel = !$this->showOnlyMaxLevel;
        $this->addNotification(
            $this->showOnlyMaxLevel ? 'Showing only max level buildings' : 'Showing all buildings',
            'info'
        );
    }

    public function getBuildingIcon($type)
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
            'warehouse' => '📦',
            'granary' => '🌾',
        ];

        return $icons[$type] ?? '🏗️';
    }

    public function getBuildingColor($building)
    {
        if ($building['is_upgrading']) {
            return 'orange';
        }

        if ($building['level'] >= $building['max_level']) {
            return 'green';
        }

        if ($building['level'] < $building['max_level']) {
            return 'blue';
        }

        return 'gray';
    }

    public function getBuildingStatus($building)
    {
        if ($building['is_upgrading']) {
            return 'Upgrading...';
        }

        if ($building['level'] >= $building['max_level']) {
            return 'Max Level';
        }

        if ($building['level'] < $building['max_level']) {
            return 'Ready to Upgrade';
        }

        return 'Unknown';
    }

    public function getUpgradeTime($building)
    {
        if ($building['level'] >= $building['max_level']) {
            return 'N/A';
        }

        // Calculate upgrade time based on building type and level
        $baseTime = 60;  // 1 minute base
        $levelMultiplier = pow(1.5, $building['level']);
        $totalTime = $baseTime * $levelMultiplier;

        return gmdate('H:i:s', $totalTime);
    }

    public function getUpgradeCost($building)
    {
        if ($building['level'] >= $building['max_level']) {
            return 'N/A';
        }

        // Calculate upgrade cost based on building type and level
        $baseCost = 100;
        $levelMultiplier = pow(1.2, $building['level']);

        return [
            'wood' => round($baseCost * $levelMultiplier),
            'clay' => round($baseCost * $levelMultiplier),
            'iron' => round($baseCost * $levelMultiplier),
            'crop' => round($baseCost * $levelMultiplier),
        ];
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

    #[On('gameTickProcessed')]
    public function handleGameTickProcessed()
    {
        if ($this->realTimeUpdates) {
            $this->loadBuildings();
            $this->calculateBuildingProgress();
        }
    }

    #[On('buildingUpgraded')]
    public function handleBuildingUpgraded($data)
    {
        $this->loadBuildings();
        $this->addNotification('Building upgraded successfully', 'success');
    }

    #[On('villageSelected')]
    public function handleVillageSelected($villageId)
    {
        $this->village = Village::findOrFail($villageId);
        $this->loadBuildings();
        $this->loadAvailableBuildings();
        $this->addNotification('Village selected - buildings updated', 'info');
    }

    public function render()
    {
        return view('livewire.game.building-manager', [
            'village' => $this->village,
            'buildings' => $this->buildings,
            'availableBuildings' => $this->availableBuildings,
            'selectedBuilding' => $this->selectedBuilding,
            'upgradeCosts' => $this->upgradeCosts,
            'constructionQueue' => $this->constructionQueue,
            'showUpgradeModal' => $this->showUpgradeModal,
            'realTimeUpdates' => $this->realTimeUpdates,
            'autoRefresh' => $this->autoRefresh,
            'refreshInterval' => $this->refreshInterval,
            'gameSpeed' => $this->gameSpeed,
            'notifications' => $this->notifications,
            'buildingProgress' => $this->buildingProgress,
            'constructionHistory' => $this->constructionHistory,
            'isLoading' => $this->isLoading,
            'showDetails' => $this->showDetails,
            'selectedBuildingType' => $this->selectedBuildingType,
            'filterByType' => $this->filterByType,
            'sortBy' => $this->sortBy,
            'sortOrder' => $this->sortOrder,
            'searchQuery' => $this->searchQuery,
            'showOnlyUpgradeable' => $this->showOnlyUpgradeable,
            'showOnlyMaxLevel' => $this->showOnlyMaxLevel,
        ]);
    }
}
