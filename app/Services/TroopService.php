<?php

namespace App\Services;

use App\Models\Game\TrainingQueue;
use App\Models\Game\Village;
use Illuminate\Support\Facades\Log;

class TroopService
{
    public function canTrain($village, $unitType, $quantity = 1)
    {
        // Check if player has enough resources
        $costs = $this->calculateTrainingCost($unitType, $quantity);
        $resourceService = new ResourceProductionService();

        if (!$resourceService->canAfford($village, $costs)) {
            return false;
        }

        // Check if required buildings exist
        $requirements = $unitType->requirements ?? [];

        foreach ($requirements as $buildingKey => $requiredLevel) {
            $building = $village
                ->buildings()
                ->whereHas('buildingType', function ($query) use ($buildingKey) {
                    $query->where('key', $buildingKey);
                })
                ->first();

            if (!$building || $building->level < $requiredLevel) {
                return false;
            }
        }

        return true;
    }

    public function calculateTrainingCost($unitType, $quantity)
    {
        $baseCosts = $unitType->costs ?? [];
        $costs = [];

        foreach ($baseCosts as $resource => $baseCost) {
            $costs[$resource] = $baseCost * $quantity;
        }

        return $costs;
    }

    public function calculateTrainingTime($unitType, $quantity)
    {
        $baseTime = 60;  // Base time in seconds
        $quantityMultiplier = $quantity;

        return $baseTime * $quantityMultiplier;
    }

    public function startTraining($village, $unitType, $quantity = 1)
    {
        if (!$this->canTrain($village, $unitType, $quantity)) {
            return false;
        }

        try {
            $costs = $this->calculateTrainingCost($unitType, $quantity);
            $trainingTime = $this->calculateTrainingTime($unitType, $quantity);

            // Deduct resources
            $resourceService = new ResourceProductionService();
            $resourceService->spendResources($village, $costs);

            // Create training queue
            TrainingQueue::create([
                'village_id' => $village->id,
                'player_id' => $village->player_id,
                'unit_type_id' => $unitType->id,
                'quantity' => $quantity,
                'started_at' => now(),
                'completed_at' => now()->addSeconds($trainingTime),
                'costs' => $costs,
                'status' => 'in_progress',
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to start troop training: ' . $e->getMessage());

            return false;
        }
    }

    public function completeTraining($training)
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

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to complete troop training: ' . $e->getMessage());

            return false;
        }
    }

    public function cancelTraining($training)
    {
        try {
            // Refund resources (50% refund)
            $costs = $training->costs ?? [];
            $refundCosts = [];

            foreach ($costs as $resource => $cost) {
                $refundCosts[$resource] = $cost * 0.5;
            }

            $resourceService = new ResourceProductionService();
            $resourceService->addResources($training->village, $refundCosts);

            $training->delete();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cancel troop training: ' . $e->getMessage());

            return false;
        }
    }

    public function getTroopInfo($village, $unitType)
    {
        $troop = $village
            ->troops()
            ->where('unit_type_id', $unitType->id)
            ->first();

        $info = [
            'quantity' => $troop ? $troop->quantity : 0,
            'can_train' => $this->canTrain($village, $unitType, 1),
            'training_cost' => $this->calculateTrainingCost($unitType, 1),
            'training_time' => $this->calculateTrainingTime($unitType, 1),
            'attack_power' => $unitType->attack,
            'defense_infantry' => $unitType->defense_infantry,
            'defense_cavalry' => $unitType->defense_cavalry,
            'speed' => $unitType->speed,
            'carry_capacity' => $unitType->carry_capacity,
        ];

        return $info;
    }

    public function getVillageTroopStats($village)
    {
        $troops = $village->troops;

        $stats = [
            'total_troops' => $troops->sum('quantity'),
            'total_attack' => 0,
            'total_defense' => 0,
            'total_carry_capacity' => 0,
            'troop_types' => $troops->count(),
        ];

        foreach ($troops as $troop) {
            $unitType = $troop->unitType;
            $quantity = $troop->quantity;

            $stats['total_attack'] += $unitType->attack * $quantity;
            $stats['total_defense'] += ($unitType->defense_infantry + $unitType->defense_cavalry) * $quantity;
            $stats['total_carry_capacity'] += $unitType->carry_capacity * $quantity;
        }

        return $stats;
    }

    public function canAttack($attackerVillage, $defenderVillage)
    {
        // Check if attacker has troops
        $attackerTroops = $attackerVillage->troops()->sum('quantity');
        if ($attackerTroops === 0) {
            return false;
        }

        // Check if defender is the same player
        if ($attackerVillage->player_id === $defenderVillage->player_id) {
            return false;
        }

        // Check if defender is in the same alliance
        if ($attackerVillage->player->alliance_id &&
                $attackerVillage->player->alliance_id === $defenderVillage->player->alliance_id) {
            return false;
        }

        return true;
    }

    public function calculateBattleResult($attackerTroops, $defenderTroops)
    {
        $attackerPower = 0;
        $defenderPower = 0;

        foreach ($attackerTroops as $troop) {
            $attackerPower += $troop->quantity * $troop->unitType->attack;
        }

        foreach ($defenderTroops as $troop) {
            $defenderPower += $troop->quantity * ($troop->unitType->defense_infantry + $troop->unitType->defense_cavalry);
        }

        $attackerWin = $attackerPower > $defenderPower;
        $attackerLosses = $attackerWin ? $defenderPower * 0.1 : $attackerPower * 0.8;
        $defenderLosses = $attackerWin ? $attackerPower * 0.8 : $defenderPower * 0.1;

        return [
            'attacker_wins' => $attackerWin,
            'attacker_losses' => $attackerLosses,
            'defender_losses' => $defenderLosses,
            'attacker_power' => $attackerPower,
            'defender_power' => $defenderPower,
        ];
    }
}
