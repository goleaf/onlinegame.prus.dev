<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\BattleSimulationTest;
use App\Models\Game\Village;
use App\Services\BattleSimulationService;
use App\Services\DefenseCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BattleSimulationTestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_run_battle_simulation_with_default_options()
    {
        $attackerVillage = Village::factory()->create(['name' => 'Attacker Village']);
        $defenderVillage = Village::factory()->create(['name' => 'Defender Village']);

        $this->mock(BattleSimulationService::class, function ($mock): void {
            $mock->shouldReceive('simulateBattle')->andReturn([
                'attacker_win_rate' => 65.5,
                'defender_win_rate' => 34.5,
                'draw_rate' => 0.0,
                'attacker_wins' => 655,
                'defender_wins' => 345,
                'draws' => 0,
                'battle_power_stats' => [
                    'attacker_avg' => 1500,
                    'defender_avg' => 1200,
                    'attacker_min' => 1000,
                    'attacker_max' => 2000,
                    'defender_min' => 800,
                    'defender_max' => 1600,
                ],
                'defensive_bonus' => 0.15,
                'attacker_avg_losses' => ['infantry' => 10, 'archer' => 5],
                'defender_avg_losses' => ['infantry' => 15, 'archer' => 8],
                'avg_resources_looted' => ['wood' => 1000, 'clay' => 500],
            ]);
        });

        $this->mock(DefenseCalculationService::class, function ($mock): void {
            $mock->shouldReceive('getDefenseReport')->andReturn([
                'defensive_bonus' => 0.15,
                'spy_defense' => 25,
            ]);
        });

        $this
            ->artisan('battle:simulate', [
                '--attacker-village' => $attackerVillage->id,
                '--defender-village' => $defenderVillage->id,
            ])
            ->expectsOutput('Starting battle simulation test...')
            ->expectsOutput("Attacker Village ID: {$attackerVillage->id}")
            ->expectsOutput("Defender Village ID: {$defenderVillage->id}")
            ->expectsOutput('Iterations: 1000')
            ->expectsOutput("Attacker Village: {$attackerVillage->name}")
            ->expectsOutput("Defender Village: {$defenderVillage->name}")
            ->expectsOutput('Defender Defensive Bonus: 15.0%')
            ->expectsOutput('Defender Spy Defense: 25%')
            ->expectsOutput('=== BATTLE SIMULATION RESULTS ===')
            ->expectsOutput('Win Rates:')
            ->expectsOutput('  Attacker Wins: 65.5% (655 battles)')
            ->expectsOutput('  Defender Wins: 34.5% (345 battles)')
            ->expectsOutput('  Draws: 0.0% (0 battles)')
            ->expectsOutput('Battle Power Statistics:')
            ->expectsOutput('  Attacker Average: 1,500')
            ->expectsOutput('  Defender Average: 1,200')
            ->expectsOutput('  Attacker Range: 1,000 - 2,000')
            ->expectsOutput('  Defender Range: 800 - 1,600')
            ->expectsOutput('Defensive Bonus: 15.0%')
            ->expectsOutput('Attacker Average Losses:')
            ->expectsOutput('  - infantry: 10')
            ->expectsOutput('  - archer: 5')
            ->expectsOutput('Defender Average Losses:')
            ->expectsOutput('  - infantry: 15')
            ->expectsOutput('  - archer: 8')
            ->expectsOutput('Average Resources Looted:')
            ->expectsOutput('  - wood: 1,000')
            ->expectsOutput('  - clay: 500')
            ->expectsOutput('=== BATTLE RECOMMENDATIONS ===')
            ->expectsOutput('No specific recommendations at this time.')
            ->expectsOutput('Battle simulation test completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_battle_simulation_with_custom_iterations()
    {
        $attackerVillage = Village::factory()->create(['name' => 'Attacker Village']);
        $defenderVillage = Village::factory()->create(['name' => 'Defender Village']);

        $this->mock(BattleSimulationService::class, function ($mock): void {
            $mock->shouldReceive('simulateBattle')->andReturn([
                'attacker_win_rate' => 70.0,
                'defender_win_rate' => 30.0,
                'draw_rate' => 0.0,
                'attacker_wins' => 700,
                'defender_wins' => 300,
                'draws' => 0,
                'battle_power_stats' => [
                    'attacker_avg' => 1600,
                    'defender_avg' => 1100,
                    'attacker_min' => 1200,
                    'attacker_max' => 2000,
                    'defender_min' => 900,
                    'defender_max' => 1300,
                ],
                'defensive_bonus' => 0.2,
                'attacker_avg_losses' => ['infantry' => 8, 'archer' => 4],
                'defender_avg_losses' => ['infantry' => 12, 'archer' => 6],
                'avg_resources_looted' => ['wood' => 1200, 'clay' => 600],
            ]);
        });

        $this->mock(DefenseCalculationService::class, function ($mock): void {
            $mock->shouldReceive('getDefenseReport')->andReturn([
                'defensive_bonus' => 0.2,
                'spy_defense' => 30,
            ]);
        });

        $this
            ->artisan('battle:simulate', [
                '--attacker-village' => $attackerVillage->id,
                '--defender-village' => $defenderVillage->id,
                '--iterations' => 2000,
            ])
            ->expectsOutput('Starting battle simulation test...')
            ->expectsOutput("Attacker Village ID: {$attackerVillage->id}")
            ->expectsOutput("Defender Village ID: {$defenderVillage->id}")
            ->expectsOutput('Iterations: 2000')
            ->expectsOutput("Attacker Village: {$attackerVillage->name}")
            ->expectsOutput("Defender Village: {$defenderVillage->name}")
            ->expectsOutput('Defender Defensive Bonus: 20.0%')
            ->expectsOutput('Defender Spy Defense: 30%')
            ->expectsOutput('=== BATTLE SIMULATION RESULTS ===')
            ->expectsOutput('Win Rates:')
            ->expectsOutput('  Attacker Wins: 70.0% (700 battles)')
            ->expectsOutput('  Defender Wins: 30.0% (300 battles)')
            ->expectsOutput('  Draws: 0.0% (0 battles)')
            ->expectsOutput('Battle Power Statistics:')
            ->expectsOutput('  Attacker Average: 1,600')
            ->expectsOutput('  Defender Average: 1,100')
            ->expectsOutput('  Attacker Range: 1,200 - 2,000')
            ->expectsOutput('  Defender Range: 900 - 1,300')
            ->expectsOutput('Defensive Bonus: 20.0%')
            ->expectsOutput('Attacker Average Losses:')
            ->expectsOutput('  - infantry: 8')
            ->expectsOutput('  - archer: 4')
            ->expectsOutput('Defender Average Losses:')
            ->expectsOutput('  - infantry: 12')
            ->expectsOutput('  - archer: 6')
            ->expectsOutput('Average Resources Looted:')
            ->expectsOutput('  - wood: 1,200')
            ->expectsOutput('  - clay: 600')
            ->expectsOutput('=== BATTLE RECOMMENDATIONS ===')
            ->expectsOutput('No specific recommendations at this time.')
            ->expectsOutput('Battle simulation test completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_battle_simulation_with_optimization()
    {
        $attackerVillage = Village::factory()->create(['name' => 'Attacker Village']);
        $defenderVillage = Village::factory()->create(['name' => 'Defender Village']);

        $this->mock(BattleSimulationService::class, function ($mock): void {
            $mock->shouldReceive('simulateBattle')->andReturn([
                'attacker_win_rate' => 65.5,
                'defender_win_rate' => 34.5,
                'draw_rate' => 0.0,
                'attacker_wins' => 655,
                'defender_wins' => 345,
                'draws' => 0,
                'battle_power_stats' => [
                    'attacker_avg' => 1500,
                    'defender_avg' => 1200,
                    'attacker_min' => 1000,
                    'attacker_max' => 2000,
                    'defender_min' => 800,
                    'defender_max' => 1600,
                ],
                'defensive_bonus' => 0.15,
                'attacker_avg_losses' => ['infantry' => 10, 'archer' => 5],
                'defender_avg_losses' => ['infantry' => 15, 'archer' => 8],
                'avg_resources_looted' => ['wood' => 1000, 'clay' => 500],
            ]);

            $mock->shouldReceive('optimizeTroopComposition')->andReturn([
                'win_rate' => 75.0,
                'composition' => [
                    ['unit_type' => 'infantry', 'count' => 50, 'attack' => 10],
                    ['unit_type' => 'archer', 'count' => 30, 'attack' => 15],
                    ['unit_type' => 'cavalry', 'count' => 20, 'attack' => 25],
                ],
            ]);

            $mock->shouldReceive('getBattleRecommendations')->andReturn([
                ['priority' => 'high', 'message' => 'Increase infantry count'],
                ['priority' => 'medium', 'message' => 'Consider adding siege weapons'],
            ]);
        });

        $this->mock(DefenseCalculationService::class, function ($mock): void {
            $mock->shouldReceive('getDefenseReport')->andReturn([
                'defensive_bonus' => 0.15,
                'spy_defense' => 25,
            ]);
        });

        $this
            ->artisan('battle:simulate', [
                '--attacker-village' => $attackerVillage->id,
                '--defender-village' => $defenderVillage->id,
                '--optimize' => true,
                '--total-troops' => 100,
            ])
            ->expectsOutput('Starting battle simulation test...')
            ->expectsOutput("Attacker Village ID: {$attackerVillage->id}")
            ->expectsOutput("Defender Village ID: {$defenderVillage->id}")
            ->expectsOutput('Iterations: 1000')
            ->expectsOutput("Attacker Village: {$attackerVillage->name}")
            ->expectsOutput("Defender Village: {$defenderVillage->name}")
            ->expectsOutput('Defender Defensive Bonus: 15.0%')
            ->expectsOutput('Defender Spy Defense: 25%')
            ->expectsOutput('=== BATTLE SIMULATION RESULTS ===')
            ->expectsOutput('Win Rates:')
            ->expectsOutput('  Attacker Wins: 65.5% (655 battles)')
            ->expectsOutput('  Defender Wins: 34.5% (345 battles)')
            ->expectsOutput('  Draws: 0.0% (0 battles)')
            ->expectsOutput('Battle Power Statistics:')
            ->expectsOutput('  Attacker Average: 1,500')
            ->expectsOutput('  Defender Average: 1,200')
            ->expectsOutput('  Attacker Range: 1,000 - 2,000')
            ->expectsOutput('  Defender Range: 800 - 1,600')
            ->expectsOutput('Defensive Bonus: 15.0%')
            ->expectsOutput('Attacker Average Losses:')
            ->expectsOutput('  - infantry: 10')
            ->expectsOutput('  - archer: 5')
            ->expectsOutput('Defender Average Losses:')
            ->expectsOutput('  - infantry: 15')
            ->expectsOutput('  - archer: 8')
            ->expectsOutput('Average Resources Looted:')
            ->expectsOutput('  - wood: 1,000')
            ->expectsOutput('  - clay: 500')
            ->expectsOutput('=== TROOP OPTIMIZATION ===')
            ->expectsOutput('Optimizing troop composition for 100 total troops...')
            ->expectsOutput('Best Win Rate: 75.0%')
            ->expectsOutput('Optimal Composition:')
            ->expectsOutput('  - infantry: 50 (Total Power: 500)')
            ->expectsOutput('  - archer: 30 (Total Power: 450)')
            ->expectsOutput('  - cavalry: 20 (Total Power: 500)')
            ->expectsOutput('=== BATTLE RECOMMENDATIONS ===')
            ->expectsOutput('  [HIGH] Increase infantry count')
            ->expectsOutput('  [MEDIUM] Consider adding siege weapons')
            ->expectsOutput('Battle simulation test completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_missing_attacker_village()
    {
        $this
            ->artisan('battle:simulate', [
                '--attacker-village' => 999,
                '--defender-village' => 1,
            ])
            ->expectsOutput('Starting battle simulation test...')
            ->expectsOutput('Attacker Village ID: 999')
            ->expectsOutput('Defender Village ID: 1')
            ->expectsOutput('Iterations: 1000')
            ->expectsOutput('Attacker village not found: 999')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_missing_defender_village()
    {
        $attackerVillage = Village::factory()->create(['name' => 'Attacker Village']);

        $this
            ->artisan('battle:simulate', [
                '--attacker-village' => $attackerVillage->id,
                '--defender-village' => 999,
            ])
            ->expectsOutput('Starting battle simulation test...')
            ->expectsOutput("Attacker Village ID: {$attackerVillage->id}")
            ->expectsOutput('Defender Village ID: 999')
            ->expectsOutput('Iterations: 1000')
            ->expectsOutput("Attacker Village: {$attackerVillage->name}")
            ->expectsOutput('Defender village not found: 999')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_no_attacking_troops()
    {
        $attackerVillage = Village::factory()->create(['name' => 'Attacker Village']);
        $defenderVillage = Village::factory()->create(['name' => 'Defender Village']);

        $this->mock(DefenseCalculationService::class, function ($mock): void {
            $mock->shouldReceive('getDefenseReport')->andReturn([
                'defensive_bonus' => 0.15,
                'spy_defense' => 25,
            ]);
        });

        $this
            ->artisan('battle:simulate', [
                '--attacker-village' => $attackerVillage->id,
                '--defender-village' => $defenderVillage->id,
            ])
            ->expectsOutput('Starting battle simulation test...')
            ->expectsOutput("Attacker Village ID: {$attackerVillage->id}")
            ->expectsOutput("Defender Village ID: {$defenderVillage->id}")
            ->expectsOutput('Iterations: 1000')
            ->expectsOutput("Attacker Village: {$attackerVillage->name}")
            ->expectsOutput("Defender Village: {$defenderVillage->name}")
            ->expectsOutput('Defender Defensive Bonus: 15.0%')
            ->expectsOutput('Defender Spy Defense: 25%')
            ->expectsOutput("No attacking troops found in village {$attackerVillage->name}")
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_no_defending_troops()
    {
        $attackerVillage = Village::factory()->create(['name' => 'Attacker Village']);
        $defenderVillage = Village::factory()->create(['name' => 'Defender Village']);

        $this->mock(DefenseCalculationService::class, function ($mock): void {
            $mock->shouldReceive('getDefenseReport')->andReturn([
                'defensive_bonus' => 0.15,
                'spy_defense' => 25,
            ]);
        });

        $this
            ->artisan('battle:simulate', [
                '--attacker-village' => $attackerVillage->id,
                '--defender-village' => $defenderVillage->id,
            ])
            ->expectsOutput('Starting battle simulation test...')
            ->expectsOutput("Attacker Village ID: {$attackerVillage->id}")
            ->expectsOutput("Defender Village ID: {$defenderVillage->id}")
            ->expectsOutput('Iterations: 1000')
            ->expectsOutput("Attacker Village: {$attackerVillage->name}")
            ->expectsOutput("Defender Village: {$defenderVillage->name}")
            ->expectsOutput('Defender Defensive Bonus: 15.0%')
            ->expectsOutput('Defender Spy Defense: 25%')
            ->expectsOutput("No defending troops found in village {$defenderVillage->name}")
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new BattleSimulationTest();
        $this->assertEquals('battle:simulate', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new BattleSimulationTest();
        $this->assertEquals('Test battle simulation with real village data', $command->getDescription());
    }
}
