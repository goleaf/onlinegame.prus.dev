<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\FinalOptimizationCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FinalOptimizationCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_run_final_optimization()
    {
        $this
            ->artisan('optimize:final')
            ->expectsOutput('Starting final optimization process...')
            ->expectsOutput('=== Final Optimization Report ===')
            ->expectsOutput('Cache optimization: COMPLETED')
            ->expectsOutput('Database optimization: COMPLETED')
            ->expectsOutput('Asset optimization: COMPLETED')
            ->expectsOutput('Performance optimization: COMPLETED')
            ->expectsOutput('Final optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_force_flag()
    {
        $this
            ->artisan('optimize:final', ['--force' => true])
            ->expectsOutput('Starting final optimization process...')
            ->expectsOutput('Force mode enabled - all optimizations will be applied')
            ->expectsOutput('=== Final Optimization Report ===')
            ->expectsOutput('Final optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_dry_run()
    {
        $this
            ->artisan('optimize:final', ['--dry-run' => true])
            ->expectsOutput('Starting final optimization process...')
            ->expectsOutput('DRY RUN MODE - No optimizations will be applied')
            ->expectsOutput('=== Final Optimization Report (Dry Run) ===')
            ->expectsOutput('Would optimize cache: YES')
            ->expectsOutput('Would optimize database: YES')
            ->expectsOutput('Would optimize assets: YES')
            ->expectsOutput('Would optimize performance: YES')
            ->expectsOutput('Dry run completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_specific_modules()
    {
        $this
            ->artisan('optimize:final', ['--modules' => 'cache,database'])
            ->expectsOutput('Starting final optimization process...')
            ->expectsOutput('Optimizing modules: cache, database')
            ->expectsOutput('=== Final Optimization Report ===')
            ->expectsOutput('Cache optimization: COMPLETED')
            ->expectsOutput('Database optimization: COMPLETED')
            ->expectsOutput('Final optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_verbose_output()
    {
        $this
            ->artisan('optimize:final', ['--verbose' => true])
            ->expectsOutput('Starting final optimization process...')
            ->expectsOutput('Clearing application cache...')
            ->expectsOutput('Optimizing route cache...')
            ->expectsOutput('Optimizing config cache...')
            ->expectsOutput('Optimizing view cache...')
            ->expectsOutput('Running database optimizations...')
            ->expectsOutput('Optimizing database tables...')
            ->expectsOutput('Analyzing database performance...')
            ->expectsOutput('Compiling assets...')
            ->expectsOutput('Minifying CSS and JavaScript...')
            ->expectsOutput('Generating optimized assets...')
            ->expectsOutput('Running performance optimizations...')
            ->expectsOutput('Optimizing autoloader...')
            ->expectsOutput('Clearing temporary files...')
            ->expectsOutput('=== Final Optimization Report ===')
            ->expectsOutput('Final optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_backup()
    {
        $this
            ->artisan('optimize:final', ['--backup' => true])
            ->expectsOutput('Starting final optimization process...')
            ->expectsOutput('Creating backup before optimization...')
            ->expectsOutput('Backup created successfully')
            ->expectsOutput('=== Final Optimization Report ===')
            ->expectsOutput('Final optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_rollback_option()
    {
        $this
            ->artisan('optimize:final', ['--rollback' => true])
            ->expectsOutput('Starting final optimization process...')
            ->expectsOutput('Rollback mode enabled - reverting to previous state')
            ->expectsOutput('=== Final Optimization Report ===')
            ->expectsOutput('Rollback completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_maintenance_mode()
    {
        $this
            ->artisan('optimize:final', ['--maintenance' => true])
            ->expectsOutput('Starting final optimization process...')
            ->expectsOutput('Enabling maintenance mode...')
            ->expectsOutput('Maintenance mode enabled')
            ->expectsOutput('=== Final Optimization Report ===')
            ->expectsOutput('Disabling maintenance mode...')
            ->expectsOutput('Maintenance mode disabled')
            ->expectsOutput('Final optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_optimization_errors_gracefully()
    {
        // Mock a failure in cache optimization
        Cache::shouldReceive('flush')->andThrow(new \Exception('Cache flush failed'));

        $this
            ->artisan('optimize:final')
            ->expectsOutput('Starting final optimization process...')
            ->expectsOutput('Error during optimization: Cache flush failed')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_parallel_processing()
    {
        $this
            ->artisan('optimize:final', ['--parallel' => true])
            ->expectsOutput('Starting final optimization process...')
            ->expectsOutput('Parallel processing enabled')
            ->expectsOutput('=== Final Optimization Report ===')
            ->expectsOutput('Final optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_optimization_with_memory_limit()
    {
        $this
            ->artisan('optimize:final', ['--memory-limit' => '512M'])
            ->expectsOutput('Starting final optimization process...')
            ->expectsOutput('Memory limit set to: 512M')
            ->expectsOutput('=== Final Optimization Report ===')
            ->expectsOutput('Final optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_show_optimization_statistics()
    {
        $this
            ->artisan('optimize:final', ['--stats' => true])
            ->expectsOutput('Starting final optimization process...')
            ->expectsOutput('=== Final Optimization Report ===')
            ->expectsOutput('=== Optimization Statistics ===')
            ->expectsOutput('Cache size before: ')
            ->expectsOutput('Cache size after: ')
            ->expectsOutput('Database queries optimized: ')
            ->expectsOutput('Assets compressed: ')
            ->expectsOutput('Performance improvement: ')
            ->expectsOutput('Final optimization completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new FinalOptimizationCommand();
        $this->assertEquals('optimize:final', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new FinalOptimizationCommand();
        $this->assertEquals('Run final optimization for production deployment', $command->getDescription());
    }
}
