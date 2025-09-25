<?php

namespace App\Livewire\Game;

use App\Models\Game\Player;
use App\Models\Game\TrainingQueue;
use App\Models\Game\Troop;
use App\Models\Game\UnitType;
use App\Models\Game\Village;
use Illuminate\Support\Facades\Auth;
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

    protected $listeners = [
        'refreshTroops',
        'trainingCompleted',
        'villageSelected'
    ];

    public function mount($villageId = null)
    {
        if ($villageId) {
            $this->village = Village::with(['troops.unitType', 'player'])
                ->findOrFail($villageId);
        } else {
            $player = Player::where('user_id', Auth::id())->first();
            $this->village = $player?->villages()->with(['troops.unitType', 'player'])->first();
        }

        if ($this->village) {
            $this->loadTroopData();
        }
    }

    public function loadTroopData()
    {
        $this->isLoading = true;

        try {
            $this->troops = $this->village->troops;
            $this->unitTypes = UnitType::where('is_active', true)
                ->where('tribe', $this->village->player->tribe)
                ->get();
            $this->trainingQueues = $this
                ->village
                ->trainingQueues()
                ->where('is_completed', false)
                ->with('unitType')
                ->get();
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectUnitType($unitTypeId)
    {
        $this->selectedUnitType = UnitType::find($unitTypeId);
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
                'status' => 'in_progress'
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
                'quantity' => $this->trainingQuantity
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

    #[On('villageSelected')]
    public function handleVillageSelected($data)
    {
        if ($data['villageId'] == $this->village->id) {
            $this->loadTroopData();
        }
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

    public function refreshTroops()
    {
        $this->loadTroopData();
        $this->addNotification('Troop data refreshed', 'info');
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
            'isLoading' => $this->isLoading
        ]);
    }
}
