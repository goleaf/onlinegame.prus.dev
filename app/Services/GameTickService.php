<?php

namespace App\Services;

use App\Models\Game\Battle;
use App\Models\Game\BuildingQueue;
use App\Models\Game\GameEvent;
use App\Models\Game\Movement;
use App\Models\Game\Player;
use App\Models\Game\Report;
use App\Models\Game\Resource;
use App\Models\Game\TrainingQueue;
use App\Models\Game\Village;
use App\Services\RabbitMQService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameTickService
{
    protected $rabbitMQ;

    public function __construct()
    {
        $this->rabbitMQ = new RabbitMQService();
    }

    public function processGameTick()
    {
        DB::beginTransaction();

        try {
            // Process resource production
            $this->processResourceProduction();

            // Process building queues
            $this->processBuildingQueues();

            // Process training queues
            $this->processTrainingQueues();

            // Process movements (attacks, support, etc.)
            $this->processMovements();

            // Process game events
            $this->processGameEvents();

            // Update player statistics
            $this->updatePlayerStatistics();

            DB::commit();

            Log::info('Game tick processed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Game tick failed: ' . $e->getMessage());

            throw $e;
        }
    }

    private function processResourceProduction()
    {
        $villages = Village::with('resources')->get();

        foreach ($villages as $village) {
            $resources = $village->resources;

            foreach ($resources as $resource) {
                $timeSinceLastUpdate = now()->diffInSeconds($resource->last_updated);

                // Calculate production rate based on buildings
                $productionRate = $this->calculateResourceProduction($village, $resource->type);
                $production = $productionRate * $timeSinceLastUpdate;

                $newAmount = min(
                    $resource->amount + $production,
                    $resource->storage_capacity
                );

                if ($newAmount !== $resource->amount) {
                    $resource->update([
                        'amount' => $newAmount,
                        'production_rate' => $productionRate,
                        'last_updated' => now(),
                    ]);

                    // Publish resource update to RabbitMQ
                    $this->rabbitMQ->publishResourceUpdate($village->id, [
                        $resource->type => $newAmount
                    ]);
                }
            }
        }
    }

    private function processBuildingQueues()
    {
        $completedBuildings = BuildingQueue::where('completed_at', '<=', now())
            ->where('status', 'in_progress')
            ->get();

        foreach ($completedBuildings as $building) {
            $this->completeBuilding($building);
        }
    }

    private function processTrainingQueues()
    {
        $completedTraining = TrainingQueue::where('completed_at', '<=', now())
            ->where('status', 'in_progress')
            ->get();

        foreach ($completedTraining as $training) {
            $this->completeTraining($training);
        }
    }

    private function processMovements()
    {
        $arrivedMovements = Movement::where('arrives_at', '<=', now())
            ->where('status', 'travelling')
            ->with(['fromVillage', 'toVillage', 'player'])
            ->get();

        foreach ($arrivedMovements as $movement) {
            $this->processMovementArrival($movement);
        }
    }

    private function processMovementArrival(Movement $movement)
    {
        try {
            $movement->update(['status' => 'arrived']);

            switch ($movement->type) {
                case 'attack':
                    $this->processAttack($movement);
                    break;
                case 'support':
                    $this->processSupport($movement);
                    break;
                case 'spy':
                    $this->processSpy($movement);
                    break;
                case 'trade':
                    $this->processTrade($movement);
                    break;
                default:
                    Log::warning("Unknown movement type: {$movement->type}");
            }

            // Schedule return movement
            $this->scheduleReturnMovement($movement);
        } catch (\Exception $e) {
            Log::error("Failed to process movement {$movement->id}: " . $e->getMessage());
        }
    }

    private function processAttack(Movement $movement)
    {
        $attackerVillage = $movement->fromVillage;
        $defenderVillage = $movement->toVillage;
        $attackingTroops = $movement->troops;

        // Get defending troops
        $defendingTroops = $defenderVillage
            ->troops()
            ->with('unitType')
            ->where('in_village', '>', 0)
            ->get()
            ->map(function ($troop) {
                return [
                    'troop_id' => $troop->id,
                    'unit_type' => $troop->unitType->name,
                    'count' => $troop->in_village,
                    'attack' => $troop->unitType->attack_power,
                    'defense_infantry' => $troop->unitType->defense_power,
                    'defense_cavalry' => $troop->unitType->defense_power,
                    'speed' => $troop->unitType->speed,
                ];
            })
            ->toArray();

        // Calculate battle result
        $battleResult = $this->calculateBattleResult($attackingTroops, $defendingTroops, $defenderVillage);

        // Create battle record
        $battle = Battle::create([
            'attacker_id' => $attackerVillage->player_id,
            'defender_id' => $defenderVillage->player_id,
            'village_id' => $defenderVillage->id,
            'battle_type' => 'attack',
            'result' => $battleResult['result'],
            'attacker_losses' => $battleResult['attacker_losses'],
            'defender_losses' => $battleResult['defender_losses'],
            'resources_looted' => $battleResult['resources_looted'],
            'battle_data' => [
                'attacking_troops' => $attackingTroops,
                'defending_troops' => $defendingTroops,
                'battle_power' => $battleResult['battle_power'],
            ],
            'occurred_at' => now(),
        ]);

        // Update troop counts
        $this->updateTroopLosses($attackerVillage, $battleResult['attacker_losses']);
        $this->updateTroopLosses($defenderVillage, $battleResult['defender_losses']);

        // Loot resources if attacker wins
        if ($battleResult['result'] === 'attacker_wins') {
            $this->lootResources($defenderVillage, $battleResult['resources_looted']);
            $this->addLootToAttacker($attackerVillage, $battleResult['resources_looted']);
        }

        // Create battle reports
        $this->createBattleReports($battle);

        // Publish battle result to RabbitMQ
        $this->rabbitMQ->publishBattleResult(
            $attackerVillage->player_id,
            $defenderVillage->player_id,
            [
                'battle_id' => $battle->id,
                'result' => $battleResult['result'],
                'attacker_losses' => $battleResult['attacker_losses'],
                'defender_losses' => $battleResult['defender_losses'],
                'resources_looted' => $battleResult['resources_looted'],
                'village_id' => $defenderVillage->id,
                'village_name' => $defenderVillage->name,
            ]
        );

        Log::info("Battle processed: {$battleResult['result']} at village {$defenderVillage->name}");
    }

    private function calculateBattleResult($attackingTroops, $defendingTroops, $defenderVillage)
    {
        $attackerPower = 0;
        $defenderPower = 0;

        // Calculate attacker power
        foreach ($attackingTroops as $troop) {
            $attackerPower += $troop['count'] * $troop['attack'];
        }

        // Calculate defender power
        foreach ($defendingTroops as $troop) {
            $defenderPower += $troop['count'] * ($troop['defense_infantry'] + $troop['defense_cavalry']);
        }

        // Apply defensive bonuses from buildings
        $defensiveBonus = $this->calculateDefensiveBonus($defenderVillage);
        $defenderPower *= (1 + $defensiveBonus);

        // Add randomness (80-120% of calculated power)
        $attackerPower *= (0.8 + (rand(0, 40) / 100));
        $defenderPower *= (0.8 + (rand(0, 40) / 100));

        $result = 'draw';
        $attackerLosses = [];
        $defenderLosses = [];
        $resourcesLooted = [];

        if ($attackerPower > $defenderPower) {
            $result = 'attacker_wins';
            // Calculate losses (attacker loses 10-30%, defender loses 50-80%)
            $attackerLossRate = 0.1 + (rand(0, 20) / 100);
            $defenderLossRate = 0.5 + (rand(0, 30) / 100);

            $attackerLosses = $this->calculateTroopLosses($attackingTroops, $attackerLossRate);
            $defenderLosses = $this->calculateTroopLosses($defendingTroops, $defenderLossRate);

            // Calculate loot (10-25% of defender's resources)
            $resourcesLooted = $this->calculateResourceLoot($defenderVillage);
        } elseif ($defenderPower > $attackerPower) {
            $result = 'defender_wins';
            // Calculate losses (attacker loses 50-80%, defender loses 10-30%)
            $attackerLossRate = 0.5 + (rand(0, 30) / 100);
            $defenderLossRate = 0.1 + (rand(0, 20) / 100);

            $attackerLosses = $this->calculateTroopLosses($attackingTroops, $attackerLossRate);
            $defenderLosses = $this->calculateTroopLosses($defendingTroops, $defenderLossRate);
        } else {
            // Draw - both sides lose 20-40%
            $lossRate = 0.2 + (rand(0, 20) / 100);
            $attackerLosses = $this->calculateTroopLosses($attackingTroops, $lossRate);
            $defenderLosses = $this->calculateTroopLosses($defendingTroops, $lossRate);
        }

        return [
            'result' => $result,
            'attacker_losses' => $attackerLosses,
            'defender_losses' => $defenderLosses,
            'resources_looted' => $resourcesLooted,
            'battle_power' => [
                'attacker' => $attackerPower,
                'defender' => $defenderPower,
            ],
        ];
    }

    private function calculateTroopLosses($troops, $lossRate)
    {
        $losses = [];
        foreach ($troops as $troop) {
            $losses[] = [
                'troop_id' => $troop['troop_id'],
                'unit_type' => $troop['unit_type'],
                'count' => max(0, floor($troop['count'] * $lossRate)),
            ];
        }
        return $losses;
    }

    private function calculateResourceLoot($defendingVillage)
    {
        // Get actual village resources for realistic loot calculation
        $villageResources = $defendingVillage->resources;
        $lootRate = 0.1 + (rand(0, 15) / 100);  // 10-25% loot rate

        $loot = [];
        foreach ($villageResources as $resource) {
            $availableAmount = $resource->amount;
            $lootAmount = floor($availableAmount * $lootRate);
            $loot[$resource->type] = min($lootAmount, $availableAmount);
        }

        return $loot;
    }

    private function updateTroopLosses($village, $losses)
    {
        foreach ($losses as $loss) {
            $troop = $village->troops()->find($loss['troop_id']);
            if ($troop && $loss['count'] > 0) {
                $troop->decrement('in_village', $loss['count']);
            }
        }
    }

    private function lootResources($village, $loot)
    {
        foreach ($loot as $resourceType => $amount) {
            $resource = $village->resources()->where('type', $resourceType)->first();
            if ($resource && $amount > 0) {
                $resource->decrement('amount', min($amount, $resource->amount));
            }
        }
    }

    private function addLootToAttacker($village, $loot)
    {
        foreach ($loot as $resourceType => $amount) {
            $resource = $village->resources()->where('type', $resourceType)->first();
            if ($resource && $amount > 0) {
                $resource->increment('amount', $amount);
            }
        }
    }

    private function createBattleReports(Battle $battle)
    {
        // Create detailed report for attacker
        Report::create([
            'world_id' => $battle->village->world_id,
            'attacker_id' => $battle->attacker_id,
            'defender_id' => $battle->defender_id,
            'from_village_id' => $battle->battle_data['attacking_troops'][0]['from_village_id'] ?? null,
            'to_village_id' => $battle->village_id,
            'type' => 'attack',
            'status' => $battle->result === 'attacker_wins' ? 'victory' : ($battle->result === 'defender_wins' ? 'defeat' : 'draw'),
            'title' => $this->generateBattleReportTitle($battle, 'attacker'),
            'content' => $this->generateDetailedBattleReportContent($battle, 'attacker'),
            'battle_data' => [
                'battle_id' => $battle->id,
                'result' => $battle->result,
                'attacker_losses' => $battle->attacker_losses,
                'defender_losses' => $battle->defender_losses,
                'resources_looted' => $battle->resources_looted,
                'battle_power' => $battle->battle_data['battle_power'],
                'attacking_troops' => $battle->battle_data['attacking_troops'],
                'defending_troops' => $battle->battle_data['defending_troops'],
                'casualties_summary' => $this->generateCasualtiesSummary($battle->attacker_losses),
                'loot_summary' => $this->generateLootSummary($battle->resources_looted),
            ],
            'is_read' => false,
            'is_important' => $battle->result === 'attacker_wins',
        ]);

        // Create detailed report for defender
        Report::create([
            'world_id' => $battle->village->world_id,
            'attacker_id' => $battle->attacker_id,
            'defender_id' => $battle->defender_id,
            'from_village_id' => $battle->battle_data['attacking_troops'][0]['from_village_id'] ?? null,
            'to_village_id' => $battle->village_id,
            'type' => 'defense',
            'status' => $battle->result === 'attacker_wins' ? 'defeat' : ($battle->result === 'defender_wins' ? 'victory' : 'draw'),
            'title' => $this->generateBattleReportTitle($battle, 'defender'),
            'content' => $this->generateDetailedBattleReportContent($battle, 'defender'),
            'battle_data' => [
                'battle_id' => $battle->id,
                'result' => $battle->result,
                'attacker_losses' => $battle->attacker_losses,
                'defender_losses' => $battle->defender_losses,
                'resources_looted' => $battle->resources_looted,
                'battle_power' => $battle->battle_data['battle_power'],
                'attacking_troops' => $battle->battle_data['attacking_troops'],
                'defending_troops' => $battle->battle_data['defending_troops'],
                'casualties_summary' => $this->generateCasualtiesSummary($battle->defender_losses),
                'loot_summary' => $this->generateLootSummary($battle->resources_looted),
            ],
            'is_read' => false,
            'is_important' => $battle->result === 'defender_wins',
        ]);
    }

    private function generateBattleReportTitle(Battle $battle, $perspective)
    {
        $result = $battle->result;
        if ($perspective === 'defender') {
            $result = $battle->result === 'attacker_wins' ? 'defeat' : 'victory';
        }

        $status = match ($result) {
            'attacker_wins' => 'Victory',
            'defender_wins' => 'Defeat',
            'draw' => 'Draw',
            default => 'Unknown'
        };

        return "Battle Report - {$status} at {$battle->village->name}";
    }

    private function generateDetailedBattleReportContent(Battle $battle, $perspective)
    {
        $result = $battle->result;
        $isAttacker = $perspective === 'attacker';

        if ($perspective === 'defender') {
            $result = $battle->result === 'attacker_wins' ? 'defeat' : 'victory';
        }

        $status = match ($result) {
            'attacker_wins' => 'Victory',
            'defender_wins' => 'Defeat',
            'draw' => 'Draw',
            default => 'Unknown'
        };

        $content = "=== BATTLE REPORT ===\n\n";
        $content .= "Location: {$battle->village->name}\n";
        $content .= "Result: {$status}\n";
        $content .= 'Date: ' . $battle->occurred_at->format('Y-m-d H:i:s') . "\n\n";

        // Battle Power Summary
        $content .= "=== BATTLE POWER ===\n";
        $content .= 'Attacker Power: ' . number_format($battle->battle_data['battle_power']['attacker'], 0) . "\n";
        $content .= 'Defender Power: ' . number_format($battle->battle_data['battle_power']['defender'], 0) . "\n\n";

        // Troop Summary
        $content .= "=== TROOP SUMMARY ===\n";
        if ($isAttacker) {
            $content .= "Your Troops Sent:\n";
            foreach ($battle->battle_data['attacking_troops'] as $troop) {
                $content .= "- {$troop['unit_type']}: {$troop['count']}\n";
            }
            $content .= "\nEnemy Defenders:\n";
            foreach ($battle->battle_data['defending_troops'] as $troop) {
                $content .= "- {$troop['unit_type']}: {$troop['count']}\n";
            }
        } else {
            $content .= "Enemy Attackers:\n";
            foreach ($battle->battle_data['attacking_troops'] as $troop) {
                $content .= "- {$troop['unit_type']}: {$troop['count']}\n";
            }
            $content .= "\nYour Defenders:\n";
            foreach ($battle->battle_data['defending_troops'] as $troop) {
                $content .= "- {$troop['unit_type']}: {$troop['count']}\n";
            }
        }

        // Casualties
        $content .= "\n=== CASUALTIES ===\n";
        $losses = $isAttacker ? $battle->attacker_losses : $battle->defender_losses;
        $totalLosses = 0;
        foreach ($losses as $loss) {
            if ($loss['count'] > 0) {
                $content .= "- {$loss['unit_type']}: {$loss['count']} lost\n";
                $totalLosses += $loss['count'];
            }
        }
        if ($totalLosses === 0) {
            $content .= "No casualties\n";
        } else {
            $content .= "Total Losses: {$totalLosses}\n";
        }

        // Loot Information
        if ($battle->resources_looted && $isAttacker && $battle->result === 'attacker_wins') {
            $content .= "\n=== LOOT ===\n";
            $totalLoot = 0;
            foreach ($battle->resources_looted as $resource => $amount) {
                if ($amount > 0) {
                    $content .= '- ' . ucfirst($resource) . ': ' . number_format($amount) . "\n";
                    $totalLoot += $amount;
                }
            }
            if ($totalLoot > 0) {
                $content .= 'Total Loot: ' . number_format($totalLoot) . " resources\n";
            } else {
                $content .= "No resources looted\n";
            }
        } elseif (!$isAttacker && $battle->resources_looted) {
            $content .= "\n=== RESOURCES LOST ===\n";
            $totalLost = 0;
            foreach ($battle->resources_looted as $resource => $amount) {
                if ($amount > 0) {
                    $content .= '- ' . ucfirst($resource) . ': ' . number_format($amount) . " lost\n";
                    $totalLost += $amount;
                }
            }
            if ($totalLost > 0) {
                $content .= 'Total Lost: ' . number_format($totalLost) . " resources\n";
            }
        }

        // Battle Analysis
        $content .= "\n=== BATTLE ANALYSIS ===\n";
        if ($battle->result === 'attacker_wins') {
            $content .= "The attack was successful! Your forces overwhelmed the defenders.\n";
        } elseif ($battle->result === 'defender_wins') {
            $content .= "The defense was successful! Your forces repelled the attackers.\n";
        } else {
            $content .= "The battle ended in a draw. Both sides suffered heavy losses.\n";
        }

        return $content;
    }

    private function generateCasualtiesSummary($losses)
    {
        $summary = [];
        $totalLosses = 0;

        foreach ($losses as $loss) {
            if ($loss['count'] > 0) {
                $summary[] = "{$loss['unit_type']}: {$loss['count']}";
                $totalLosses += $loss['count'];
            }
        }

        return [
            'total' => $totalLosses,
            'breakdown' => $summary,
            'formatted' => $totalLosses > 0 ? implode(', ', $summary) : 'No casualties'
        ];
    }

    private function generateLootSummary($loot)
    {
        $summary = [];
        $totalLoot = 0;

        foreach ($loot as $resource => $amount) {
            if ($amount > 0) {
                $summary[] = ucfirst($resource) . ': ' . number_format($amount);
                $totalLoot += $amount;
            }
        }

        return [
            'total' => $totalLoot,
            'breakdown' => $summary,
            'formatted' => $totalLoot > 0 ? implode(', ', $summary) : 'No loot'
        ];
    }

    private function generateBattleReportContent(Battle $battle, $perspective)
    {
        $result = $battle->result;
        if ($perspective === 'defender') {
            $result = $battle->result === 'attacker_wins' ? 'defeat' : 'victory';
        }

        return "Battle at {$battle->village->name}: {$result}. "
            . "Attacker power: {$battle->battle_data['battle_power']['attacker']}, "
            . "Defender power: {$battle->battle_data['battle_power']['defender']}";
    }

    private function calculateDefensiveBonus($village)
    {
        $totalBonus = 0;
        
        // Get all buildings for the village
        $buildings = $village->buildings()->with('buildingType')->get();
        
        foreach ($buildings as $building) {
            $buildingType = $building->buildingType;
            $level = $building->level;
            
            switch ($buildingType->key) {
                case 'wall':
                    // Wall provides 2% defense bonus per level
                    $totalBonus += ($level * 0.02);
                    break;
                    
                case 'watchtower':
                    // Watchtower provides 1.5% defense bonus per level
                    $totalBonus += ($level * 0.015);
                    break;
                    
                case 'trap':
                    // Trap provides 1% defense bonus per level
                    $totalBonus += ($level * 0.01);
                    break;
                    
                case 'rally_point':
                    // Rally point provides 0.5% defense bonus per level
                    $totalBonus += ($level * 0.005);
                    break;
            }
        }
        
        // Cap defensive bonus at 50% (level 20 wall = 40% + other buildings)
        return min($totalBonus, 0.5);
    }

    private function calculateSpyDefense($village)
    {
        $spyDefense = 0;
        
        // Get trap level for spy defense
        $trap = $village->buildings()
            ->whereHas('buildingType', function ($query) {
                $query->where('key', 'trap');
            })
            ->first();
            
        if ($trap) {
            // Each trap level provides 5% chance to catch spies
            $spyDefense = $trap->level * 5;
        }
        
        return min($spyDefense, 100); // Cap at 100%
    }

    private function processSpyDefense($movement)
    {
        $targetVillage = $movement->toVillage;
        $spyDefense = $this->calculateSpyDefense($targetVillage);
        
        // Check if spy is caught
        $spyCaught = (rand(1, 100) <= $spyDefense);
        
        if ($spyCaught) {
            // Spy is caught - create failure report
            Report::create([
                'world_id' => $targetVillage->world_id,
                'attacker_id' => $movement->player_id,
                'defender_id' => $targetVillage->player_id,
                'from_village_id' => $movement->from_village_id,
                'to_village_id' => $targetVillage->id,
                'type' => 'spy',
                'status' => 'failure',
                'title' => 'Spy Mission Failed',
                'content' => "Your spy was caught by the traps at {$targetVillage->name}. The mission failed.",
                'battle_data' => [
                    'spy_caught' => true,
                    'trap_level' => $targetVillage->buildings()
                        ->whereHas('buildingType', function ($query) {
                            $query->where('key', 'trap');
                        })
                        ->first()?->level ?? 0,
                ],
                'is_read' => false,
                'is_important' => false,
            ]);
            
            Log::info("Spy caught at village {$targetVillage->name}");
            return;
        }
        
        // Spy succeeds - create success report
        Report::create([
            'world_id' => $targetVillage->world_id,
            'attacker_id' => $movement->player_id,
            'defender_id' => $targetVillage->player_id,
            'from_village_id' => $movement->from_village_id,
            'to_village_id' => $targetVillage->id,
            'type' => 'spy',
            'status' => 'success',
            'title' => 'Spy Report',
            'content' => "Spy report from {$targetVillage->name}: " .
                        "Population: {$targetVillage->population}, " .
                        "Resources: " . $this->getVillageResourceSummary($targetVillage),
            'battle_data' => [
                'spy_caught' => false,
                'spy_data' => $this->getSpyData($targetVillage),
            ],
            'is_read' => false,
            'is_important' => true,
        ]);

        Log::info("Spy mission completed at village {$targetVillage->name}");
    }

    private function processSupport(Movement $movement)
    {
        // Add supporting troops to target village
        $targetVillage = $movement->toVillage;
        $supportingTroops = $movement->troops;

        foreach ($supportingTroops as $troop) {
            $villageTroop = $targetVillage
                ->troops()
                ->where('unit_type_id', $troop['unit_type_id'])
                ->first();

            if ($villageTroop) {
                $villageTroop->increment('in_village', $troop['count']);
            } else {
                $targetVillage->troops()->create([
                    'unit_type_id' => $troop['unit_type_id'],
                    'quantity' => $troop['count'],
                    'in_village' => $troop['count'],
                ]);
            }
        }

        Log::info("Support troops arrived at village {$targetVillage->name}");
    }

    private function processSpy(Movement $movement)
    {
        // Create spy report
        $targetVillage = $movement->toVillage;

        Report::create([
            'player_id' => $movement->player_id,
            'type' => 'spy',
            'title' => 'Spy Report',
            'content' => "Spy report from {$targetVillage->name}: "
                . "Population: {$targetVillage->population}, "
                . 'Resources: ' . $this->getVillageResourceSummary($targetVillage),
            'data' => [
                'village_id' => $targetVillage->id,
                'spy_data' => $this->getSpyData($targetVillage),
            ],
            'is_read' => false,
        ]);

        Log::info("Spy mission completed at village {$targetVillage->name}");
    }

    private function processTrade(Movement $movement)
    {
        // Process trade movement
        $targetVillage = $movement->toVillage;
        $resources = $movement->resources ?? [];

        foreach ($resources as $resourceType => $amount) {
            $resource = $targetVillage->resources()->where('type', $resourceType)->first();
            if ($resource) {
                $resource->increment('amount', $amount);
            }
        }

        Log::info("Trade completed at village {$targetVillage->name}");
    }

    private function scheduleReturnMovement(Movement $movement)
    {
        // Calculate return time (same as travel time)
        $travelTime = $movement->arrives_at->diffInSeconds($movement->started_at);

        Movement::create([
            'player_id' => $movement->player_id,
            'from_village_id' => $movement->to_village_id,
            'to_village_id' => $movement->from_village_id,
            'type' => 'return',
            'troops' => $movement->troops,
            'resources' => $movement->resources,
            'started_at' => now(),
            'arrives_at' => now()->addSeconds($travelTime),
            'status' => 'travelling',
            'metadata' => ['original_movement_id' => $movement->id],
        ]);
    }

    private function getVillageResourceSummary($village)
    {
        $resources = $village->resources;
        $summary = [];
        foreach ($resources as $resource) {
            $summary[] = "{$resource->type}: {$resource->amount}";
        }
        return implode(', ', $summary);
    }

    private function getSpyData($village)
    {
        return [
            'population' => $village->population,
            'resources' => $village->resources->pluck('amount', 'type'),
            'buildings' => $village->buildings->pluck('level', 'building_type_id'),
            'troops' => $village->troops->pluck('in_village', 'unit_type_id'),
        ];
    }

    private function processGameEvents()
    {
        // Game events are processed immediately when created
        // This method can be used for scheduled events in the future
        return;
    }

    private function updatePlayerStatistics()
    {
        $players = Player::with(['villages.troops'])->get();

        foreach ($players as $player) {
            $totalPopulation = $player->villages->sum('population');
            $totalTroops = $player->villages->sum(function ($village) {
                return $village->troops->sum('quantity');
            });

            $player->update([
                'population' => $totalPopulation,
                'villages_count' => $player->villages->count(),
                'last_active_at' => now(),
            ]);
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

            // Create game event
            GameEvent::create([
                'player_id' => $building->village->player_id,
                'village_id' => $building->village_id,
                'type' => 'building_completed',
                'title' => 'Building Completed',
                'description' => "{$building->buildingType->name} reached level {$building->target_level}",
                'data' => [
                    'building_name' => $building->buildingType->name,
                    'level' => $building->target_level,
                    'village_id' => $building->village_id,
                ],
                'triggered_at' => now(),
                'is_completed' => true,
            ]);

            // Publish building completion to RabbitMQ
            $this->rabbitMQ->publishBuildingCompleted(
                $building->village_id,
                $building->building_type_id,
                $building->buildingType->name
            );
        } catch (\Exception $e) {
            Log::error("Failed to complete building {$building->id}: " . $e->getMessage());
        }
    }

    private function completeTraining($training)
    {
        try {
            $training->update(['status' => 'completed']);

            // Add troops to village
            $village = $training->village;
            $troop = $village
                ->troops()
                ->where('unit_type_id', $training->unit_type_id)
                ->first();

            if ($troop) {
                $troop->increment('quantity', $training->count);
                $troop->increment('in_village', $training->count);
            } else {
                $village->troops()->create([
                    'unit_type_id' => $training->unit_type_id,
                    'quantity' => $training->count,
                    'in_village' => $training->count,
                ]);
            }

            // Create game event
            GameEvent::create([
                'player_id' => $training->village->player_id,
                'village_id' => $training->village_id,
                'event_type' => 'training_completed',
                'event_subtype' => 'unit_training',
                'description' => "{$training->count} {$training->unitType->name} trained",
                'data' => [
                    'unit_name' => $training->unitType->name,
                    'quantity' => $training->count,
                    'village_id' => $training->village_id,
                ],
                'occurred_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to complete training {$training->id}: " . $e->getMessage());
        }
    }

    private function completeEvent($event)
    {
        try {
            $event->update(['is_completed' => true]);

            // Process event rewards if any
            if ($event->rewards) {
                $this->processEventRewards($event);
            }
        } catch (\Exception $e) {
            Log::error("Failed to complete event {$event->id}: " . $e->getMessage());
        }
    }

    private function processEventRewards($event)
    {
        $rewards = $event->rewards;

        if (isset($rewards['resources'])) {
            $village = $event->village;

            foreach ($rewards['resources'] as $resource => $amount) {
                $resourceModel = $village->resources()->where('type', $resource)->first();
                if ($resourceModel) {
                    $newAmount = min(
                        $resourceModel->amount + $amount,
                        $resourceModel->storage_capacity
                    );
                    $resourceModel->update(['amount' => $newAmount]);
                }
            }
        }
    }

    public function getGameTickStatus()
    {
        return [
            'last_tick' => now(),
            'pending_buildings' => BuildingQueue::where('is_completed', false)->count(),
            'pending_training' => TrainingQueue::where('is_completed', false)->count(),
            'pending_events' => GameEvent::where('is_completed', false)->count(),
        ];
    }

    /**
     * Calculate resource production rate for a village
     */
    public function calculateResourceProduction(Village $village, string $resourceType): int
    {
        $baseProduction = 10;  // Base production per second

        // Get building levels that affect this resource
        $buildingLevels = $this->getResourceBuildingLevels($village, $resourceType);

        // Calculate production based on building levels
        $production = $baseProduction;
        foreach ($buildingLevels as $level) {
            $production += $level * 2;  // Each level adds 2 production per second
        }

        return $production;
    }

    /**
     * Get building levels that affect a specific resource type
     */
    public function getResourceBuildingLevels(Village $village, string $resourceType): array
    {
        $buildingTypeMap = [
            'wood' => ['woodcutter'],
            'clay' => ['clay_pit'],
            'iron' => ['iron_mine'],
            'crop' => ['crop_field'],
        ];

        $buildingTypes = $buildingTypeMap[$resourceType] ?? [];

        if (empty($buildingTypes)) {
            return [];
        }

        $levels = [];
        foreach ($buildingTypes as $buildingType) {
            $building = $village
                ->buildings()
                ->whereHas('buildingType', function ($query) use ($buildingType) {
                    $query->where('key', $buildingType);
                })
                ->first();

            if ($building) {
                $levels[] = $building->level;
            }
        }

        return $levels;
    }

    /**
     * Create a game event
     */
    public function createGameEvent($player, $village, string $eventType, string $description, array $data = [])
    {
        return GameEvent::create([
            'player_id' => is_object($player) ? $player->id : $player,
            'village_id' => is_object($village) ? $village->id : $village,
            'event_type' => $eventType,
            'description' => $description,
            'data' => $data,
            'occurred_at' => now(),
        ]);
    }

    /**
     * Log resource production
     */
    public function logResourceProduction(Village $village, string $resourceType, int $amountProduced, int $finalAmount)
    {
        return \App\Models\Game\ResourceProductionLog::create([
            'village_id' => $village->id,
            'type' => $resourceType,
            'amount_produced' => $amountProduced,
            'final_amount' => $finalAmount,
            'produced_at' => now(),
        ]);
    }
}
