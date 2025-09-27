<?php

namespace App\Services;

use App\Models\Game\UnitType;
use App\Models\Game\Village;
use App\Services\DefenseCalculationService;
use Illuminate\Support\Facades\Log;

class BattleSimulationService
{
    protected $defenseService;

    public function __construct()
    {
        $this->defenseService = new DefenseCalculationService();
    }

    /**
     * Simulate a battle between attacker and defender
     */
    public function simulateBattle(array $attackingTroops, array $defendingTroops, Village $defenderVillage, int $iterations = 1000): array
    {
        $startTime = microtime(true);

        ds('BattleSimulationService: Starting battle simulation', [
            'service' => 'BattleSimulationService',
            'attacker_troops' => $attackingTroops,
            'defender_troops' => $defendingTroops,
            'defender_village_id' => $defenderVillage->id,
            'iterations' => $iterations,
            'simulation_time' => now()
        ]);

        $results = [
            'attacker_wins' => 0,
            'defender_wins' => 0,
            'draws' => 0,
            'attacker_avg_losses' => [],
            'defender_avg_losses' => [],
            'avg_resources_looted' => [],
            'battle_power_stats' => [
                'attacker_avg' => 0,
                'defender_avg' => 0,
                'attacker_min' => PHP_INT_MAX,
                'defender_min' => PHP_INT_MAX,
                'attacker_max' => 0,
                'defender_max' => 0,
            ],
            'defensive_bonus' => $this->defenseService->calculateDefensiveBonus($defenderVillage),
        ];

        $totalAttackerPower = 0;
        $totalDefenderPower = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $battleResult = $this->calculateSingleBattle($attackingTroops, $defendingTroops, $defenderVillage);

            // Count results
            switch ($battleResult['result']) {
                case 'attacker_wins':
                    $results['attacker_wins']++;
                    break;
                case 'defender_wins':
                    $results['defender_wins']++;
                    break;
                case 'draw':
                    $results['draws']++;
                    break;
            }

            // Track battle power statistics
            $attackerPower = $battleResult['battle_power']['attacker'];
            $defenderPower = $battleResult['battle_power']['defender'];

            $totalAttackerPower += $attackerPower;
            $totalDefenderPower += $defenderPower;

            $results['battle_power_stats']['attacker_min'] = min($results['battle_power_stats']['attacker_min'], $attackerPower);
            $results['battle_power_stats']['attacker_max'] = max($results['battle_power_stats']['attacker_max'], $attackerPower);
            $results['battle_power_stats']['defender_min'] = min($results['battle_power_stats']['defender_min'], $defenderPower);
            $results['battle_power_stats']['defender_max'] = max($results['battle_power_stats']['defender_max'], $defenderPower);

            // Accumulate losses for averaging
            foreach ($battleResult['attacker_losses'] as $loss) {
                if (!isset($results['attacker_avg_losses'][$loss['unit_type']])) {
                    $results['attacker_avg_losses'][$loss['unit_type']] = 0;
                }
                $results['attacker_avg_losses'][$loss['unit_type']] += $loss['count'];
            }

            foreach ($battleResult['defender_losses'] as $loss) {
                if (!isset($results['defender_avg_losses'][$loss['unit_type']])) {
                    $results['defender_avg_losses'][$loss['unit_type']] = 0;
                }
                $results['defender_avg_losses'][$loss['unit_type']] += $loss['count'];
            }

            // Accumulate resources looted
            if ($battleResult['resources_looted']) {
                foreach ($battleResult['resources_looted'] as $resource => $amount) {
                    if (!isset($results['avg_resources_looted'][$resource])) {
                        $results['avg_resources_looted'][$resource] = 0;
                    }
                    $results['avg_resources_looted'][$resource] += $amount;
                }
            }
        }

        // Calculate averages
        $results['battle_power_stats']['attacker_avg'] = $totalAttackerPower / $iterations;
        $results['battle_power_stats']['defender_avg'] = $totalDefenderPower / $iterations;

        foreach ($results['attacker_avg_losses'] as $unitType => $totalLosses) {
            $results['attacker_avg_losses'][$unitType] = round($totalLosses / $iterations);
        }

        foreach ($results['defender_avg_losses'] as $unitType => $totalLosses) {
            $results['defender_avg_losses'][$unitType] = round($totalLosses / $iterations);
        }

        foreach ($results['avg_resources_looted'] as $resource => $totalLoot) {
            $results['avg_resources_looted'][$resource] = round($totalLoot / $iterations);
        }

        // Calculate win percentages
        $results['attacker_win_rate'] = ($results['attacker_wins'] / $iterations) * 100;
        $results['defender_win_rate'] = ($results['defender_wins'] / $iterations) * 100;
        $results['draw_rate'] = ($results['draws'] / $iterations) * 100;

        $totalTime = round((microtime(true) - $startTime) * 1000, 2);

        ds('BattleSimulationService: Battle simulation completed', [
            'total_simulation_time_ms' => $totalTime,
            'iterations_completed' => $iterations,
            'attacker_win_rate' => $results['attacker_win_rate'],
            'defender_win_rate' => $results['defender_win_rate'],
            'draw_rate' => $results['draw_rate'],
            'defensive_bonus' => $results['defensive_bonus']
        ]);

        return $results;
    }

    /**
     * Calculate a single battle result
     */
    private function calculateSingleBattle(array $attackingTroops, array $defendingTroops, Village $defenderVillage): array
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
        $defensiveBonus = $this->defenseService->calculateDefensiveBonus($defenderVillage);
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
            'defensive_bonus' => $defensiveBonus,
        ];
    }

    /**
     * Calculate troop losses based on loss rate
     */
    private function calculateTroopLosses(array $troops, float $lossRate): array
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

    /**
     * Calculate resource loot
     */
    private function calculateResourceLoot(Village $defenderVillage): array
    {
        $villageResources = $defenderVillage->resources;
        $lootRate = 0.1 + (rand(0, 15) / 100);  // 10-25% loot rate

        $loot = [];
        foreach ($villageResources as $resource) {
            $availableAmount = $resource->amount;
            $lootAmount = floor($availableAmount * $lootRate);
            $loot[$resource->type] = min($lootAmount, $availableAmount);
        }

        return $loot;
    }

    /**
     * Optimize troop composition for attack
     */
    public function optimizeTroopComposition(Village $targetVillage, array $availableTroops, int $totalTroops): array
    {
        $defenderTroops = $this->getDefenderTroops($targetVillage);
        $bestComposition = [];
        $bestWinRate = 0;

        // Generate different troop combinations
        $combinations = $this->generateTroopCombinations($availableTroops, $totalTroops);

        foreach ($combinations as $combination) {
            $simulation = $this->simulateBattle($combination, $defenderTroops, $targetVillage, 100);

            if ($simulation['attacker_win_rate'] > $bestWinRate) {
                $bestWinRate = $simulation['attacker_win_rate'];
                $bestComposition = $combination;
            }
        }

        return [
            'composition' => $bestComposition,
            'win_rate' => $bestWinRate,
            'simulation' => $this->simulateBattle($bestComposition, $defenderTroops, $targetVillage, 1000),
        ];
    }

    /**
     * Get defender troops from village
     */
    private function getDefenderTroops(Village $village): array
    {
        $troops = $village->troops()->with('unitType')->get();
        $defenderTroops = [];

        foreach ($troops as $troop) {
            if ($troop->in_village > 0) {
                $defenderTroops[] = [
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

        return $defenderTroops;
    }

    /**
     * Generate troop combinations for optimization
     */
    private function generateTroopCombinations(array $availableTroops, int $totalTroops): array
    {
        $combinations = [];
        $troopTypes = array_keys($availableTroops);

        // Simple optimization: try different ratios
        $ratios = [
            [1, 0, 0, 0],  // All first type
            [0.8, 0.2, 0, 0],  // 80% first, 20% second
            [0.6, 0.4, 0, 0],  // 60% first, 40% second
            [0.5, 0.5, 0, 0],  // 50/50
            [0.4, 0.3, 0.3, 0],  // Mixed
            [0.3, 0.3, 0.2, 0.2],  // All types
        ];

        foreach ($ratios as $ratio) {
            $combination = [];
            $remaining = $totalTroops;

            for ($i = 0; $i < count($troopTypes) && $remaining > 0; $i++) {
                if (isset($availableTroops[$troopTypes[$i]])) {
                    $troopType = $availableTroops[$troopTypes[$i]];
                    $count = min(floor($totalTroops * $ratio[$i]), $remaining);

                    if ($count > 0) {
                        $combination[] = [
                            'troop_id' => $troopType['id'],
                            'unit_type' => $troopType['name'],
                            'count' => $count,
                            'attack' => $troopType['attack'],
                            'defense_infantry' => $troopType['defense_infantry'],
                            'defense_cavalry' => $troopType['defense_cavalry'],
                            'speed' => $troopType['speed'],
                        ];
                        $remaining -= $count;
                    }
                }
            }

            if (!empty($combination)) {
                $combinations[] = $combination;
            }
        }

        return $combinations;
    }

    /**
     * Analyze battle history for patterns
     */
    public function analyzeBattleHistory(Village $village, int $days = 30): array
    {
        $battles = \App\Models\Game\Battle::where('village_id', $village->id)
            ->where('occurred_at', '>=', now()->subDays($days))
            ->get();

        $analysis = [
            'total_battles' => $battles->count(),
            'attacks_received' => $battles->where('battle_type', 'attack')->count(),
            'attacks_launched' => 0,  // Would need to query from attacker perspective
            'defense_success_rate' => 0,
            'average_defensive_bonus' => 0,
            'most_common_attacker_troops' => [],
            'resource_losses' => [],
            'battle_frequency' => [],
        ];

        if ($battles->count() > 0) {
            $defenderWins = $battles->where('result', 'defender_wins')->count();
            $analysis['defense_success_rate'] = ($defenderWins / $battles->count()) * 100;

            $totalDefensiveBonus = 0;
            foreach ($battles as $battle) {
                if (isset($battle->battle_data['defensive_bonus'])) {
                    $totalDefensiveBonus += $battle->battle_data['defensive_bonus'];
                }
            }
            $analysis['average_defensive_bonus'] = $totalDefensiveBonus / $battles->count();

            // Analyze resource losses
            $totalResourcesLost = [];
            foreach ($battles as $battle) {
                if ($battle->resources_looted) {
                    foreach ($battle->resources_looted as $resource => $amount) {
                        if (!isset($totalResourcesLost[$resource])) {
                            $totalResourcesLost[$resource] = 0;
                        }
                        $totalResourcesLost[$resource] += $amount;
                    }
                }
            }
            $analysis['resource_losses'] = $totalResourcesLost;
        }

        return $analysis;
    }

    /**
     * Get battle recommendations based on analysis
     */
    public function getBattleRecommendations(Village $village): array
    {
        $analysis = $this->analyzeBattleHistory($village);
        $defenseReport = $this->defenseService->getDefenseReport($village);
        $recommendations = [];

        // Defense recommendations
        if ($analysis['defense_success_rate'] < 50) {
            $recommendations[] = [
                'type' => 'defense',
                'priority' => 'high',
                'message' => 'Your defense success rate is low. Consider upgrading defensive buildings.',
                'current_rate' => $analysis['defense_success_rate'],
                'target_rate' => 70,
            ];
        }

        if ($defenseReport['defensive_bonus'] < 0.2) {
            $recommendations[] = [
                'type' => 'defense',
                'priority' => 'medium',
                'message' => 'Your defensive bonus is low. Upgrade walls and watchtowers.',
                'current_bonus' => $defenseReport['defensive_bonus'] * 100,
                'target_bonus' => 20,
            ];
        }

        // Resource protection recommendations
        if (!empty($analysis['resource_losses'])) {
            $totalLost = array_sum($analysis['resource_losses']);
            if ($totalLost > 10000) {
                $recommendations[] = [
                    'type' => 'resources',
                    'priority' => 'medium',
                    'message' => 'You have lost significant resources in battles. Consider upgrading storage buildings.',
                    'resources_lost' => $totalLost,
                ];
            }
        }

        return $recommendations;
    }
}
