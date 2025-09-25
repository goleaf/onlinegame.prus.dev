<?php

namespace App\Livewire\Game;

use App\Models\Game\Player;
use App\Models\Game\Village;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class ResourceManager extends Component
{
    public $village;

    public $resources = [
        'wood' => 0,
        'clay' => 0,
        'iron' => 0,
        'crop' => 0
    ];

    public $productionRates = [
        'wood' => 0,
        'clay' => 0,
        'iron' => 0,
        'crop' => 0
    ];

    public $capacities = [
        'wood' => 0,
        'clay' => 0,
        'iron' => 0,
        'crop' => 0
    ];

    public $lastUpdate;
    public $autoUpdate = true;
    public $updateInterval = 5;  // seconds

    public function mount($villageId = null)
    {
        if ($villageId) {
            $this->village = Village::findOrFail($villageId);
        } else {
            $player = Player::where('user_id', Auth::id())->first();
            $this->village = $player?->villages()->first();
        }

        $this->loadResources();
        $this->lastUpdate = now();
        $this->startResourcePolling();
    }

    public function loadResources()
    {
        if (!$this->village)
            return;

        // Load resources from the relationship
        $villageResources = $this->village->resources;

        $this->resources = [
            'wood' => $villageResources->where('type', 'wood')->first()->amount ?? 0,
            'clay' => $villageResources->where('type', 'clay')->first()->amount ?? 0,
            'iron' => $villageResources->where('type', 'iron')->first()->amount ?? 0,
            'crop' => $villageResources->where('type', 'crop')->first()->amount ?? 0
        ];

        // For now, set default capacities (these would come from buildings in a real game)
        $this->capacities = [
            'wood' => 800,
            'clay' => 800,
            'iron' => 800,
            'crop' => 800
        ];

        // Calculate production rates based on building levels
        $this->calculateProductionRates();
    }

    public function calculateProductionRates()
    {
        if (!$this->village)
            return;

        // Base production rates (would be calculated from building levels)
        $this->productionRates = [
            'wood' => 10,
            'clay' => 10,
            'iron' => 10,
            'crop' => 10
        ];
    }

    #[On('resources-updated')]
    public function updateResources()
    {
        $this->loadResources();
        $this->lastUpdate = now();
        $this->dispatch('resources-updated');
    }

    public function startResourcePolling()
    {
        if ($this->autoUpdate) {
            $this->dispatch('start-resource-polling', ['interval' => $this->updateInterval * 1000]);
        }
    }

    public function stopResourcePolling()
    {
        $this->dispatch('stop-resource-polling');
    }

    public function toggleAutoUpdate()
    {
        $this->autoUpdate = !$this->autoUpdate;

        if ($this->autoUpdate) {
            $this->startResourcePolling();
        } else {
            $this->stopResourcePolling();
        }
    }

    public function setUpdateInterval($seconds)
    {
        $this->updateInterval = max(1, min(60, $seconds));

        if ($this->autoUpdate) {
            $this->stopResourcePolling();
            $this->startResourcePolling();
        }
    }

    #[On('tick')]
    public function processTick()
    {
        if (!$this->village || !$this->autoUpdate)
            return;

        $this->updateResourceProduction();
        $this->loadResources();
        $this->lastUpdate = now();

        // Dispatch event to update other components
        $this->dispatch('resources-updated');
    }

    public function updateResourceProduction()
    {
        if (!$this->village)
            return;

        $timeSinceLastUpdate = now()->diffInSeconds($this->lastUpdate);

        foreach ($this->productionRates as $resource => $rate) {
            $production = $rate * $timeSinceLastUpdate;
            $resourceModel = $this->village->resources()->where('type', $resource)->first();

            if ($resourceModel) {
                $currentAmount = $resourceModel->amount;
                $capacity = $this->capacities[$resource];
                $newAmount = min($currentAmount + $production, $capacity);
                $resourceModel->update(['amount' => $newAmount]);
            }
        }
    }

    public function spendResources($costs)
    {
        if (!$this->village)
            return false;

        foreach ($costs as $resource => $amount) {
            if ($this->resources[$resource] < $amount) {
                $this->dispatch('insufficient-resources', [
                    'resource' => $resource,
                    'required' => $amount,
                    'available' => $this->resources[$resource]
                ]);
                return false;
            }
        }

        // Deduct resources
        foreach ($costs as $resource => $amount) {
            $resourceModel = $this->village->resources()->where('type', $resource)->first();
            if ($resourceModel) {
                $resourceModel->decrement('amount', $amount);
            }
        }

        $this->loadResources();
        $this->dispatch('resources-updated');
        return true;
    }

    public function addResources($amounts)
    {
        if (!$this->village)
            return;

        foreach ($amounts as $resource => $amount) {
            $resourceModel = $this->village->resources()->where('type', $resource)->first();
            if ($resourceModel) {
                $currentAmount = $resourceModel->amount;
                $capacity = $this->capacities[$resource];
                $newAmount = min($currentAmount + $amount, $capacity);
                $resourceModel->update(['amount' => $newAmount]);
            }
        }

        $this->loadResources();
        $this->dispatch('resources-updated');
    }

    public function render()
    {
        return view('livewire.game.resource-manager');
    }
}
