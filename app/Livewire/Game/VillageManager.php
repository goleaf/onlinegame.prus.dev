<?php

namespace App\Livewire\Game;

use App\Models\Game\Building;
use App\Models\Game\BuildingQueue;
use App\Models\Game\BuildingType;
use App\Models\Game\Resource;
use App\Models\Game\Village;
use App\Services\QueryOptimizationService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class VillageManager extends Component
{
    use WithPagination;

    public $village;

    public $buildings = [];

    public $resources = [];

    public $buildingTypes = [];

    public $selectedBuilding = null;

    public $showUpgradeModal = false;

    public $upgradeLevel = 1;

    public $upgradeCost = [];

    public $canUpgrade = false;

    public $buildingQueues = [];

    public $notifications = [];

    public $isLoading = false;

    public $autoRefresh = true;

    public $refreshInterval = 5;

    public $realTimeUpdates = true;

    public $showNotifications = true;

    public $gameSpeed = 1;

    public $buildingProgress = [];

    public $resourceProductionRates = [];

    protected $listeners = [
        'refreshVillage',
        'buildingUpgraded',
        'resourceUpdated',
        'buildingCompleted',
        'villageSelected',
        'gameTickProcessed',
        'buildingProgressUpdated',
    ];

    public function mount($village)
    {
        $this->village = Village::withStats()
            ->with(['buildings.buildingType:id,name,description,costs,production_bonus', 'resources', 'player:id,name,points'])
            ->findOrFail($village);

        $this->loadVillageData();
        $this->initializeRealTimeFeatures();
        $this->startVillagePolling();
    }

    public function initializeRealTimeFeatures()
    {
        $this->calculateResourceProductionRates();
        $this->calculateBuildingProgress();

        // Dispatch initial real-time setup
        $this->dispatch('initializeVillageRealTime', [
            'interval' => $this->refreshInterval * 1000,
            'autoRefresh' => $this->autoRefresh,
            'realTimeUpdates' => $this->realTimeUpdates,
        ]);
    }

    public function loadVillageData()
    {
        $this->isLoading = true;

        try {
            $this->buildings = $this->village->buildings;
            $this->resources = $this->village->resources;

            // Use optimized query for building types
            $this->buildingTypes = BuildingType::where('is_active', true)
                ->selectRaw('
                    building_types.*,
                    (SELECT COUNT(*) FROM buildings b WHERE b.building_type_id = building_types.id AND b.is_active = 1) as total_buildings,
                    (SELECT AVG(level) FROM buildings b2 WHERE b2.building_type_id = building_types.id AND b2.is_active = 1) as avg_level
                ')
                ->get();

            // Use optimized query for building queues
            $this->buildingQueues = $this
                ->village
                ->buildingQueues()
                ->where('is_completed', false)
                ->with('buildingType:id,name,description')
                ->selectRaw('
                    building_queues.*,
                    (SELECT COUNT(*) FROM building_queues bq2 WHERE bq2.village_id = building_queues.village_id AND bq2.is_completed = 0) as total_active_queues
                ')
                ->get();
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectBuilding($buildingId)
    {
        $this->selectedBuilding = Building::with('buildingType:id,name,description,costs,production_bonus')
            ->selectRaw('
                buildings.*,
                (SELECT COUNT(*) FROM buildings b2 WHERE b2.village_id = buildings.village_id AND b2.building_type_id = buildings.building_type_id) as same_type_count,
                (SELECT AVG(level) FROM buildings b3 WHERE b3.village_id = buildings.village_id AND b3.building_type_id = buildings.building_type_id) as avg_type_level
            ')
            ->find($buildingId);
        $this->calculateUpgradeCost();
        $this->showUpgradeModal = true;
        
        // Track building selection
        $this->dispatch('fathom-track', name: 'building selected', value: $buildingId);
    }

    public function calculateUpgradeCost()
    {
        if (!$this->selectedBuilding) {
            return;
        }

        $currentLevel = $this->selectedBuilding->level;
        $this->upgradeLevel = $currentLevel + 1;

        // Calculate costs based on building type and level
        $baseCosts = $this->selectedBuilding->buildingType->costs ?? [];
        $this->upgradeCost = [
            'wood' => $baseCosts['wood'] ?? 0,
            'clay' => $baseCosts['clay'] ?? 0,
            'iron' => $baseCosts['iron'] ?? 0,
            'crop' => $baseCosts['crop'] ?? 0,
        ];

        // Scale costs by level
        foreach ($this->upgradeCost as $resource => $cost) {
            $this->upgradeCost[$resource] = $cost * pow(1.5, $currentLevel);
        }

        $this->checkCanUpgrade();
    }

    public function checkCanUpgrade()
    {
        $this->canUpgrade = true;

        foreach ($this->upgradeCost as $resource => $cost) {
            $resourceAmount = $this->resources->where('type', $resource)->first()->amount ?? 0;
            if ($resourceAmount < $cost) {
                $this->canUpgrade = false;

                break;
            }
        }
    }

    public function upgradeBuilding()
    {
        if (!$this->canUpgrade || !$this->selectedBuilding) {
            return;
        }

        try {
            // Create building queue
            $buildingQueue = BuildingQueue::create([
                'village_id' => $this->village->id,
                'building_id' => $this->selectedBuilding->id,
                'target_level' => $this->upgradeLevel,
                'started_at' => now(),
                'completed_at' => now()->addSeconds($this->calculateUpgradeTime()),
                'costs' => $this->upgradeCost,
                'status' => 'in_progress',
            ]);

            // Deduct resources
            foreach ($this->upgradeCost as $resource => $cost) {
                $resourceModel = $this->resources->where('type', $resource)->first();
                if ($resourceModel) {
                    $resourceModel->decrement('amount', $cost);
                }
            }

            // Update building
            $this->selectedBuilding->update([
                'upgrade_started_at' => now(),
            ]);

            $this->showUpgradeModal = false;
            $this->loadVillageData();

            // Track building upgrade
            $totalCost = array_sum($this->upgradeCost);
            $this->dispatch('fathom-track', name: 'building upgrade started', value: $totalCost);

            $this->dispatch('buildingUpgradeStarted', [
                'building' => $this->selectedBuilding->name,
                'level' => $this->upgradeLevel,
            ]);
        } catch (\Exception $e) {
            $this->dispatch('buildingUpgradeError', ['message' => $e->getMessage()]);
        }
    }

    public function calculateUpgradeTime()
    {
        if (!$this->selectedBuilding) {
            return 0;
        }

        $baseTime = 60;  // Base time in seconds
        $levelMultiplier = pow(1.5, $this->selectedBuilding->level);

        return $baseTime * $levelMultiplier;
    }

    public function cancelUpgrade($buildingId)
    {
        $building = Building::find($buildingId);
        if ($building && $building->upgrade_started_at) {
            $building->update(['upgrade_started_at' => null]);
            $this->loadVillageData();
        }
    }

    #[On('villageSelected')]
    public function handleVillageSelected($data)
    {
        if ($data['villageId'] == $this->village->id) {
            $this->loadVillageData();
        }
    }

    #[On('buildingCompleted')]
    public function handleBuildingCompleted($data)
    {
        if ($data['village_id'] == $this->village->id) {
            $this->addNotification("Building completed: {$data['building_name']} Level {$data['level']}", 'success');
            $this->loadVillageData();
        }
    }

    #[On('resourceUpdated')]
    public function handleResourceUpdated()
    {
        $this->loadVillageData();
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

    public function startVillagePolling()
    {
        // Start polling for village updates every 10 seconds
        $this->dispatch('start-village-polling', ['interval' => 10000]);
    }

    public function stopVillagePolling()
    {
        $this->dispatch('stop-village-polling');
    }

    public function handleBuildingProgress()
    {
        // Check for building progress updates
        $this->loadVillageData();
        $this->addNotification('Building progress updated', 'info');
    }

    public function handleResourceProduction()
    {
        // Handle resource production updates
        $this->loadVillageData();
        $this->addNotification('Resource production updated', 'success');
    }

    public function refreshVillage()
    {
        $this->loadVillageData();
        $this->addNotification('Village data refreshed', 'info');
    }

    public function calculateResourceProductionRates()
    {
        if (!$this->village) {
            return;
        }

        $this->resourceProductionRates = [];
        foreach ($this->village->resources as $resource) {
            $this->resourceProductionRates[$resource->type] = [
                'current' => $resource->amount,
                'production' => $resource->production_rate,
                'capacity' => $resource->storage_capacity,
                'percentage' => min(100, ($resource->amount / $resource->storage_capacity) * 100),
            ];
        }
    }

    public function calculateBuildingProgress()
    {
        $this->buildingProgress = [];

        foreach ($this->buildingQueues as $queue) {
            $startTime = $queue->started_at;
            $endTime = $queue->completed_at;
            $now = now();

            if ($now->lt($endTime)) {
                $totalDuration = $endTime->diffInSeconds($startTime);
                $elapsed = $now->diffInSeconds($startTime);
                $progress = min(100, ($elapsed / $totalDuration) * 100);

                $this->buildingProgress[$queue->id] = [
                    'progress' => $progress,
                    'remaining' => $endTime->diffInSeconds($now),
                    'building_name' => $queue->buildingType->name,
                    'target_level' => $queue->target_level,
                ];
            }
        }
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
            'warehouse' => '📦',
            'granary' => '🌾',
        ];

        return $icons[$buildingType] ?? '🏗️';
    }

    #[On('buildingProgressUpdated')]
    public function handleBuildingProgressUpdated($data)
    {
        $this->calculateBuildingProgress();
        $this->addNotification('Building progress updated', 'info');
    }

    #[On('gameTickProcessed')]
    public function handleGameTickProcessed()
    {
        $this->loadVillageData();
        $this->calculateResourceProductionRates();
        $this->calculateBuildingProgress();
    }

    public function render()
    {
        return view('livewire.game.village-manager', [
            'village' => $this->village,
            'buildings' => $this->buildings,
            'resources' => $this->resources,
            'buildingTypes' => $this->buildingTypes,
            'selectedBuilding' => $this->selectedBuilding,
            'upgradeCost' => $this->upgradeCost,
            'canUpgrade' => $this->canUpgrade,
            'buildingQueues' => $this->buildingQueues,
            'notifications' => $this->notifications,
            'isLoading' => $this->isLoading,
            'resourceProductionRates' => $this->resourceProductionRates,
            'buildingProgress' => $this->buildingProgress,
            'autoRefresh' => $this->autoRefresh,
            'refreshInterval' => $this->refreshInterval,
            'realTimeUpdates' => $this->realTimeUpdates,
            'gameSpeed' => $this->gameSpeed,
        ]);
    }
}
