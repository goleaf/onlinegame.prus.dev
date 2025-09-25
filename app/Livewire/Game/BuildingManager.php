<?php

namespace App\Livewire\Game;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use App\Models\Game\Village;
use App\Models\Game\Building;
use App\Models\Game\Player;
use Illuminate\Support\Facades\Auth;

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

    public function mount($villageId = null)
    {
        if ($villageId) {
            $this->village = Village::findOrFail($villageId);
        } else {
            $player = Player::where('user_id', Auth::id())->first();
            $this->village = $player?->villages()->first();
        }
        
        $this->loadBuildings();
        $this->loadAvailableBuildings();
    }

    public function loadBuildings()
    {
        if (!$this->village) return;
        
        $this->buildings = $this->village->buildings()
            ->where('is_active', true)
            ->get()
            ->keyBy('type')
            ->toArray();
    }

    public function loadAvailableBuildings()
    {
        $this->availableBuildings = [
            'wood' => [
                'name' => 'Woodcutter',
                'description' => 'Produces wood',
                'base_cost' => ['wood' => 50, 'clay' => 30, 'iron' => 20, 'crop' => 10],
                'production_bonus' => 10
            ],
            'clay' => [
                'name' => 'Clay Pit',
                'description' => 'Produces clay',
                'base_cost' => ['wood' => 30, 'clay' => 50, 'iron' => 20, 'crop' => 10],
                'production_bonus' => 10
            ],
            'iron' => [
                'name' => 'Iron Mine',
                'description' => 'Produces iron',
                'base_cost' => ['wood' => 20, 'clay' => 30, 'iron' => 50, 'crop' => 10],
                'production_bonus' => 10
            ],
            'crop' => [
                'name' => 'Cropland',
                'description' => 'Produces crop',
                'base_cost' => ['wood' => 10, 'clay' => 20, 'iron' => 10, 'crop' => 50],
                'production_bonus' => 10
            ],
            'warehouse' => [
                'name' => 'Warehouse',
                'description' => 'Increases storage capacity',
                'base_cost' => ['wood' => 100, 'clay' => 80, 'iron' => 60, 'crop' => 40],
                'storage_bonus' => 1000
            ],
            'granary' => [
                'name' => 'Granary',
                'description' => 'Increases crop storage',
                'base_cost' => ['wood' => 80, 'clay' => 100, 'iron' => 40, 'crop' => 60],
                'storage_bonus' => 1000
            ]
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
        if (!$this->selectedBuilding || !$this->village) return;
        
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
        if (!$this->selectedBuilding || !$this->village) return;
        
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
                'message' => 'Not enough resources to upgrade this building!'
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
                'type' => $this->selectedBuilding
            ],
            [
                'level' => ($this->buildings[$this->selectedBuilding]['level'] ?? 0) + 1,
                'name' => $this->availableBuildings[$this->selectedBuilding]['name'],
                'is_active' => true
            ]
        );
        
        // Update village stats
        $this->updateVillageStats($building);
        
        $this->loadBuildings();
        $this->showUpgradeModal = false;
        
        $this->dispatch('building-upgraded', [
            'building' => $building->type,
            'level' => $building->level
        ]);
        
        $this->dispatch('resources-updated');
    }

    public function updateVillageStats($building)
    {
        if (!$this->village) return;
        
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

    public function render()
    {
        return view('livewire.game.building-manager');
    }
}