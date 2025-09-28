<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\GameOptimizationCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GameOptimizationCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_run_game_optimization()
    {
        $this
            ->artisan('game:optimization')
            ->expectsOutput('=== Game Optimization Report ===')
            ->expectsOutput('Starting game optimization...')
            ->expectsOutput('Database optimization: COMPLETED')
            ->expectsOutput('Cache optimization: COMPLETED')
            ->expectsOutput('Game optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_specific_tasks()
    {
        $this
            ->artisan('game:optimization', ['--tasks' => 'database,cache'])
            ->expectsOutput('=== Game Optimization Report ===')
            ->expectsOutput('Starting game optimization...')
            ->expectsOutput('Running optimization tasks: database, cache')
            ->expectsOutput('Database optimization: COMPLETED')
            ->expectsOutput('Cache optimization: COMPLETED')
            ->expectsOutput('Game optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_verbose_output()
    {
        $this
            ->artisan('game:optimization', ['--verbose' => true])
            ->expectsOutput('=== Game Optimization Report ===')
            ->expectsOutput('Starting game optimization...')
            ->expectsOutput('=== Database Optimization ===')
            ->expectsOutput('Optimizing database tables...')
            ->expectsOutput('Analyzing query performance...')
            ->expectsOutput('=== Cache Optimization ===')
            ->expectsOutput('Optimizing cache storage...')
            ->expectsOutput('Cleaning up expired cache...')
            ->expectsOutput('Game optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_reporting()
    {
        $this
            ->artisan('game:optimization', ['--report' => true])
            ->expectsOutput('=== Game Optimization Report ===')
            ->expectsOutput('Starting game optimization...')
            ->expectsOutput('Generating optimization report...')
            ->expectsOutput('Game optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_export()
    {
        $this
            ->artisan('game:optimization', ['--export' => 'optimization.json'])
            ->expectsOutput('=== Game Optimization Report ===')
            ->expectsOutput('Starting game optimization...')
            ->expectsOutput('Exporting optimization data to: optimization.json')
            ->expectsOutput('Game optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_scheduling()
    {
        $this
            ->artisan('game:optimization', ['--schedule' => 'weekly'])
            ->expectsOutput('=== Game Optimization Report ===')
            ->expectsOutput('Starting game optimization...')
            ->expectsOutput('Scheduling weekly optimization...')
            ->expectsOutput('Game optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_cleanup()
    {
        $this
            ->artisan('game:optimization', ['--cleanup' => true])
            ->expectsOutput('=== Game Optimization Report ===')
            ->expectsOutput('Starting game optimization...')
            ->expectsOutput('Cleaning up optimization data...')
            ->expectsOutput('Game optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_validation()
    {
        $this
            ->artisan('game:optimization', ['--validate' => true])
            ->expectsOutput('=== Game Optimization Report ===')
            ->expectsOutput('Starting game optimization...')
            ->expectsOutput('Validating optimization configuration...')
            ->expectsOutput('Game optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_monitoring()
    {
        $this
            ->artisan('game:optimization', ['--monitor' => true])
            ->expectsOutput('=== Game Optimization Report ===')
            ->expectsOutput('Starting game optimization...')
            ->expectsOutput('Monitoring optimization progress...')
            ->expectsOutput('Game optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_debugging()
    {
        $this
            ->artisan('game:optimization', ['--debug' => true])
            ->expectsOutput('=== Game Optimization Report ===')
            ->expectsOutput('Starting game optimization...')
            ->expectsOutput('Debug mode enabled')
            ->expectsOutput('Game optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_dry_run()
    {
        $this
            ->artisan('game:optimization', ['--dry-run' => true])
            ->expectsOutput('=== Game Optimization Report ===')
            ->expectsOutput('Starting game optimization...')
            ->expectsOutput('Dry run mode enabled')
            ->expectsOutput('Game optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_quiet_mode()
    {
        $this
            ->artisan('game:optimization', ['--quiet' => true])
            ->expectsOutput('=== Game Optimization Report ===')
            ->expectsOutput('Starting game optimization...')
            ->expectsOutput('Quiet mode enabled')
            ->expectsOutput('Game optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_optimization_errors()
    {
        // Mock database error
        DB::shouldReceive('table')->andThrow(new \Exception('Database error'));

        $this
            ->artisan('game:optimization')
            ->expectsOutput('=== Game Optimization Report ===')
            ->expectsOutput('Error during optimization: Database error')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_retry_on_failure()
    {
        $this
            ->artisan('game:optimization', ['--retry' => 3])
            ->expectsOutput('=== Game Optimization Report ===')
            ->expectsOutput('Starting game optimization...')
            ->expectsOutput('Retry attempts set to: 3')
            ->expectsOutput('Game optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_timeout()
    {
        $this
            ->artisan('game:optimization', ['--timeout' => '300'])
            ->expectsOutput('=== Game Optimization Report ===')
            ->expectsOutput('Starting game optimization...')
            ->expectsOutput('Timeout set to: 300 seconds')
            ->expectsOutput('Game optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new GameOptimizationCommand();
        $this->assertEquals('game:optimization', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new GameOptimizationCommand();
        $this->assertEquals('Optimize game performance and database efficiency', $command->getDescription());
    }
}
