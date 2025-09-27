<?php

namespace App\Livewire\Game;

use App\Models\Game\Player;
use App\Models\Game\Village;
use App\Models\Game\World;
use Livewire\Component;

class Dashboard extends Component
{
    public $player;
    public $villages;
    public $world;

    public $resources = [
        'wood' => 1000,
        'clay' => 1000,
        'iron' => 1000,
        'crop' => 1000,
    ];

    public function mount()
    {
        $this->player = auth()->user();
        $this->villages = $this->player->villages ?? collect();
        $this->world = World::first();

        // Load player resources
        $this->loadResources();
    }

    public function createVillage()
    {
        if ($this->villages->count() >= 1 && !$this->player->hasRole('premium')) {
            session()->flash('error', 'You need premium to create more villages!');

            // Track failed village creation
            $this->dispatch('fathom-track', name: 'village creation failed - premium required');

            return;
        }

        $village = Village::create([
            'player_id' => $this->player->id,
            'world_id' => $this->world->id,
            'name' => 'Village ' . ($this->villages->count() + 1),
            'x_coordinate' => rand(1, 100),
            'y_coordinate' => rand(1, 100),
            'population' => 2,
            'is_capital' => $this->villages->count() === 0,
            'is_active' => true,
        ]);

        // Create initial resources
        $this->createInitialResources($village);

        $this->villages = $this->player->fresh()->villages;
        session()->flash('success', 'Village created successfully!');

        // Track successful village creation
        $this->dispatch('fathom-track', name: 'village created', value: $this->villages->count() * 100);
    }

    public function enterVillage($villageId)
    {
        // Track village entry
        $this->dispatch('fathom-track', name: 'village entered', value: $villageId);

        return redirect()->route('game.village', $villageId);
    }

    private function loadResources()
    {
        // Load resources from database or calculate from villages
        $totalWood = 0;
        $totalClay = 0;
        $totalIron = 0;
        $totalCrop = 0;

        foreach ($this->villages as $village) {
            foreach ($village->resources as $resource) {
                switch ($resource->type) {
                    case 'wood':
                        $totalWood += $resource->amount;

                        break;
                    case 'clay':
                        $totalClay += $resource->amount;

                        break;
                    case 'iron':
                        $totalIron += $resource->amount;

                        break;
                    case 'crop':
                        $totalCrop += $resource->amount;

                        break;
                }
            }
        }

        $this->resources = [
            'wood' => $totalWood,
            'clay' => $totalClay,
            'iron' => $totalIron,
            'crop' => $totalCrop,
        ];
    }

    private function createInitialResources($village)
    {
        $resourceTypes = ['wood', 'clay', 'iron', 'crop'];

        foreach ($resourceTypes as $type) {
            \App\Models\Game\Resource::create([
                'village_id' => $village->id,
                'type' => $type,
                'amount' => 1000,
                'production_rate' => 10,
                'storage_capacity' => 10000,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.game.dashboard');
    }
}
