<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\AdvancedCombatCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdvancedCombatCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_run_advanced_combat()
    {
        $this
            ->artisan('game:advanced-combat')
            ->expectsOutput('=== Advanced Combat System ===')
            ->expectsOutput('Starting advanced combat simulation...')
            ->expectsOutput('Combat mechanics: COMPLETED')
            ->expectsOutput('Battle calculations: COMPLETED')
            ->expectsOutput('Advanced combat simulation completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_combat_with_specific_modes()
    {
        $this
            ->artisan('game:advanced-combat', ['--modes' => 'pvp,pve'])
            ->expectsOutput('=== Advanced Combat System ===')
            ->expectsOutput('Starting advanced combat simulation...')
            ->expectsOutput('Running combat modes: pvp, pve')
            ->expectsOutput('PvP combat: COMPLETED')
            ->expectsOutput('PvE combat: COMPLETED')
            ->expectsOutput('Advanced combat simulation completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_combat_with_verbose_output()
    {
        $this
            ->artisan('game:advanced-combat', ['--verbose' => true])
            ->expectsOutput('=== Advanced Combat System ===')
            ->expectsOutput('Starting advanced combat simulation...')
            ->expectsOutput('=== Combat Mechanics ===')
            ->expectsOutput('Calculating damage formulas...')
            ->expectsOutput('Processing combat effects...')
            ->expectsOutput('=== Battle Calculations ===')
            ->expectsOutput('Computing battle outcomes...')
            ->expectsOutput('Analyzing combat statistics...')
            ->expectsOutput('Advanced combat simulation completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_combat_with_simulation()
    {
        $this
            ->artisan('game:advanced-combat', ['--simulate' => true])
            ->expectsOutput('=== Advanced Combat System ===')
            ->expectsOutput('Starting advanced combat simulation...')
            ->expectsOutput('Simulation mode enabled')
            ->expectsOutput('Advanced combat simulation completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_combat_with_testing()
    {
        $this
            ->artisan('game:advanced-combat', ['--test' => true])
            ->expectsOutput('=== Advanced Combat System ===')
            ->expectsOutput('Starting advanced combat simulation...')
            ->expectsOutput('Testing mode enabled')
            ->expectsOutput('Advanced combat simulation completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_combat_with_debugging()
    {
        $this
            ->artisan('game:advanced-combat', ['--debug' => true])
            ->expectsOutput('=== Advanced Combat System ===')
            ->expectsOutput('Starting advanced combat simulation...')
            ->expectsOutput('Debug mode enabled')
            ->expectsOutput('Advanced combat simulation completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_combat_errors()
    {
        // Mock database error
        DB::shouldReceive('table')->andThrow(new \Exception('Database error'));

        $this
            ->artisan('game:advanced-combat')
            ->expectsOutput('=== Advanced Combat System ===')
            ->expectsOutput('Error during combat simulation: Database error')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new AdvancedCombatCommand();
        $this->assertEquals('game:advanced-combat', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new AdvancedCombatCommand();
        $this->assertEquals('Run advanced combat system simulation and testing', $command->getDescription());
    }
}
