<?php

namespace App\Console\Commands;

use App\Models\Game\UnitType;
use App\Models\Game\Village;
use App\Services\BattleSimulationService;
use App\Services\DefenseCalculationService;
use Illuminate\Console\Command;

class BattleSimulationTest extends Command
{
    protected $signature = 'battle:simulate 
                            {--attacker-village=1 : Attacker village ID}
                            {--defender-village=2 : Defender village ID}
                            {--iterations=1000 : Number of simulation iterations}
                            {--optimize : Run troop optimization}
                            {--total-troops=100 : Total troops for optimization}';

    protected $description = 'Test battle simulation with real village data';

    public function handle()
    {
        $attackerVillageId = $this->option('attacker-village');
        $defenderVillageId = $this->option('defender-village');
        $iterations = (int) $this->option('iterations');
        $optimize = $this->option('optimize');
        $totalTroops = (int) $this->option('total-troops');

        $this->info('Starting battle simulation test...');
        $this->info("Attacker Village ID: {$attackerVillageId}");
        $this->info("Defender Village ID: {$defenderVillageId}");
        $this->info("Iterations: {$iterations}");

        // Load villages
        $attackerVillage = Village::with(['troops.unitType'])->find($attackerVillageId);
        $defenderVillage = Village::with(['troops.unitType', 'buildings.buildingType'])->find($defenderVillageId);

        if (! $attackerVillage) {
            $this->error("Attacker village not found: {$attackerVillageId}");

            return 1;
        }

        if (! $defenderVillage) {
            $this->error("Defender village not found: {$defenderVillageId}");

            return 1;
        }

        $this->info("Attacker Village: {$attackerVillage->name}");
        $this->info("Defender Village: {$defenderVillage->name}");

        // Initialize services
        $battleService = new BattleSimulationService();
        $defenseService = new DefenseCalculationService();

        // Get defender defense report
        $defenseReport = $defenseService->getDefenseReport($defenderVillage);
        $this->info('Defender Defensive Bonus: '.number_format($defenseReport['defensive_bonus'] * 100, 1).'%');
        $this->info("Defender Spy Defense: {$defenseReport['spy_defense']}%");

        // Prepare attacking troops
        $attackingTroops = [];
        foreach ($attackerVillage->troops as $troop) {
            if ($troop->in_village > 0) {
                $attackingTroops[] = [
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

        // Prepare defending troops
        $defendingTroops = [];
        foreach ($defenderVillage->troops as $troop) {
            if ($troop->in_village > 0) {
                $defendingTroops[] = [
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

        if (empty($attackingTroops)) {
            $this->error("No attacking troops found in village {$attackerVillage->name}");

            return 1;
        }

        if (empty($defendingTroops)) {
            $this->error("No defending troops found in village {$defenderVillage->name}");

            return 1;
        }

        $this->info('Attacking Troops:');
        foreach ($attackingTroops as $troop) {
            $this->line("  - {$troop['unit_type']}: {$troop['count']} (Attack: {$troop['attack']})");
        }

        $this->info('Defending Troops:');
        foreach ($defendingTroops as $troop) {
            $this->line("  - {$troop['unit_type']}: {$troop['count']} (Defense: {$troop['defense_infantry']})");
        }

        // Run simulation
        $this->info("\nRunning battle simulation...");
        $startTime = microtime(true);

        $results = $battleService->simulateBattle(
            $attackingTroops,
            $defendingTroops,
            $defenderVillage,
            $iterations
        );

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);

        // Display results
        $this->info("\n=== BATTLE SIMULATION RESULTS ===");
        $this->info("Execution Time: {$executionTime} seconds");
        $this->info("Iterations: {$iterations}");

        $this->info("\nWin Rates:");
        $this->line('  Attacker Wins: '.number_format($results['attacker_win_rate'], 1)."% ({$results['attacker_wins']} battles)");
        $this->line('  Defender Wins: '.number_format($results['defender_win_rate'], 1)."% ({$results['defender_wins']} battles)");
        $this->line('  Draws: '.number_format($results['draw_rate'], 1)."% ({$results['draws']} battles)");

        $this->info("\nBattle Power Statistics:");
        $this->line('  Attacker Average: '.number_format($results['battle_power_stats']['attacker_avg'], 0));
        $this->line('  Defender Average: '.number_format($results['battle_power_stats']['defender_avg'], 0));
        $this->line('  Attacker Range: '.number_format($results['battle_power_stats']['attacker_min']).' - '.number_format($results['battle_power_stats']['attacker_max']));
        $this->line('  Defender Range: '.number_format($results['battle_power_stats']['defender_min']).' - '.number_format($results['battle_power_stats']['defender_max']));

        $this->info("\nDefensive Bonus: ".number_format($results['defensive_bonus'] * 100, 1).'%');

        if (! empty($results['attacker_avg_losses'])) {
            $this->info("\nAttacker Average Losses:");
            foreach ($results['attacker_avg_losses'] as $unitType => $losses) {
                $this->line("  - {$unitType}: {$losses}");
            }
        }

        if (! empty($results['defender_avg_losses'])) {
            $this->info("\nDefender Average Losses:");
            foreach ($results['defender_avg_losses'] as $unitType => $losses) {
                $this->line("  - {$unitType}: {$losses}");
            }
        }

        if (! empty($results['avg_resources_looted'])) {
            $this->info("\nAverage Resources Looted:");
            foreach ($results['avg_resources_looted'] as $resource => $amount) {
                $this->line("  - {$resource}: ".number_format($amount));
            }
        }

        // Run optimization if requested
        if ($optimize) {
            $this->info("\n=== TROOP OPTIMIZATION ===");

            // Get available unit types
            $unitTypes = UnitType::all();
            $availableTroops = [];

            foreach ($unitTypes as $unitType) {
                $availableTroops[$unitType->name] = [
                    'id' => $unitType->id,
                    'name' => $unitType->name,
                    'attack' => $unitType->attack_power,
                    'defense_infantry' => $unitType->defense_power,
                    'defense_cavalry' => $unitType->defense_power,
                    'speed' => $unitType->speed,
                ];
            }

            $this->info("Optimizing troop composition for {$totalTroops} total troops...");

            $optimizationResults = $battleService->optimizeTroopComposition(
                $defenderVillage,
                $availableTroops,
                $totalTroops
            );

            $this->info('Best Win Rate: '.number_format($optimizationResults['win_rate'], 1).'%');
            $this->info('Optimal Composition:');

            foreach ($optimizationResults['composition'] as $troop) {
                $totalPower = $troop['count'] * $troop['attack'];
                $this->line("  - {$troop['unit_type']}: {$troop['count']} (Total Power: {$totalPower})");
            }
        }

        // Get battle recommendations
        $this->info("\n=== BATTLE RECOMMENDATIONS ===");
        $recommendations = $battleService->getBattleRecommendations($defenderVillage);

        if (empty($recommendations)) {
            $this->info('No specific recommendations at this time.');
        } else {
            foreach ($recommendations as $recommendation) {
                $priority = strtoupper($recommendation['priority']);
                $this->line("  [{$priority}] {$recommendation['message']}");
            }
        }

        $this->info("\nBattle simulation test completed successfully!");

        return 0;
    }
}
