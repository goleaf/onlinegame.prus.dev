<?php

namespace App\Livewire\Game;

use App\Models\Game\Village;
use App\Services\DefenseCalculationService;
use Livewire\Component;

class DefenseCalculator extends Component
{
    public $villageId;
    public $village;
    public $defenseReport;
    public $recommendations;
    public $selectedBuilding = '';
    public $buildingLevel = 1;
    public $simulationResults = [];

    protected $defenseService;

    public function mount($villageId = null)
    {
        $this->defenseService = new DefenseCalculationService();
        $this->villageId = $villageId;
        
        if ($this->villageId) {
            $this->loadVillage();
        }
    }

    public function loadVillage()
    {
        $this->village = Village::with(['buildings.buildingType', 'resources'])
            ->find($this->villageId);
            
        if ($this->village) {
            $this->calculateDefense();
        }
    }

    public function calculateDefense()
    {
        if (!$this->village) {
            return;
        }

        $this->defenseReport = $this->defenseService->getDefenseReport($this->village);
        $this->recommendations = $this->defenseService->getDefenseRecommendations($this->village);
    }

    public function simulateBuildingUpgrade()
    {
        if (!$this->village || !$this->selectedBuilding) {
            return;
        }

        $this->simulationResults = [];
        
        // Simulate upgrades from current level to level 20
        $currentBuilding = $this->village->buildings()
            ->whereHas('buildingType', function ($query) {
                $query->where('key', $this->selectedBuilding);
            })
            ->first();

        $currentLevel = $currentBuilding ? $currentBuilding->level : 0;
        
        for ($level = $currentLevel + 1; $level <= min($currentLevel + 10, 20); $level++) {
            $bonus = $this->defenseService->getBuildingDefenseBonus($this->selectedBuilding, $level);
            
            $this->simulationResults[] = [
                'level' => $level,
                'bonus' => $bonus,
                'percentage' => $bonus * 100,
                'total_defense' => $this->calculateTotalDefenseWithUpgrade($level),
            ];
        }
    }

    private function calculateTotalDefenseWithUpgrade($newLevel)
    {
        $totalBonus = 0;
        $buildings = $this->village->buildings()->with('buildingType')->get();
        
        foreach ($buildings as $building) {
            $buildingType = $building->buildingType;
            $level = $building->level;
            
            // Use new level for the selected building
            if ($buildingType->key === $this->selectedBuilding) {
                $level = $newLevel;
            }
            
            $bonus = $this->defenseService->getBuildingDefenseBonus($buildingType->key, $level);
            $totalBonus += $bonus;
        }
        
        return min($totalBonus, 0.5) * 100; // Return as percentage
    }

    public function getBuildingTypesProperty()
    {
        return [
            'wall' => 'Wall',
            'watchtower' => 'Watchtower',
            'trap' => 'Trap',
            'rally_point' => 'Rally Point',
        ];
    }

    public function render()
    {
        return view('livewire.game.defense-calculator');
    }
}
