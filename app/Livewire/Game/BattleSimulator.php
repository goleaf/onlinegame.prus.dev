<?php

namespace App\Livewire\Game;

use App\Models\Game\Village;
use App\Models\Game\UnitType;
use App\Services\BattleSimulationService;
use App\Services\DefenseCalculationService;
use Livewire\Component;

class BattleSimulator extends Component
{
    public $attackerVillageId;
    public $defenderVillageId;
    public $attackerVillage;
    public $defenderVillage;
    public $attackingTroops = [];
    public $defendingTroops = [];
    public $simulationResults;
    public $iterations = 1000;
    public $optimizationResults;
    public $showOptimization = false;
    public $totalTroops = 100;
    public $availableTroops = [];

    protected $battleService;
    protected $defenseService;

    public function mount($attackerVillageId = null, $defenderVillageId = null)
    {
        $this->battleService = new BattleSimulationService();
        $this->defenseService = new DefenseCalculationService();
        $this->attackerVillageId = $attackerVillageId;
        $this->defenderVillageId = $defenderVillageId;
        
        if ($this->attackerVillageId) {
            $this->loadAttackerVillage();
        }
        if ($this->defenderVillageId) {
            $this->loadDefenderVillage();
        }
        
        $this->loadAvailableTroops();
    }

    public function loadAttackerVillage()
    {
        $this->attackerVillage = Village::with(['troops.unitType', 'resources'])
            ->find($this->attackerVillageId);
            
        if ($this->attackerVillage) {
            $this->loadAttackingTroops();
        }
    }

    public function loadDefenderVillage()
    {
        $this->defenderVillage = Village::with(['troops.unitType', 'buildings.buildingType', 'resources'])
            ->find($this->defenderVillageId);
            
        if ($this->defenderVillage) {
            $this->loadDefendingTroops();
        }
    }

    public function loadAttackingTroops()
    {
        if (!$this->attackerVillage) {
            return;
        }

        $this->attackingTroops = [];
        foreach ($this->attackerVillage->troops as $troop) {
            if ($troop->in_village > 0) {
                $this->attackingTroops[] = [
                    'troop_id' => $troop->id,
                    'unit_type' => $troop->unitType->name,
                    'count' => $troop->in_village,
                    'attack' => $troop->unitType->attack_power,
                    'defense_infantry' => $troop->unitType->defense_power,
                    'defense_cavalry' => $troop->unitType->defense_power,
                    'speed' => $troop->unitType->speed,
                ];
            }
        }
    }

    public function loadDefendingTroops()
    {
        if (!$this->defenderVillage) {
            return;
        }

        $this->defendingTroops = [];
        foreach ($this->defenderVillage->troops as $troop) {
            if ($troop->in_village > 0) {
                $this->defendingTroops[] = [
                    'troop_id' => $troop->id,
                    'unit_type' => $troop->unitType->name,
                    'count' => $troop->in_village,
                    'attack' => $troop->unitType->attack_power,
                    'defense_infantry' => $troop->unitType->defense_power,
                    'defense_cavalry' => $troop->unitType->defense_power,
                    'speed' => $troop->unitType->speed,
                ];
            }
        }
    }

    public function loadAvailableTroops()
    {
        $unitTypes = UnitType::all();
        $this->availableTroops = [];
        
        foreach ($unitTypes as $unitType) {
            $this->availableTroops[$unitType->name] = [
                'id' => $unitType->id,
                'name' => $unitType->name,
                'attack' => $unitType->attack_power,
                'defense_infantry' => $unitType->defense_power,
                'defense_cavalry' => $unitType->defense_power,
                'speed' => $unitType->speed,
            ];
        }
    }

    public function runSimulation()
    {
        if (empty($this->attackingTroops) || empty($this->defendingTroops) || !$this->defenderVillage) {
            session()->flash('error', 'Please select both attacker and defender villages with troops.');
            return;
        }

        $this->simulationResults = $this->battleService->simulateBattle(
            $this->attackingTroops,
            $this->defendingTroops,
            $this->defenderVillage,
            $this->iterations
        );
    }

    public function optimizeTroopComposition()
    {
        if (!$this->defenderVillage || empty($this->availableTroops)) {
            session()->flash('error', 'Please select a defender village and ensure troops are available.');
            return;
        }

        $this->optimizationResults = $this->battleService->optimizeTroopComposition(
            $this->defenderVillage,
            $this->availableTroops,
            $this->totalTroops
        );
        
        $this->showOptimization = true;
    }

    public function updateTroopCount($index, $count)
    {
        if (isset($this->attackingTroops[$index])) {
            $this->attackingTroops[$index]['count'] = max(0, (int)$count);
        }
    }

    public function getDefenseReportProperty()
    {
        if (!$this->defenderVillage) {
            return null;
        }
        
        return $this->defenseService->getDefenseReport($this->defenderVillage);
    }

    public function getBattleHistoryProperty()
    {
        if (!$this->defenderVillage) {
            return null;
        }
        
        return $this->battleService->analyzeBattleHistory($this->defenderVillage);
    }

    public function getRecommendationsProperty()
    {
        if (!$this->defenderVillage) {
            return [];
        }
        
        return $this->battleService->getBattleRecommendations($this->defenderVillage);
    }

    public function render()
    {
        return view('livewire.game.battle-simulator');
    }
}

