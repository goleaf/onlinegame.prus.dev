<?php

namespace App\Livewire\Game;

use App\Models\Game\Battle;
use App\Models\Game\Movement;
use App\Models\Game\Troop;
use App\Models\Game\Village;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class BattleManager extends Component
{
    use WithPagination;

    public $village;
    public $attackingTroops = [];
    public $defendingTroops = [];
    public $availableTroops = [];
    public $selectedTarget = null;
    public $showBattleModal = false;
    public $battleResult = null;
    public $recentBattles = [];

    protected $listeners = ['refreshBattles', 'battleCompleted', 'movementArrived'];

    public function mount()
    {
        $user = Auth::user();
        $player = $user->player;

        if ($player) {
            $this->village = $player->villages()->with(['troops.unitType'])->first();
            $this->loadBattleData();
        }
    }

    public function loadBattleData()
    {
        if ($this->village) {
            $this->availableTroops = $this->village->troops->where('in_village', '>', 0);
            $this->loadRecentBattles();
        }
    }

    public function loadRecentBattles()
    {
        if ($this->village) {
            $this->recentBattles = Battle::where('village_id', $this->village->id)
                ->orWhere('attacker_id', $this->village->player_id)
                ->orWhere('defender_id', $this->village->player_id)
                ->orderBy('occurred_at', 'desc')
                ->limit(10)
                ->get();
        }
    }

    public function selectTarget($villageId)
    {
        $this->selectedTarget = Village::with(['player', 'troops.unitType'])->find($villageId);
        $this->showBattleModal = true;
    }

    public function addTroopToAttack($troopId, $count)
    {
        $troop = $this->availableTroops->find($troopId);
        if ($troop && $count > 0 && $count <= $troop->in_village) {
            $this->attackingTroops[] = [
                'troop_id' => $troopId,
                'unit_type' => $troop->unitType->name,
                'count' => $count,
                'attack' => $troop->unitType->attack,
                'defense_infantry' => $troop->unitType->defense_infantry,
                'defense_cavalry' => $troop->unitType->defense_cavalry,
                'speed' => $troop->unitType->speed,
            ];
        }
    }

    public function removeTroopFromAttack($index)
    {
        unset($this->attackingTroops[$index]);
        $this->attackingTroops = array_values($this->attackingTroops);
    }

    public function launchAttack()
    {
        if (! $this->selectedTarget || empty($this->attackingTroops)) {
            return;
        }

        try {
            // Calculate travel time based on distance and troop speed
            $distance = $this->calculateDistance();
            $travelTime = $this->calculateTravelTime($distance);

            // Create movement record
            $movement = Movement::create([
                'player_id' => $this->village->player_id,
                'from_village_id' => $this->village->id,
                'to_village_id' => $this->selectedTarget->id,
                'type' => 'attack',
                'troops' => $this->attackingTroops,
                'started_at' => now(),
                'arrives_at' => now()->addSeconds($travelTime),
                'status' => 'travelling',
            ]);

            // Update troop counts
            foreach ($this->attackingTroops as $troop) {
                $villageTroop = $this->village->troops->find($troop['troop_id']);
                if ($villageTroop) {
                    $villageTroop->decrement('in_village', $troop['count']);
                    $villageTroop->increment('in_attack', $troop['count']);
                }
            }

            $this->showBattleModal = false;
            $this->attackingTroops = [];
            $this->loadBattleData();

            $this->dispatch('attackLaunched', [
                'target' => $this->selectedTarget->name,
                'arrives_at' => $movement->arrives_at,
            ]);
        } catch (\Exception $e) {
            $this->dispatch('attackError', ['message' => $e->getMessage()]);
        }
    }

    public function calculateDistance()
    {
        if (! $this->selectedTarget) {
            return 0;
        }

        $x1 = $this->village->x_coordinate;
        $y1 = $this->village->y_coordinate;
        $x2 = $this->selectedTarget->x_coordinate;
        $y2 = $this->selectedTarget->y_coordinate;

        return sqrt(pow($x2 - $x1, 2) + pow($y2 - $y1, 2));
    }

    public function calculateTravelTime($distance)
    {
        if (empty($this->attackingTroops)) {
            return 0;
        }

        // Find the slowest troop
        $slowestSpeed = min(array_column($this->attackingTroops, 'speed'));

        // Base travel time calculation
        $baseTime = 60;  // 1 minute per distance unit

        return ($distance * $baseTime) / $slowestSpeed;
    }

    public function simulateBattle($attackerTroops, $defenderTroops)
    {
        $attackerPower = 0;
        $defenderPower = 0;

        // Calculate attacker power
        foreach ($attackerTroops as $troop) {
            $attackerPower += $troop['count'] * $troop['attack'];
        }

        // Calculate defender power
        foreach ($defenderTroops as $troop) {
            $defenderPower += $troop['count'] * ($troop['defense_infantry'] + $troop['defense_cavalry']);
        }

        // Add some randomness
        $attackerPower *= (0.8 + (rand(0, 40) / 100));
        $defenderPower *= (0.8 + (rand(0, 40) / 100));

        if ($attackerPower > $defenderPower) {
            return 'attacker_wins';
        } elseif ($defenderPower > $attackerPower) {
            return 'defender_wins';
        } else {
            return 'draw';
        }
    }

    public function refreshBattles()
    {
        $this->loadBattleData();
    }

    public function render()
    {
        return view('livewire.game.battle-manager', [
            'village' => $this->village,
            'availableTroops' => $this->availableTroops,
            'attackingTroops' => $this->attackingTroops,
            'selectedTarget' => $this->selectedTarget,
            'recentBattles' => $this->recentBattles,
        ]);
    }
}
