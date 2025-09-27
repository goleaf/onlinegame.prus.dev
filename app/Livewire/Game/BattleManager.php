<?php

namespace App\Livewire\Game;

use App\Models\Game\Battle;
use App\Models\Game\Movement;
use App\Models\Game\Troop;
use App\Models\Game\Village;
use App\Services\GeographicService;
use App\Services\QueryOptimizationService;
use Illuminate\Support\Facades\Auth;
use LaraUtilX\Traits\ApiResponseTrait;
use Livewire\Component;
use Livewire\WithPagination;
use SmartCache\Facades\SmartCache;

class BattleManager extends Component
{
    use WithPagination, ApiResponseTrait;

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
            $this->village = $player
                ->villages()
                ->with(['troops.unitType:id,name,attack_power,defense_power,speed'])
                ->selectRaw('
                    villages.*,
                    (SELECT COUNT(*) FROM troops WHERE village_id = villages.id AND quantity > 0) as total_troops,
                    (SELECT SUM(quantity * unit_types.attack_power) FROM troops JOIN unit_types ON troops.unit_type_id = unit_types.id WHERE village_id = villages.id) as total_attack_power,
                    (SELECT SUM(quantity * unit_types.defense_power) FROM troops JOIN unit_types ON troops.unit_type_id = unit_types.id WHERE village_id = villages.id) as total_defense_power
                ')
                ->first();

            // Laradumps debugging
            ds('BattleManager mounted', [
                'user_id' => $user->id,
                'player_id' => $player->id,
                'village' => $this->village,
                'available_troops_count' => $this->village?->troops?->count() ?? 0
            ])->label('BattleManager Mount');

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
            $cacheKey = "player_{$this->village->player_id}_recent_battles";

            $this->recentBattles = SmartCache::remember($cacheKey, now()->addMinutes(2), function () {
                return Battle::byPlayer($this->village->player_id)
                    ->withStats()
                    ->withPlayerInfo()
                    ->recent(7)
                    ->orderBy('occurred_at', 'desc')
                    ->limit(10)
                    ->get();
            });
        }
    }

    public function selectTarget($villageId)
    {
        $cacheKey = "village_{$villageId}_battle_target_data";

        $this->selectedTarget = SmartCache::remember($cacheKey, now()->addMinutes(1), function () use ($villageId) {
            return Village::with(['player:id,name', 'troops.unitType:id,name,attack_power,defense_power,speed'])
                ->selectRaw('
                    villages.*,
                    (SELECT COUNT(*) FROM troops WHERE village_id = villages.id AND quantity > 0) as total_troops,
                    (SELECT SUM(quantity * unit_types.attack_power) FROM troops JOIN unit_types ON troops.unit_type_id = unit_types.id WHERE village_id = villages.id) as total_attack_power,
                    (SELECT SUM(quantity * unit_types.defense_power) FROM troops JOIN unit_types ON troops.unit_type_id = unit_types.id WHERE village_id = villages.id) as total_defense_power
                ')
                ->find($villageId);
        });

        $this->showBattleModal = true;
        
        // Track target selection
        $this->dispatch('fathom-track', name: 'battle target selected', value: $villageId);
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
        if (!$this->selectedTarget || empty($this->attackingTroops)) {
            ds('Attack launch failed - missing target or troops', [
                'selected_target' => $this->selectedTarget,
                'attacking_troops' => $this->attackingTroops
            ])->label('BattleManager Attack Launch Failed');
            return;
        }

        try {
            // Calculate travel time based on distance and troop speed
            $distance = $this->calculateDistance();
            $travelTime = $this->calculateTravelTime($distance);

            // Calculate real-world distance for additional context
            $realWorldDistance = $this->calculateRealWorldDistance();

            // Laradumps debugging for attack launch
            ds('Launching attack', [
                'from_village' => $this->village->name,
                'to_village' => $this->selectedTarget->name,
                'game_distance' => $distance,
                'real_world_distance_km' => $realWorldDistance,
                'travel_time' => $travelTime,
                'attacking_troops' => $this->attackingTroops,
                'total_attack_power' => array_sum(array_column($this->attackingTroops, 'attack')),
                'from_coordinates' => "({$this->village->x_coordinate}|{$this->village->y_coordinate})",
                'to_coordinates' => "({$this->selectedTarget->x_coordinate}|{$this->selectedTarget->y_coordinate})"
            ])->label('BattleManager Attack Launch');

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

            // Generate reference number for the movement
            $movement->generateReferenceNumber();

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

            ds('Attack launched successfully', [
                'movement_id' => $movement->id,
                'reference_number' => $movement->reference_number,
                'arrives_at' => $movement->arrives_at
            ])->label('BattleManager Attack Success');

            // Track attack launch
            $totalAttackPower = array_sum(array_column($this->attackingTroops, 'attack'));
            $this->dispatch('fathom-track', name: 'attack launched', value: $totalAttackPower);

            $this->dispatch('attackLaunched', [
                'target' => $this->selectedTarget->name,
                'reference_number' => $movement->reference_number,
                'arrives_at' => $movement->arrives_at,
            ]);
        } catch (\Exception $e) {
            ds('Attack launch error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ])->label('BattleManager Attack Error');
            $this->dispatch('attackError', ['message' => $e->getMessage()]);
        }
    }

    public function calculateDistance()
    {
        if (!$this->selectedTarget) {
            return 0;
        }

        $geoService = app(GeographicService::class);
        return $geoService->calculateGameDistance(
            $this->village->x_coordinate,
            $this->village->y_coordinate,
            $this->selectedTarget->x_coordinate,
            $this->selectedTarget->y_coordinate
        );
    }

    /**
     * Calculate real-world distance between villages
     *
     * @return float
     */
    public function calculateRealWorldDistance()
    {
        if (!$this->selectedTarget) {
            return 0;
        }

        return $this->village->realWorldDistanceTo($this->selectedTarget);
    }

    public function calculateTravelTime($distance)
    {
        if (empty($this->attackingTroops)) {
            return 0;
        }

        // Find the slowest troop
        $slowestSpeed = min(array_column($this->attackingTroops, 'speed'));

        // Use geographic service for more accurate travel time calculation
        $geoService = app(GeographicService::class);

        // Convert game distance to approximate real-world distance (km)
        $realWorldDistanceKm = $distance * 0.1;  // Rough conversion: 1 game unit = 0.1 km

        // Convert speed from game units to km/h (rough conversion)
        $speedKmh = $slowestSpeed * 10;  // Rough conversion: 1 game speed = 10 km/h

        return $geoService->calculateTravelTime($realWorldDistanceKm, $speedKmh);
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
        $randomFactor = (0.8 + (rand(0, 40) / 100));
        $attackerPower *= $randomFactor;
        $defenderPower *= $randomFactor;

        $result = $attackerPower > $defenderPower
            ? 'attacker_wins'
            : ($defenderPower > $attackerPower ? 'defender_wins' : 'draw');

        // Laradumps debugging for battle simulation
        ds('Battle simulation completed', [
            'attacker_troops' => $attackerTroops,
            'defender_troops' => $defenderTroops,
            'attacker_power' => $attackerPower,
            'defender_power' => $defenderPower,
            'random_factor' => $randomFactor,
            'result' => $result
        ])->label('BattleManager Battle Simulation');

        return $result;
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
