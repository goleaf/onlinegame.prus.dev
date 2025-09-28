<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\GameMonitoringCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GameMonitoringCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_run_game_monitoring()
    {
        $this
            ->artisan('game:monitoring')
            ->expectsOutput('=== Game Monitoring Report ===')
            ->expectsOutput('Starting game monitoring...')
            ->expectsOutput('System health check: COMPLETED')
            ->expectsOutput('Performance monitoring: COMPLETED')
            ->expectsOutput('Game monitoring completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_monitoring_with_specific_checks()
    {
        $this
            ->artisan('game:monitoring', ['--checks' => 'health,performance'])
            ->expectsOutput('=== Game Monitoring Report ===')
            ->expectsOutput('Starting game monitoring...')
            ->expectsOutput('Running monitoring checks: health, performance')
            ->expectsOutput('System health check: COMPLETED')
            ->expectsOutput('Performance monitoring: COMPLETED')
            ->expectsOutput('Game monitoring completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_monitoring_with_verbose_output()
    {
        $this
            ->artisan('game:monitoring', ['--verbose' => true])
            ->expectsOutput('=== Game Monitoring Report ===')
            ->expectsOutput('Starting game monitoring...')
            ->expectsOutput('=== System Health Check ===')
            ->expectsOutput('Checking system health...')
            ->expectsOutput('Verifying database connections...')
            ->expectsOutput('=== Performance Monitoring ===')
            ->expectsOutput('Monitoring system performance...')
            ->expectsOutput('Checking resource usage...')
            ->expectsOutput('Game monitoring completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_monitoring_with_reporting()
    {
        $this
            ->artisan('game:monitoring', ['--report' => true])
            ->expectsOutput('=== Game Monitoring Report ===')
            ->expectsOutput('Starting game monitoring...')
            ->expectsOutput('Generating monitoring report...')
            ->expectsOutput('Game monitoring completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_monitoring_with_export()
    {
        $this
            ->artisan('game:monitoring', ['--export' => 'monitoring.json'])
            ->expectsOutput('=== Game Monitoring Report ===')
            ->expectsOutput('Starting game monitoring...')
            ->expectsOutput('Exporting monitoring data to: monitoring.json')
            ->expectsOutput('Game monitoring completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_monitoring_with_scheduling()
    {
        $this
            ->artisan('game:monitoring', ['--schedule' => 'hourly'])
            ->expectsOutput('=== Game Monitoring Report ===')
            ->expectsOutput('Starting game monitoring...')
            ->expectsOutput('Scheduling hourly monitoring...')
            ->expectsOutput('Game monitoring completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_monitoring_with_cleanup()
    {
        $this
            ->artisan('game:monitoring', ['--cleanup' => true])
            ->expectsOutput('=== Game Monitoring Report ===')
            ->expectsOutput('Starting game monitoring...')
            ->expectsOutput('Cleaning up monitoring data...')
            ->expectsOutput('Game monitoring completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_monitoring_with_validation()
    {
        $this
            ->artisan('game:monitoring', ['--validate' => true])
            ->expectsOutput('=== Game Monitoring Report ===')
            ->expectsOutput('Starting game monitoring...')
            ->expectsOutput('Validating monitoring configuration...')
            ->expectsOutput('Game monitoring completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_monitoring_with_alerts()
    {
        $this
            ->artisan('game:monitoring', ['--alerts' => true])
            ->expectsOutput('=== Game Monitoring Report ===')
            ->expectsOutput('Starting game monitoring...')
            ->expectsOutput('Alert system enabled')
            ->expectsOutput('Game monitoring completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_monitoring_with_debugging()
    {
        $this
            ->artisan('game:monitoring', ['--debug' => true])
            ->expectsOutput('=== Game Monitoring Report ===')
            ->expectsOutput('Starting game monitoring...')
            ->expectsOutput('Debug mode enabled')
            ->expectsOutput('Game monitoring completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_monitoring_with_dry_run()
    {
        $this
            ->artisan('game:monitoring', ['--dry-run' => true])
            ->expectsOutput('=== Game Monitoring Report ===')
            ->expectsOutput('Starting game monitoring...')
            ->expectsOutput('Dry run mode enabled')
            ->expectsOutput('Game monitoring completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_monitoring_with_quiet_mode()
    {
        $this
            ->artisan('game:monitoring', ['--quiet' => true])
            ->expectsOutput('=== Game Monitoring Report ===')
            ->expectsOutput('Starting game monitoring...')
            ->expectsOutput('Quiet mode enabled')
            ->expectsOutput('Game monitoring completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_monitoring_errors()
    {
        // Mock database error
        DB::shouldReceive('table')->andThrow(new \Exception('Database error'));

        $this
            ->artisan('game:monitoring')
            ->expectsOutput('=== Game Monitoring Report ===')
            ->expectsOutput('Error during monitoring: Database error')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_run_monitoring_with_retry_on_failure()
    {
        $this
            ->artisan('game:monitoring', ['--retry' => 3])
            ->expectsOutput('=== Game Monitoring Report ===')
            ->expectsOutput('Starting game monitoring...')
            ->expectsOutput('Retry attempts set to: 3')
            ->expectsOutput('Game monitoring completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_monitoring_with_timeout()
    {
        $this
            ->artisan('game:monitoring', ['--timeout' => '300'])
            ->expectsOutput('=== Game Monitoring Report ===')
            ->expectsOutput('Starting game monitoring...')
            ->expectsOutput('Timeout set to: 300 seconds')
            ->expectsOutput('Game monitoring completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new GameMonitoringCommand();
        $this->assertEquals('game:monitoring', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new GameMonitoringCommand();
        $this->assertEquals('Monitor system health and performance metrics', $command->getDescription());
    }
}
