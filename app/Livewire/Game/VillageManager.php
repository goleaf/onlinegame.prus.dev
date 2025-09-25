<?php

namespace App\Livewire\Game;

use App\Models\Game\Building;
use App\Models\Game\BuildingQueue;
use App\Models\Game\BuildingType;
use App\Models\Game\Resource;
use App\Models\Game\Village;
use App\Services\GameTickService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
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

    protected $listeners = [
        'refreshVillage',
        'buildingUpgraded',
        'resourceUpdated',
        'buildingCompleted',
        'villageSelected'
    ];

    public function mount($village)
    {
        $this->village = Village::with(['buildings.buildingType', 'resources', 'player'])
            ->findOrFail($village);

        $this->loadVillageData();
        $this->startVillagePolling();
    }

    public function loadVillageData()
    {
        $this->isLoading = true;

        try {
            $this->buildings = $this->village->buildings;
            $this->resources = $this->village->resources;
            $this->buildingTypes = BuildingType::where('is_active', true)->get();
            $this->buildingQueues = $this
                ->village
                ->buildingQueues()
                ->where('is_completed', false)
                ->with('buildingType')
                ->get();
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectBuilding($buildingId)
    {
        $this->selectedBuilding = Building::with('buildingType')->find($buildingId);
        $this->calculateUpgradeCost();
        $this->showUpgradeModal = true;
    }

    public function calculateUpgradeCost()
    {
        if (!$this->selectedBuilding)
            return;

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
        if (!$this->canUpgrade || !$this->selectedBuilding)
            return;

        try {
            // Create building queue
            $buildingQueue = BuildingQueue::create([
                'village_id' => $this->village->id,
                'building_id' => $this->selectedBuilding->id,
                'target_level' => $this->upgradeLevel,
                'started_at' => now(),
                'completed_at' => now()->addSeconds($this->calculateUpgradeTime()),
                'costs' => $this->upgradeCost,
                'status' => 'in_progress'
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
                'upgrade_started_at' => now()
            ]);

            $this->showUpgradeModal = false;
            $this->loadVillageData();

            $this->dispatch('buildingUpgradeStarted', [
                'building' => $this->selectedBuilding->name,
                'level' => $this->upgradeLevel
            ]);
        } catch (\Exception $e) {
            $this->dispatch('buildingUpgradeError', ['message' => $e->getMessage()]);
        }
    }

    public function calculateUpgradeTime()
    {
        if (!$this->selectedBuilding)
            return 0;

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
            'timestamp' => now()
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
            'isLoading' => $this->isLoading
        ]);
    }
}
