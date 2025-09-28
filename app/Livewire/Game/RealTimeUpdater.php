<?php

namespace App\Livewire\Game;

use App\Models\Game\BuildingQueue;
use App\Models\Game\GameEvent;
use App\Models\Game\Player;
use App\Models\Game\TrainingQueue;
use App\Services\GameTickService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class RealTimeUpdater extends Component
{
    public $player;

    public $isActive = true;

    public $tickInterval = 5;  // seconds

    public $lastTick = null;

    public $tickCount = 0;

    public $updates = [];

    public $errors = [];

    protected $listeners = [
        'startTicking',
        'stopTicking',
        'processTick',
        'updateResources',
        'updateBuildings',
        'updateTroops',
    ];

    public function mount()
    {
        $this->player = Player::where('user_id', Auth::id())->first();
        $this->lastTick = now();
    }

    #[On('startTicking')]
    public function startTicking()
    {
        $this->isActive = true;
        $this->addUpdate('Real-time updates started');
    }

    #[On('stopTicking')]
    public function stopTicking()
    {
        $this->isActive = false;
        $this->addUpdate('Real-time updates stopped');
    }

    #[On('tick')]
    public function processTick()
    {
        if (! $this->isActive || ! $this->player) {
            return;
        }

        try {
            $this->tickCount++;
            $this->lastTick = now();

            // Process game tick
            $gameTickService = new GameTickService();
            $gameTickService->processGameTick();

            // Update resources
            $this->updateResources();

            // Update building queues
            $this->updateBuildingQueues();

            // Update training queues
            $this->updateTrainingQueues();

            // Check for completed events
            $this->checkCompletedEvents();

            $this->addUpdate("Tick #{$this->tickCount} processed successfully");

            // Dispatch events to other components
            $this->dispatch('gameTickProcessed', [
                'tickCount' => $this->tickCount,
                'timestamp' => $this->lastTick,
            ]);
        } catch (\Exception $e) {
            $this->addError("Tick #{$this->tickCount} failed: ".$e->getMessage());
            $this->dispatch('gameTickError', ['message' => $e->getMessage()]);
        }
    }

    public function updateResources()
    {
        if (! $this->player) {
            return;
        }

        $villages = $this->player->villages()->with('resources')->get();

        foreach ($villages as $village) {
            $resources = $village->resources;

            foreach ($resources as $resource) {
                $timeSinceLastUpdate = now()->diffInSeconds($resource->last_updated);
                $production = $resource->production_rate * $timeSinceLastUpdate;

                $newAmount = min(
                    $resource->amount + $production,
                    $resource->storage_capacity
                );

                if ($newAmount !== $resource->amount) {
                    $resource->update([
                        'amount' => $newAmount,
                        'last_updated' => now(),
                    ]);
                }
            }
        }

        $this->dispatch('resourcesUpdated');
    }

    public function updateBuildingQueues()
    {
        if (! $this->player) {
            return;
        }

        $completedBuildings = BuildingQueue::where('player_id', $this->player->id)
            ->where('completed_at', '<=', now())
            ->where('is_completed', false)
            ->get();

        foreach ($completedBuildings as $building) {
            $this->completeBuilding($building);
        }
    }

    public function updateTrainingQueues()
    {
        if (! $this->player) {
            return;
        }

        $completedTraining = TrainingQueue::where('player_id', $this->player->id)
            ->where('completed_at', '<=', now())
            ->where('is_completed', false)
            ->get();

        foreach ($completedTraining as $training) {
            $this->completeTraining($training);
        }
    }

    private function completeBuilding($building)
    {
        try {
            $building->update(['is_completed' => true]);

            // Update village building
            $villageBuilding = $building
                ->village
                ->buildings()
                ->where('building_type_id', $building->building_type_id)
                ->first();

            if ($villageBuilding) {
                $villageBuilding->update(['level' => $building->target_level]);
            }

            $this->addUpdate("Building completed: {$building->buildingType->name} Level {$building->target_level}");

            $this->dispatch('buildingCompleted', [
                'building_name' => $building->buildingType->name,
                'level' => $building->target_level,
                'village_id' => $building->village_id,
            ]);
        } catch (\Exception $e) {
            $this->addError('Failed to complete building: '.$e->getMessage());
        }
    }

    private function completeTraining($training)
    {
        try {
            $training->update(['is_completed' => true]);

            // Add troops to village
            $village = $training->village;
            $troop = $village
                ->troops()
                ->where('unit_type_id', $training->unit_type_id)
                ->first();

            if ($troop) {
                $troop->increment('quantity', $training->quantity);
            } else {
                $village->troops()->create([
                    'unit_type_id' => $training->unit_type_id,
                    'quantity' => $training->quantity,
                ]);
            }

            $this->addUpdate("Training completed: {$training->unitType->name} x{$training->quantity}");

            $this->dispatch('trainingCompleted', [
                'unit_name' => $training->unitType->name,
                'quantity' => $training->quantity,
                'village_id' => $training->village_id,
            ]);
        } catch (\Exception $e) {
            $this->addError('Failed to complete training: '.$e->getMessage());
        }
    }

    private function checkCompletedEvents()
    {
        if (! $this->player) {
            return;
        }

        $events = GameEvent::where('player_id', $this->player->id)
            ->where('is_completed', false)
            ->where('triggered_at', '<=', now())
            ->get();

        foreach ($events as $event) {
            $this->completeEvent($event);
        }
    }

    private function completeEvent($event)
    {
        try {
            $event->update(['is_completed' => true]);

            $this->addUpdate("Event completed: {$event->title}");

            $this->dispatch('eventCompleted', [
                'event_id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
            ]);
        } catch (\Exception $e) {
            $this->addError('Failed to complete event: '.$e->getMessage());
        }
    }

    public function addUpdate($message)
    {
        $this->updates[] = [
            'id' => uniqid(),
            'message' => $message,
            'timestamp' => now(),
            'type' => 'info',
        ];

        // Keep only last 20 updates
        $this->updates = array_slice($this->updates, -20);
    }

    public function addError($name, $message = null)
    {
        // Handle both old and new method signatures
        if ($message === null) {
            $message = $name;
            $name = 'error';
        }

        $this->errors[] = [
            'id' => uniqid(),
            'message' => $message,
            'timestamp' => now(),
            'type' => 'error',
        ];

        // Keep only last 10 errors
        $this->errors = array_slice($this->errors, -10);
    }

    public function clearUpdates()
    {
        $this->updates = [];
    }

    public function clearErrors()
    {
        $this->errors = [];
    }

    public function setTickInterval($seconds)
    {
        $this->tickInterval = max(1, min(60, $seconds));
        $this->addUpdate("Tick interval set to {$this->tickInterval} seconds");
    }

    public function getTickStatus()
    {
        return [
            'isActive' => $this->isActive,
            'tickCount' => $this->tickCount,
            'lastTick' => $this->lastTick,
            'tickInterval' => $this->tickInterval,
            'updatesCount' => count($this->updates),
            'errorsCount' => count($this->errors),
        ];
    }

    public function render()
    {
        return view('livewire.game.real-time-updater', [
            'player' => $this->player,
            'isActive' => $this->isActive,
            'tickCount' => $this->tickCount,
            'lastTick' => $this->lastTick,
            'updates' => $this->updates,
            'errors' => $this->errors,
        ]);
    }
}
