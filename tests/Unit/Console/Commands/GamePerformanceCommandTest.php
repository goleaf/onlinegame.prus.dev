<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\GamePerformanceCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GamePerformanceCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_run_game_performance()
    {
        $this
            ->artisan('game:performance')
            ->expectsOutput('=== Game Performance Report ===')
            ->expectsOutput('Starting game performance analysis...')
            ->expectsOutput('Database performance: COMPLETED')
            ->expectsOutput('Cache performance: COMPLETED')
            ->expectsOutput('Game performance analysis completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_performance_with_specific_metrics()
    {
        $this
            ->artisan('game:performance', ['--metrics' => 'database,cache'])
            ->expectsOutput('=== Game Performance Report ===')
            ->expectsOutput('Starting game performance analysis...')
            ->expectsOutput('Running performance metrics: database, cache')
            ->expectsOutput('Database performance: COMPLETED')
            ->expectsOutput('Cache performance: COMPLETED')
            ->expectsOutput('Game performance analysis completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_performance_with_verbose_output()
    {
        $this
            ->artisan('game:performance', ['--verbose' => true])
            ->expectsOutput('=== Game Performance Report ===')
            ->expectsOutput('Starting game performance analysis...')
            ->expectsOutput('=== Database Performance ===')
            ->expectsOutput('Analyzing database performance...')
            ->expectsOutput('Checking query execution times...')
            ->expectsOutput('=== Cache Performance ===')
            ->expectsOutput('Analyzing cache performance...')
            ->expectsOutput('Checking cache hit rates...')
            ->expectsOutput('Game performance analysis completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_performance_with_reporting()
    {
        $this
            ->artisan('game:performance', ['--report' => true])
            ->expectsOutput('=== Game Performance Report ===')
            ->expectsOutput('Starting game performance analysis...')
            ->expectsOutput('Generating performance report...')
            ->expectsOutput('Game performance analysis completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_performance_with_export()
    {
        $this
            ->artisan('game:performance', ['--export' => 'performance.json'])
            ->expectsOutput('=== Game Performance Report ===')
            ->expectsOutput('Starting game performance analysis...')
            ->expectsOutput('Exporting performance data to: performance.json')
            ->expectsOutput('Game performance analysis completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_performance_with_scheduling()
    {
        $this
            ->artisan('game:performance', ['--schedule' => 'hourly'])
            ->expectsOutput('=== Game Performance Report ===')
            ->expectsOutput('Starting game performance analysis...')
            ->expectsOutput('Scheduling hourly performance analysis...')
            ->expectsOutput('Game performance analysis completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_performance_with_cleanup()
    {
        $this
            ->artisan('game:performance', ['--cleanup' => true])
            ->expectsOutput('=== Game Performance Report ===')
            ->expectsOutput('Starting game performance analysis...')
            ->expectsOutput('Cleaning up performance data...')
            ->expectsOutput('Game performance analysis completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_performance_with_validation()
    {
        $this
            ->artisan('game:performance', ['--validate' => true])
            ->expectsOutput('=== Game Performance Report ===')
            ->expectsOutput('Starting game performance analysis...')
            ->expectsOutput('Validating performance configuration...')
            ->expectsOutput('Game performance analysis completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_performance_with_monitoring()
    {
        $this
            ->artisan('game:performance', ['--monitor' => true])
            ->expectsOutput('=== Game Performance Report ===')
            ->expectsOutput('Starting game performance analysis...')
            ->expectsOutput('Monitoring performance analysis progress...')
            ->expectsOutput('Game performance analysis completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_performance_with_debugging()
    {
        $this
            ->artisan('game:performance', ['--debug' => true])
            ->expectsOutput('=== Game Performance Report ===')
            ->expectsOutput('Starting game performance analysis...')
            ->expectsOutput('Debug mode enabled')
            ->expectsOutput('Game performance analysis completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_performance_with_dry_run()
    {
        $this
            ->artisan('game:performance', ['--dry-run' => true])
            ->expectsOutput('=== Game Performance Report ===')
            ->expectsOutput('Starting game performance analysis...')
            ->expectsOutput('Dry run mode enabled')
            ->expectsOutput('Game performance analysis completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_performance_with_quiet_mode()
    {
        $this
            ->artisan('game:performance', ['--quiet' => true])
            ->expectsOutput('=== Game Performance Report ===')
            ->expectsOutput('Starting game performance analysis...')
            ->expectsOutput('Quiet mode enabled')
            ->expectsOutput('Game performance analysis completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_performance_errors()
    {
        // Mock database error
        DB::shouldReceive('table')->andThrow(new \Exception('Database error'));

        $this
            ->artisan('game:performance')
            ->expectsOutput('=== Game Performance Report ===')
            ->expectsOutput('Error during performance analysis: Database error')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_run_performance_with_retry_on_failure()
    {
        $this
            ->artisan('game:performance', ['--retry' => 3])
            ->expectsOutput('=== Game Performance Report ===')
            ->expectsOutput('Starting game performance analysis...')
            ->expectsOutput('Retry attempts set to: 3')
            ->expectsOutput('Game performance analysis completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_performance_with_timeout()
    {
        $this
            ->artisan('game:performance', ['--timeout' => '300'])
            ->expectsOutput('=== Game Performance Report ===')
            ->expectsOutput('Starting game performance analysis...')
            ->expectsOutput('Timeout set to: 300 seconds')
            ->expectsOutput('Game performance analysis completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new GamePerformanceCommand();
        $this->assertEquals('game:performance', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new GamePerformanceCommand();
        $this->assertEquals('Analyze and monitor game performance metrics', $command->getDescription());
    }
}
