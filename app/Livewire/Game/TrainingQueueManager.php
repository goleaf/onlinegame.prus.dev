<?php

namespace App\Livewire\Game;

use App\Models\Game\Village;
use App\Models\Game\UnitType;
use App\Models\Game\TrainingQueue;
use App\Services\TrainingQueueService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Training Queue Manager')]
#[Layout('layouts.game')]
class TrainingQueueManager extends Component
{
    use WithPagination;

    public $village;
    public $selectedUnitType = null;
    public $trainingQuantity = 1;
    public $trainingCost = [];
    public $canTrain = false;
    public $trainingTime = 0;
    public $notifications = [];
    public $activeTab = 'active';
    public $search = '';
    public $filterUnitType = '';
    public $filterStatus = '';

    protected $trainingQueueService;

    protected $listeners = [
        'trainingCompleted' => 'refreshQueues',
        'trainingCancelled' => 'refreshQueues',
    ];

    public function boot()
    {
        $this->trainingQueueService = new TrainingQueueService();
    }

    public function mount($villageId = null)
    {
        if ($villageId) {
            $this->village = Village::findOrFail($villageId);
        } else {
            $player = \App\Models\Game\Player::where('user_id', Auth::id())->first();
            $this->village = $player->villages()->first();
        }

        if (!$this->village) {
            abort(404, 'No village found');
        }

        $this->loadTrainingData();
    }

    public function render()
    {
        $unitTypes = $this->getUnitTypes();
        $trainingStats = $this->getTrainingStats();
        $queues = $this->getQueues();

        return view('livewire.game.training-queue-manager', [
            'unitTypes' => $unitTypes,
            'trainingStats' => $trainingStats,
            'queues' => $queues,
        ]);
    }

    public function selectUnitType($unitTypeId)
    {
        $this->selectedUnitType = UnitType::find($unitTypeId);
        $this->calculateTrainingCost();
    }

    public function updateQuantity()
    {
        if ($this->selectedUnitType) {
            $this->calculateTrainingCost();
        }
    }

    public function startTraining()
    {
        if (!$this->canTrain || !$this->selectedUnitType) {
            $this->addNotification('Cannot start training', 'error');
            return;
        }

        try {
            $trainingQueue = $this->trainingQueueService->startTraining(
                $this->village,
                $this->selectedUnitType,
                $this->trainingQuantity
            );

            $this->loadTrainingData();
            $this->addNotification(
                "Started training {$this->trainingQuantity} {$this->selectedUnitType->name} (Ref: {$trainingQueue->reference_number})",
                'success'
            );

            $this->dispatch('trainingStarted', [
                'unit_name' => $this->selectedUnitType->name,
                'quantity' => $this->trainingQuantity,
                'reference_number' => $trainingQueue->reference_number,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Training failed: ' . $e->getMessage(), 'error');
        }
    }

    public function cancelTraining($queueId)
    {
        try {
            $queue = TrainingQueue::findOrFail($queueId);
            $this->trainingQueueService->cancelTraining($queue);

            $this->loadTrainingData();
            $this->addNotification(
                "Cancelled training queue (Ref: {$queue->reference_number})",
                'success'
            );

            $this->dispatch('trainingCancelled', [
                'queue_id' => $queueId,
                'reference_number' => $queue->reference_number,
            ]);
        } catch (\Exception $e) {
            $this->addNotification('Failed to cancel training: ' . $e->getMessage(), 'error');
        }
    }

    public function refreshQueues()
    {
        $this->loadTrainingData();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    private function loadTrainingData()
    {
        $this->calculateTrainingCost();
    }

    private function calculateTrainingCost()
    {
        if (!$this->selectedUnitType) {
            $this->trainingCost = [];
            $this->canTrain = false;
            $this->trainingTime = 0;
            return;
        }

        $costs = $this->selectedUnitType->costs ?? [];
        $this->trainingCost = [];

        foreach ($costs as $resource => $cost) {
            $this->trainingCost[$resource] = $cost * $this->trainingQuantity;
        }

        $this->trainingTime = $this->calculateTrainingTime();
        $this->canTrain = $this->checkCanTrain();
    }

    private function calculateTrainingTime(): int
    {
        if (!$this->selectedUnitType) {
            return 0;
        }

        // Base training time (in seconds)
        $baseTime = 60; // 1 minute per unit
        
        // Building bonuses
        $barracks = $this->village->buildings()
            ->whereHas('buildingType', function ($query) {
                $query->where('key', 'barracks');
            })
            ->first();

        $stable = $this->village->buildings()
            ->whereHas('buildingType', function ($query) {
                $query->where('key', 'stable');
            })
            ->first();

        $workshop = $this->village->buildings()
            ->whereHas('buildingType', function ($query) {
                $query->where('key', 'workshop');
            })
            ->first();

        // Determine which building affects this unit type
        $buildingLevel = 0;
        if (in_array($this->selectedUnitType->key, ['legionnaire', 'praetorian', 'imperian', 'clubswinger', 'spearman', 'axeman', 'phalanx', 'swordsman'])) {
            $buildingLevel = $barracks ? $barracks->level : 0;
        } elseif (in_array($this->selectedUnitType->key, ['equites_legati', 'equites_imperatoris', 'equites_caesaris', 'paladin', 'teutonic_knight', 'theutates_thunder', 'druidrider', 'haeduan'])) {
            $buildingLevel = $stable ? $stable->level : 0;
        } elseif (in_array($this->selectedUnitType->key, ['ram', 'catapult'])) {
            $buildingLevel = $workshop ? $workshop->level : 0;
        }

        // Calculate time reduction (5% per building level)
        $timeReduction = 1 - ($buildingLevel * 0.05);
        $timeReduction = max($timeReduction, 0.1); // Minimum 10% of original time

        $totalTime = $baseTime * $this->trainingQuantity * $timeReduction;

        return (int) $totalTime;
    }

    private function checkCanTrain(): bool
    {
        if (!$this->selectedUnitType || $this->trainingQuantity <= 0) {
            return false;
        }

        // Check if village has enough resources
        foreach ($this->trainingCost as $resource => $cost) {
            $resourceModel = $this->village->resources()->where('type', $resource)->first();
            if (!$resourceModel || $resourceModel->amount < $cost) {
                return false;
            }
        }

        // Check building requirements
        $requirements = $this->selectedUnitType->requirements ?? [];
        foreach ($requirements as $building => $level) {
            $buildingModel = $this->village->buildings()
                ->whereHas('buildingType', function ($query) use ($building) {
                    $query->where('key', $building);
                })
                ->first();

            if (!$buildingModel || $buildingModel->level < $level) {
                return false;
            }
        }

        return true;
    }

    private function getUnitTypes()
    {
        $query = UnitType::where('is_active', true)
            ->where('tribe', $this->village->player->tribe);

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return $query->orderBy('name')->get();
    }

    private function getTrainingStats()
    {
        return $this->trainingQueueService->getTrainingStats($this->village);
    }

    private function getQueues()
    {
        $query = $this->village->trainingQueues()->with('unitType');

        if ($this->activeTab === 'active') {
            $query->where('status', 'in_progress');
        } elseif ($this->activeTab === 'completed') {
            $query->where('status', 'completed');
        } elseif ($this->activeTab === 'cancelled') {
            $query->where('status', 'cancelled');
        }

        if ($this->filterUnitType) {
            $query->where('unit_type_id', $this->filterUnitType);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    private function addNotification($message, $type = 'info')
    {
        $this->notifications[] = [
            'message' => $message,
            'type' => $type,
            'timestamp' => now(),
        ];
    }

    public function removeNotification($index)
    {
        unset($this->notifications[$index]);
        $this->notifications = array_values($this->notifications);
    }
}
