<?php

namespace App\Livewire\Game;

use App\Models\Game\Player;
use App\Models\Game\TrainingQueue;
use App\Models\Game\UnitType;
use App\Models\Game\Village;
use App\Services\QueryOptimizationService;
use Illuminate\Support\Facades\Auth;
use SmartCache\Facades\SmartCache;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;
use Livewire\WithPagination;

class TroopManager extends Component
{
    use WithPagination;

    #[Reactive]
    public $village;

    public $troops = [];
    public $unitTypes = [];
    public $trainingQueues = [];
    public $selectedUnitType = null;
    public $trainingQuantity = 1;
    public $trainingCost = [];
    public $canTrain = false;
    public $notifications = [];
    public $isLoading = false;
    public $realTimeUpdates = true;
    public $autoRefresh = true;
    public $refreshInterval = 5;
    public $gameSpeed = 1;
    public $trainingProgress = [];
    public $troopHistory = [];
    public $showDetails = false;
    public $selectedTroop = null;
    public $filterByType = null;
    public $sortBy = 'count';
    public $sortOrder = 'desc';
    public $searchQuery = '';
    public $showOnlyAvailable = false;
    public $showOnlyTraining = false;
    public $trainingMode = 'single';  // single, batch, continuous
    public $batchSize = 10;
    public $continuousTraining = false;

    protected $listeners = [
        'refreshTroops',
        'trainingCompleted',
        'villageSelected',
        'gameTickProcessed',
        'troopTrained',
        'troopDisbanded',
    ];

    public function mount($villageId = null)
    {
        if ($villageId) {
            $this->village = Village::withStats()
                ->with(['troops.unitType:id,name,attack_power,defense_power,speed,cost', 'player:id,name,tribe'])
                ->findOrFail($villageId);
        } else {
            $player = Player::where('user_id', Auth::id())
                ->with(['villages' => function ($query) {
                    $query
                        ->withStats()
                        ->with(['troops.unitType:id,name,attack_power,defense_power,speed,cost']);
                }])
                ->first();
            $this->village = $player?->villages->first();
        }

        if ($this->village) {
            $this->loadTroopData();
            $this->initializeTroopFeatures();
        }
    }

    public function initializeTroopFeatures()
    {
        $this->calculateTrainingProgress();
        $this->initializeTroopHistory();

        $this->dispatch('initializeTroopRealTime', [
            'interval' => $this->refreshInterval * 1000,
            'autoRefresh' => $this->autoRefresh,
            'realTimeUpdates' => $this->realTimeUpdates,
        ]);
    }

    public function loadTroopData()
    {
        $this->isLoading = true;

        try {
            // Use SmartCache for troop data with automatic optimization
            $troopsCacheKey = "village_{$this->village->id}_troops_data";
            $this->troops = SmartCache::remember($troopsCacheKey, now()->addMinutes(2), function () {
                return $this->village->troops;
            });

            // Use SmartCache for unit types with automatic optimization
            $unitTypesCacheKey = "tribe_{$this->village->player->tribe}_unit_types";
            $this->unitTypes = SmartCache::remember($unitTypesCacheKey, now()->addMinutes(10), function () {
                return UnitType::where('is_active', true)
                    ->where('tribe', $this->village->player->tribe)
                    ->selectRaw('
                        unit_types.*,
                        (SELECT COUNT(*) FROM troops t WHERE t.unit_type_id = unit_types.id AND t.quantity > 0) as total_troops,
                        (SELECT SUM(quantity) FROM troops t2 WHERE t2.unit_type_id = unit_types.id) as total_quantity,
                        (SELECT AVG(quantity) FROM troops t3 WHERE t3.unit_type_id = unit_types.id AND t3.quantity > 0) as avg_quantity
                    ')
                    ->get();
            });

            // Use SmartCache for training queues with automatic optimization
            $trainingQueuesCacheKey = "village_{$this->village->id}_training_queues";
            $this->trainingQueues = SmartCache::remember($trainingQueuesCacheKey, now()->addMinutes(1), function () {
                return $this
                    ->village
                    ->trainingQueues()
                    ->where('is_completed', false)
                    ->with('unitType:id,name,attack_power,defense_power,speed')
                    ->selectRaw('
                        training_queues.*,
                        (SELECT COUNT(*) FROM training_queues tq2 WHERE tq2.village_id = training_queues.village_id AND tq2.is_completed = 0) as total_active_queues
                    ')
                    ->get();
            });
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectUnitType($unitTypeId)
    {
        $this->selectedUnitType = UnitType::selectRaw('
                unit_types.*,
                (SELECT COUNT(*) FROM troops t WHERE t.unit_type_id = unit_types.id AND t.quantity > 0) as total_troops,
                (SELECT SUM(quantity) FROM troops t2 WHERE t2.unit_type_id = unit_types.id) as total_quantity,
                (SELECT AVG(quantity) FROM troops t3 WHERE t3.unit_type_id = unit_types.id AND t3.quantity > 0) as avg_quantity
            ')
            ->find($unitTypeId);
        $this->calculateTrainingCost();
    }

    public function calculateTrainingCost()
    {
        if (!$this->selectedUnitType) {
            return;
        }

        $baseCosts = $this->selectedUnitType->costs ?? [];
        $this->trainingCost = [
            'wood' => ($baseCosts['wood'] ?? 0) * $this->trainingQuantity,
            'clay' => ($baseCosts['clay'] ?? 0) * $this->trainingQuantity,
            'iron' => ($baseCosts['iron'] ?? 0) * $this->trainingQuantity,
            'crop' => ($baseCosts['crop'] ?? 0) * $this->trainingQuantity,
        ];

        $this->checkCanTrain();
    }

    public function setTrainingQuantity($quantity)
    {
        $this->trainingQuantity = max(1, $quantity);
        $this->calculateTrainingCost();
    }

    public function checkCanTrain()
    {
        $this->canTrain = true;

        // Check if player has enough resources
        foreach ($this->trainingCost as $resource => $cost) {
            $resourceAmount = $this
                ->village
                ->resources()
                ->where('type', $resource)
                ->first()
                ->amount ?? 0;

            if ($resourceAmount < $cost) {
                $this->canTrain = false;

                break;
            }
        }

        // Check if required buildings exist
        if ($this->selectedUnitType) {
            $requirements = $this->selectedUnitType->requirements ?? [];

            foreach ($requirements as $buildingKey => $level) {
                $building = $this
                    ->village
                    ->buildings()
                    ->whereHas('buildingType', function ($query) use ($buildingKey) {
                        $query->where('key', $buildingKey);
                    })
                    ->first();

                if (!$building || $building->level < $level) {
                    $this->canTrain = false;

                    break;
                }
            }
        }
    }

    public function startTraining()
    {
        if (!$this->canTrain || !$this->selectedUnitType) {
            return;
        }

        try {
            // Create training queue
            $trainingQueue = TrainingQueue::create([
                'village_id' => $this->village->id,
                'player_id' => $this->village->player_id,
                'unit_type_id' => $this->selectedUnitType->id,
                'quantity' => $this->trainingQuantity,
                'started_at' => now(),
                'completed_at' => now()->addSeconds($this->calculateTrainingTime()),
                'costs' => $this->trainingCost,
                'status' => 'in_progress',
            ]);

            // Deduct resources
            foreach ($this->trainingCost as $resource => $cost) {
                $resourceModel = $this->village->resources()->where('type', $resource)->first();
                if ($resourceModel) {
                    $resourceModel->decrement('amount', $cost);
                }
            }

            $this->loadTroopData();
            $this->addNotification(
                "Started training {$this->trainingQuantity} {$this->selectedUnitType->name}",
                'success'
            );

            $this->dispatch('trainingStarted', [
                'unit_name' => $this->selectedUnitType->name,
                'quantity' => $this->trainingQuantity,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Training failed: ' . $e->getMessage(), 'error');
        }
    }

    public function calculateTrainingTime()
    {
        if (!$this->selectedUnitType) {
            return 0;
        }

        $baseTime = 60;  // Base time in seconds
        $quantityMultiplier = $this->trainingQuantity;

        return $baseTime * $quantityMultiplier;
    }

    public function cancelTraining($trainingId)
    {
        try {
            $training = TrainingQueue::find($trainingId);

            if ($training && $training->village_id == $this->village->id) {
                $training->delete();
                $this->loadTroopData();
                $this->addNotification('Training cancelled', 'info');
            }
        } catch (\Exception $e) {
            $this->addNotification('Failed to cancel training: ' . $e->getMessage(), 'error');
        }
    }

    public function getTroopQuantity($unitTypeId)
    {
        $troop = $this->troops->where('unit_type_id', $unitTypeId)->first();

        return $troop ? $troop->quantity : 0;
    }

    public function getTotalTroops()
    {
        return $this->troops->sum('quantity');
    }

    public function getTotalAttackPower()
    {
        return $this->troops->sum(function ($troop) {
            return $troop->quantity * $troop->unitType->attack;
        });
    }

    public function getTotalDefensePower()
    {
        return $this->troops->sum(function ($troop) {
            return $troop->quantity * ($troop->unitType->defense_infantry + $troop->unitType->defense_cavalry);
        });
    }

    #[On('trainingCompleted')]
    public function handleTrainingCompleted($data)
    {
        if ($data['village_id'] == $this->village->id) {
            $this->addNotification(
                "Training completed: {$data['quantity']} {$data['unit_name']}",
                'success'
            );
            $this->loadTroopData();
        }
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

    public function refreshTroops()
    {
        $this->loadTroopData();
        $this->addNotification('Troop data refreshed', 'info');
    }

    public function calculateTrainingProgress()
    {
        $this->trainingProgress = [];

        foreach ($this->trainingQueues as $queue) {
            $startTime = $queue['started_at'];
            $endTime = $queue['completed_at'];
            $now = now();

            if ($now->lt($endTime)) {
                $totalDuration = $endTime->diffInSeconds($startTime);
                $elapsed = $now->diffInSeconds($startTime);
                $progress = min(100, ($elapsed / $totalDuration) * 100);

                $this->trainingProgress[$queue['id']] = [
                    'progress' => $progress,
                    'remaining' => $endTime->diffInSeconds($now),
                    'unit_name' => $queue['unit_type'],
                    'quantity' => $queue['quantity'],
                ];
            }
        }
    }

    public function initializeTroopHistory()
    {
        $this->troopHistory = [];
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

    public function selectTroop($troopId)
    {
        $this->selectedTroop = $troopId;
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
        $this->showOnlyAvailable = false;
        $this->showOnlyTraining = false;
        $this->addNotification('All filters cleared', 'info');
    }

    public function sortTroops($sortBy)
    {
        if ($this->sortBy === $sortBy) {
            $this->sortOrder = $this->sortOrder === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $sortBy;
            $this->sortOrder = 'desc';
        }

        $this->addNotification("Sorted by {$sortBy} ({$this->sortOrder})", 'info');
    }

    public function searchTroops()
    {
        if (empty($this->searchQuery)) {
            $this->addNotification('Search cleared', 'info');

            return;
        }

        $this->addNotification("Searching for: {$this->searchQuery}", 'info');
    }

    public function toggleAvailableFilter()
    {
        $this->showOnlyAvailable = !$this->showOnlyAvailable;
        $this->addNotification(
            $this->showOnlyAvailable ? 'Showing only available troops' : 'Showing all troops',
            'info'
        );
    }

    public function toggleTrainingFilter()
    {
        $this->showOnlyTraining = !$this->showOnlyTraining;
        $this->addNotification(
            $this->showOnlyTraining ? 'Showing only training troops' : 'Showing all troops',
            'info'
        );
    }

    public function setTrainingMode($mode)
    {
        $this->trainingMode = $mode;
        $this->addNotification("Training mode set to: {$mode}", 'info');
    }

    public function setBatchSize($size)
    {
        $this->batchSize = max(1, min(100, $size));
        $this->addNotification("Batch size set to: {$this->batchSize}", 'info');
    }

    public function toggleContinuousTraining()
    {
        $this->continuousTraining = !$this->continuousTraining;
        $this->addNotification(
            $this->continuousTraining ? 'Continuous training enabled' : 'Continuous training disabled',
            'info'
        );
    }

    public function getUnitIcon($type)
    {
        $icons = [
            'legionnaire' => '⚔️',
            'praetorian' => '🛡️',
            'imperian' => '👑',
            'equites_legati' => '🐎',
            'equites_imperatoris' => '🏇',
            'equites_caesaris' => '👑',
            'ram' => '🐏',
            'fire_catapult' => '🔥',
            'senator' => '👨‍💼',
            'settler' => '🏘️',
            'clubswinger' => '🏏',
            'spearman' => '🔱',
            'axeman' => '🪓',
            'scout' => '👁️',
            'paladin' => '⚔️',
            'teutonic_knight' => '⚔️',
            'ram' => '🐏',
            'catapult' => '💥',
            'chief' => '👑',
            'settler' => '🏘️',
            'phalanx' => '🛡️',
            'swordsman' => '⚔️',
            'pathfinder' => '🗺️',
            'theutates_thunder' => '⚡',
            'druidrider' => '🌿',
            'haeduan' => '🏹',
            'ram' => '🐏',
            'trebuchet' => '💥',
            'chieftain' => '👑',
            'settler' => '🏘️',
        ];

        return $icons[$type] ?? '⚔️';
    }

    public function getUnitColor($unit)
    {
        if ($unit['is_training']) {
            return 'orange';
        }

        if ($unit['count'] > 0) {
            return 'green';
        }

        return 'gray';
    }

    public function getUnitStatus($unit)
    {
        if ($unit['is_training']) {
            return 'Training...';
        }

        if ($unit['count'] > 0) {
            return 'Available';
        }

        return 'Not Available';
    }

    public function getTrainingTime($unitType, $quantity)
    {
        $baseTime = 60;  // 1 minute base
        $quantityMultiplier = $quantity;
        $totalTime = $baseTime * $quantityMultiplier;

        return gmdate('H:i:s', $totalTime);
    }

    public function getTrainingCost($unitType, $quantity)
    {
        $unit = $this->unitTypes[$unitType] ?? null;
        if (!$unit) {
            return [];
        }

        return [
            'wood' => ($unit['wood_cost'] ?? 0) * $quantity,
            'clay' => ($unit['clay_cost'] ?? 0) * $quantity,
            'iron' => ($unit['iron_cost'] ?? 0) * $quantity,
            'crop' => ($unit['crop_cost'] ?? 0) * $quantity,
        ];
    }

    public function canAffordTraining($unitType, $quantity)
    {
        $cost = $this->getTrainingCost($unitType, $quantity);
        $resources = $this->village->resources;

        foreach ($cost as $resource => $amount) {
            $resourceModel = $resources->where('type', $resource)->first();
            if (!$resourceModel || $resourceModel->amount < $amount) {
                return false;
            }
        }

        return true;
    }

    public function startBatchTraining()
    {
        if ($this->trainingMode !== 'batch') {
            $this->addNotification('Batch training mode not enabled', 'error');

            return;
        }

        $this->addNotification("Starting batch training of {$this->batchSize} units", 'info');
    }

    public function startContinuousTraining()
    {
        if (!$this->continuousTraining) {
            $this->addNotification('Continuous training not enabled', 'error');

            return;
        }

        $this->addNotification('Starting continuous training', 'info');
    }

    public function stopContinuousTraining()
    {
        $this->continuousTraining = false;
        $this->addNotification('Continuous training stopped', 'info');
    }

    public function disbandTroops($unitType, $quantity)
    {
        if ($quantity <= 0) {
            $this->addNotification('Invalid quantity for disbanding', 'error');

            return;
        }

        $troop = $this->troops->where('unit_type', $unitType)->first();
        if (!$troop || $troop->count < $quantity) {
            $this->addNotification('Not enough troops to disband', 'error');

            return;
        }

        $troop->decrement('count', $quantity);
        $this->loadTroopData();
        $this->addNotification("Disbanded {$quantity} {$unitType}", 'success');

        $this->dispatch('troopDisbanded', [
            'unit_type' => $unitType,
            'quantity' => $quantity,
        ]);
    }

    #[On('gameTickProcessed')]
    public function handleGameTickProcessed()
    {
        if ($this->realTimeUpdates) {
            $this->loadTroopData();
            $this->calculateTrainingProgress();
        }
    }

    #[On('troopTrained')]
    public function handleTroopTrained($data)
    {
        $this->loadTroopData();
        $this->addNotification('Troop training completed', 'success');
    }

    #[On('troopDisbanded')]
    public function handleTroopDisbanded($data)
    {
        $this->loadTroopData();
        $this->addNotification('Troops disbanded', 'info');
    }

    #[On('villageSelected')]
    public function handleVillageSelected($villageId)
    {
        $this->village = Village::findOrFail($villageId);
        $this->loadTroopData();
        $this->addNotification('Village selected - troops updated', 'info');
    }

    public function render()
    {
        return view('livewire.game.troop-manager', [
            'village' => $this->village,
            'troops' => $this->troops,
            'unitTypes' => $this->unitTypes,
            'trainingQueues' => $this->trainingQueues,
            'selectedUnitType' => $this->selectedUnitType,
            'trainingQuantity' => $this->trainingQuantity,
            'trainingCost' => $this->trainingCost,
            'canTrain' => $this->canTrain,
            'notifications' => $this->notifications,
            'isLoading' => $this->isLoading,
            'realTimeUpdates' => $this->realTimeUpdates,
            'autoRefresh' => $this->autoRefresh,
            'refreshInterval' => $this->refreshInterval,
            'gameSpeed' => $this->gameSpeed,
            'trainingProgress' => $this->trainingProgress,
            'troopHistory' => $this->troopHistory,
            'showDetails' => $this->showDetails,
            'selectedTroop' => $this->selectedTroop,
            'filterByType' => $this->filterByType,
            'sortBy' => $this->sortBy,
            'sortOrder' => $this->sortOrder,
            'searchQuery' => $this->searchQuery,
            'showOnlyAvailable' => $this->showOnlyAvailable,
            'showOnlyTraining' => $this->showOnlyTraining,
            'trainingMode' => $this->trainingMode,
            'batchSize' => $this->batchSize,
            'continuousTraining' => $this->continuousTraining,
        ]);
    }
}
