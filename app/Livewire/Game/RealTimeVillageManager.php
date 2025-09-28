<?php

namespace App\Livewire\Game;

use App\Models\Game\Building;
use App\Models\Game\BuildingQueue;
use App\Models\Game\BuildingType;
use App\Models\Game\TrainingQueue;
use App\Models\Game\UnitType;
use App\Models\Game\Village;
use App\Services\ResourceProductionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class RealTimeVillageManager extends Component
{
    use WithPagination;

    #[Locked]
    public $village;

    #[Locked]
    public $player;

    #[Url]
    public $selectedBuildingTypeId = null;

    #[Url]
    public $selectedUnitTypeId = null;

    public $resources = [];

    public $buildings = [];

    public $availableBuildings = [];

    public $buildingQueues = [];

    public $trainingQueues = [];

    public $availableUnits = [];

    public $selectedBuildingType = null;

    public $selectedUnitType = null;

    public $showBuildingModal = false;

    public $showTrainingModal = false;

    #[Url]
    public $autoRefresh = true;

    #[Url]
    public $refreshInterval = 5;

    public $isLoading = false;

    public $realTimeUpdates = true;

    public $buildingGrid = [];

    public $resourceProductionRates = [];

    public $storageCapacities = [];

    public $population = 0;

    public $maxPopulation = 0;

    public $culturePoints = 0;

    // Enhanced Livewire features
    public $pollingEnabled = true;

    public $lastUpdateTime;

    public $connectionStatus = 'connected';

    public $buildingProgress = [];

    public $trainingProgress = [];

    public $resourceHistory = [];

    public $villageEvents = [];

    public $notifications = [];

    public $showResourceDetails = false;

    public $showBuildingDetails = false;

    public $selectedBuilding = null;

    public $upgradeCosts = [];

    public $upgradeTimes = [];

    public $productionEfficiency = 1.0;

    public $villageBonuses = [];

    protected $listeners = [
        'refreshVillageData',
        'buildingCompleted',
        'trainingCompleted',
        'resourceUpdated',
        'villageUpdated',
        'buildingProgressUpdated',
        'trainingProgressUpdated',
        'resourceProductionUpdated',
        'villageEventOccurred',
        'connectionStatusChanged',
    ];

    #[Computed]
    public function totalResourceProduction()
    {
        $total = ['wood' => 0, 'clay' => 0, 'iron' => 0, 'crop' => 0];

        foreach ($this->resourceProductionRates as $resource => $rate) {
            $total[$resource] = $rate * $this->productionEfficiency;
        }

        return $total;
    }

    #[Computed]
    public function resourceUtilization()
    {
        $utilization = [];

        foreach ($this->resources as $type => $resource) {
            $capacity = $this->storageCapacities[$type] ?? 1;
            $amount = $resource['amount'] ?? 0;
            $utilization[$type] = $capacity > 0 ? ($amount / $capacity) * 100 : 0;
        }

        return $utilization;
    }

    #[Computed]
    public function buildingEfficiency()
    {
        $efficiency = 1.0;

        // Calculate efficiency based on building levels and bonuses
        foreach ($this->buildings as $building) {
            if ($building->buildingType->key === 'main_building') {
                $efficiency += ($building->level * 0.1);
            }
        }

        return min($efficiency, 2.0);  // Cap at 200% efficiency
    }

    #[Computed]
    public function villageScore()
    {
        $score = 0;

        // Calculate village score based on buildings and resources
        foreach ($this->buildings as $building) {
            $score += $building->level * 10;
        }

        $score += $this->population * 0.1;
        $score += $this->culturePoints * 0.05;

        return round($score);
    }

    public function mount(Village $village)
    {
        if (! Auth::check()) {
            return redirect('/login');
        }

        $this->village = $village;
        $this->player = Auth::user()->player ?? $village->player;
        $this->loadVillageData();
        $this->initializeRealTimeFeatures();
        $this->startPolling();
    }

    public function initializeRealTimeFeatures()
    {
        $this->loadResourceProductionRates();
        $this->loadStorageCapacities();
        $this->calculatePopulation();
        $this->loadBuildingGrid();

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
            $this->village->load([
                'resources',
                'buildings.buildingType:id,name,description,costs,production_bonus',
                'buildingQueues.buildingType:id,name,description',
                'trainingQueues.unitType:id,name,attack_power,defense_power',
            ]);

            $this->resources = $this->village->resources->keyBy('type');
            $this->buildings = $this->village->buildings;

            // Use optimized queries for queues
            $this->buildingQueues = $this
                ->village
                ->buildingQueues()
                ->where('status', 'in_progress')
                ->with('buildingType:id,name,description')
                ->selectRaw('
                    building_queues.*,
                    (SELECT COUNT(*) FROM building_queues bq2 WHERE bq2.village_id = building_queues.village_id AND bq2.status = "in_progress") as total_active_queues
                ')
                ->get();

            $this->trainingQueues = $this
                ->village
                ->trainingQueues()
                ->where('status', 'in_progress')
                ->with('unitType:id,name,attack_power,defense_power')
                ->selectRaw('
                    training_queues.*,
                    (SELECT COUNT(*) FROM training_queues tq2 WHERE tq2.village_id = training_queues.village_id AND tq2.status = "in_progress") as total_active_queues
                ')
                ->get();

            $this->loadAvailableBuildings();
            $this->loadAvailableUnits();
            $this->loadResourceProductionRates();
            $this->loadStorageCapacities();
            $this->calculatePopulation();
            $this->loadBuildingGrid();
        } catch (\Exception $e) {
            $this->addError('error', 'Failed to load village data: '.$e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function loadAvailableBuildings()
    {
        $this->availableBuildings = BuildingType::where('is_active', true)
            ->selectRaw('
                building_types.*,
                (SELECT COUNT(*) FROM buildings b WHERE b.building_type_id = building_types.id AND b.is_active = 1) as total_buildings,
                (SELECT AVG(level) FROM buildings b2 WHERE b2.building_type_id = building_types.id AND b2.is_active = 1) as avg_level
            ')
            ->orderBy('name')
            ->get();
    }

    public function loadAvailableUnits()
    {
        $this->availableUnits = UnitType::where('tribe', $this->player->tribe)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function loadResourceProductionRates()
    {
        $resourceService = app(ResourceProductionService::class);
        $this->resourceProductionRates = $resourceService->calculateResourceProduction($this->village);
    }

    public function loadStorageCapacities()
    {
        $resourceService = app(ResourceProductionService::class);
        $this->storageCapacities = $resourceService->calculateStorageCapacity($this->village);
    }

    public function calculatePopulation()
    {
        $this->population = $this->village->population;
        $this->maxPopulation = $this->buildings->sum(function ($building) {
            $population = is_string($building->buildingType->population)
                ? json_decode($building->buildingType->population, true)
                : $building->buildingType->population;

            return $population[$building->level] ?? 0;
        });
        $this->culturePoints = $this->village->culture_points;
    }

    public function loadBuildingGrid()
    {
        $this->buildingGrid = [];

        for ($y = 0; $y < 19; $y++) {
            for ($x = 0; $x < 19; $x++) {
                $building = $this->buildings->where('x', $x)->where('y', $y)->first();
                $this->buildingGrid[$y][$x] = $building ? [
                    'id' => $building->id,
                    'type' => $building->buildingType->key,
                    'name' => $building->buildingType->name,
                    'level' => $building->level,
                    'icon' => $this->getBuildingIcon($building->buildingType->key),
                    'is_upgrading' => $this->buildingQueues->where('building_id', $building->id)->isNotEmpty(),
                ] : null;
            }
        }
    }

    public function selectBuildingType($buildingTypeId)
    {
        $this->selectedBuildingType = BuildingType::find($buildingTypeId);
        $this->showBuildingModal = true;
    }

    public function selectUnitType($unitTypeId)
    {
        $this->selectedUnitType = UnitType::find($unitTypeId);
        $this->showTrainingModal = true;
    }

    public function buildBuilding($x, $y)
    {
        if (! $this->selectedBuildingType) {
            return;
        }

        // Check if position is available
        $existingBuilding = $this->buildings->where('x', $x)->where('y', $y)->first();
        if ($existingBuilding) {
            $this->addError('error', 'Position is already occupied');

            return;
        }

        // Check if player can afford the building
        $costs = json_decode($this->selectedBuildingType->costs, true);
        $resourceService = app(ResourceProductionService::class);

        if (! $resourceService->canAfford($this->village, $costs)) {
            $this->addError('error', 'Insufficient resources');

            return;
        }

        try {
            // Spend resources
            $resourceService->spendResources($this->village, $costs);

            // Create building
            $building = Building::create([
                'village_id' => $this->village->id,
                'building_type_id' => $this->selectedBuildingType->id,
                'name' => $this->selectedBuildingType->name,
                'level' => 1,
                'x' => $x,
                'y' => $y,
                'is_active' => true,
            ]);

            $this->loadVillageData();
            $this->dispatch('buildingCreated', ['buildingId' => $building->id]);
            $this->showBuildingModal = false;
        } catch (\Exception $e) {
            $this->addError('error', 'Failed to build: '.$e->getMessage());
        }
    }

    public function upgradeBuilding($buildingId)
    {
        $building = $this->buildings->find($buildingId);
        if (! $building) {
            return;
        }

        // Check if building can be upgraded
        if ($building->level >= $building->buildingType->max_level) {
            $this->addError('error', 'Building is already at maximum level');

            return;
        }

        // Check if there's already an upgrade in progress
        $existingQueue = $this->buildingQueues->where('building_id', $buildingId)->first();
        if ($existingQueue) {
            $this->addError('error', 'Building is already being upgraded');

            return;
        }

        // Calculate upgrade costs
        $costs = $this->calculateUpgradeCosts($building);
        $resourceService = app(ResourceProductionService::class);

        if (! $resourceService->canAfford($this->village, $costs)) {
            $this->addError('error', 'Insufficient resources');

            return;
        }

        try {
            // Spend resources
            $resourceService->spendResources($this->village, $costs);

            // Create building queue
            $queue = BuildingQueue::create([
                'village_id' => $this->village->id,
                'building_id' => $buildingId,
                'target_level' => $building->level + 1,
                'started_at' => now(),
                'completed_at' => now()->addSeconds($this->calculateUpgradeTime($building)),
                'costs' => json_encode($costs),
                'status' => 'in_progress',
            ]);

            $this->loadVillageData();
            $this->dispatch('buildingUpgradeStarted', ['queueId' => $queue->id]);
        } catch (\Exception $e) {
            $this->addError('error', 'Failed to start upgrade: '.$e->getMessage());
        }
    }

    public function trainUnits($unitTypeId, $quantity)
    {
        $unitType = UnitType::find($unitTypeId);
        if (! $unitType) {
            return;
        }

        // Calculate training costs
        $costs = json_decode($unitType->costs, true);
        $totalCosts = [];
        foreach ($costs as $resource => $amount) {
            $totalCosts[$resource] = $amount * $quantity;
        }

        $resourceService = app(ResourceProductionService::class);

        if (! $resourceService->canAfford($this->village, $totalCosts)) {
            $this->addError('error', 'Insufficient resources');

            return;
        }

        try {
            // Spend resources
            $resourceService->spendResources($this->village, $totalCosts);

            // Create training queue
            $queue = TrainingQueue::create([
                'village_id' => $this->village->id,
                'unit_type_id' => $unitTypeId,
                'quantity' => $quantity,
                'started_at' => now(),
                'completed_at' => now()->addSeconds($this->calculateTrainingTime($unitType, $quantity)),
                'costs' => json_encode($totalCosts),
                'status' => 'in_progress',
            ]);

            $this->loadVillageData();
            $this->dispatch('trainingStarted', ['queueId' => $queue->id]);
            $this->showTrainingModal = false;
        } catch (\Exception $e) {
            $this->addError('error', 'Failed to start training: '.$e->getMessage());
        }
    }

    public function calculateUpgradeCosts($building)
    {
        $baseCosts = json_decode($building->buildingType->costs, true);
        $level = $building->level;

        $costs = [];
        foreach ($baseCosts as $resource => $amount) {
            $costs[$resource] = $amount * pow(1.2, $level - 1);
        }

        return $costs;
    }

    public function calculateUpgradeTime($building)
    {
        $baseTime = 60;  // Base time in seconds
        $level = $building->level;

        return $baseTime * pow(1.5, $level - 1);
    }

    public function calculateTrainingTime($unitType, $quantity)
    {
        $baseTime = 30;  // Base time in seconds per unit

        return $baseTime * $quantity;
    }

    public function startPolling()
    {
        if ($this->autoRefresh && $this->realTimeUpdates) {
            $this->dispatch('startVillagePolling', [
                'interval' => $this->refreshInterval * 1000,
            ]);
        }
    }

    public function refreshVillageData()
    {
        $this->loadVillageData();
        $this->dispatch('villageDataRefreshed');
    }

    public function processVillageTick()
    {
        try {
            $resourceService = app(ResourceProductionService::class);
            $resourceService->updateVillageResources($this->village);

            $this->loadVillageData();
            $this->dispatch('villageTickProcessed');
        } catch (\Exception $e) {
            $this->dispatch('villageTickError', ['message' => $e->getMessage()]);
        }
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
            'smithy' => '⚒️',
            'rally_point' => '🚩',
            default => '🏗️'
        };
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

    public function render()
    {
        return view('livewire.game.real-time-village-manager', [
            'village' => $this->village,
            'player' => $this->player,
            'resources' => $this->resources,
            'buildings' => $this->buildings,
            'availableBuildings' => $this->availableBuildings,
            'buildingQueues' => $this->buildingQueues,
            'trainingQueues' => $this->trainingQueues,
            'availableUnits' => $this->availableUnits,
            'selectedBuildingType' => $this->selectedBuildingType,
            'selectedUnitType' => $this->selectedUnitType,
            'buildingGrid' => $this->buildingGrid,
            'resourceProductionRates' => $this->resourceProductionRates,
            'storageCapacities' => $this->storageCapacities,
            'population' => $this->population,
            'maxPopulation' => $this->maxPopulation,
            'culturePoints' => $this->culturePoints,
        ]);
    }
}
