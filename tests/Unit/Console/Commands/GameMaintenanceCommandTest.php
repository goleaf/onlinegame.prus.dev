<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\GameMaintenanceCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GameMaintenanceCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_run_game_maintenance()
    {
        $this
            ->artisan('game:maintenance')
            ->expectsOutput('=== Game Maintenance Report ===')
            ->expectsOutput('Starting game maintenance...')
            ->expectsOutput('Database maintenance: COMPLETED')
            ->expectsOutput('Cache maintenance: COMPLETED')
            ->expectsOutput('File maintenance: COMPLETED')
            ->expectsOutput('Game maintenance completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_maintenance_with_specific_tasks()
    {
        $this
            ->artisan('game:maintenance', ['--tasks' => 'database,cache'])
            ->expectsOutput('=== Game Maintenance Report ===')
            ->expectsOutput('Running maintenance tasks: database, cache')
            ->expectsOutput('Database maintenance: COMPLETED')
            ->expectsOutput('Cache maintenance: COMPLETED')
            ->expectsOutput('Game maintenance completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_maintenance_with_force()
    {
        $this
            ->artisan('game:maintenance', ['--force' => true])
            ->expectsOutput('=== Game Maintenance Report ===')
            ->expectsOutput('Starting game maintenance...')
            ->expectsOutput('Force mode enabled')
            ->expectsOutput('Game maintenance completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_maintenance_with_verbose_output()
    {
        $this
            ->artisan('game:maintenance', ['--verbose' => true])
            ->expectsOutput('=== Game Maintenance Report ===')
            ->expectsOutput('Starting game maintenance...')
            ->expectsOutput('=== Database Maintenance ===')
            ->expectsOutput('Optimizing database tables...')
            ->expectsOutput('Cleaning up old records...')
            ->expectsOutput('=== Cache Maintenance ===')
            ->expectsOutput('Clearing expired cache entries...')
            ->expectsOutput('Optimizing cache storage...')
            ->expectsOutput('=== File Maintenance ===')
            ->expectsOutput('Cleaning up temporary files...')
            ->expectsOutput('Optimizing file storage...')
            ->expectsOutput('Game maintenance completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_maintenance_with_reporting()
    {
        $this
            ->artisan('game:maintenance', ['--report' => true])
            ->expectsOutput('=== Game Maintenance Report ===')
            ->expectsOutput('Starting game maintenance...')
            ->expectsOutput('Generating maintenance report...')
            ->expectsOutput('Game maintenance completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_maintenance_with_export()
    {
        $this
            ->artisan('game:maintenance', ['--export' => 'maintenance.json'])
            ->expectsOutput('=== Game Maintenance Report ===')
            ->expectsOutput('Starting game maintenance...')
            ->expectsOutput('Exporting maintenance data to: maintenance.json')
            ->expectsOutput('Game maintenance completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_maintenance_with_scheduling()
    {
        $this
            ->artisan('game:maintenance', ['--schedule' => 'daily'])
            ->expectsOutput('=== Game Maintenance Report ===')
            ->expectsOutput('Starting game maintenance...')
            ->expectsOutput('Scheduling daily maintenance...')
            ->expectsOutput('Game maintenance completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_maintenance_with_cleanup()
    {
        $this
            ->artisan('game:maintenance', ['--cleanup' => true])
            ->expectsOutput('=== Game Maintenance Report ===')
            ->expectsOutput('Starting game maintenance...')
            ->expectsOutput('Cleaning up maintenance data...')
            ->expectsOutput('Game maintenance completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_maintenance_with_validation()
    {
        $this
            ->artisan('game:maintenance', ['--validate' => true])
            ->expectsOutput('=== Game Maintenance Report ===')
            ->expectsOutput('Starting game maintenance...')
            ->expectsOutput('Validating maintenance configuration...')
            ->expectsOutput('Game maintenance completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_maintenance_with_monitoring()
    {
        $this
            ->artisan('game:maintenance', ['--monitor' => true])
            ->expectsOutput('=== Game Maintenance Report ===')
            ->expectsOutput('Starting game maintenance...')
            ->expectsOutput('Monitoring maintenance progress...')
            ->expectsOutput('Game maintenance completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_maintenance_with_debugging()
    {
        $this
            ->artisan('game:maintenance', ['--debug' => true])
            ->expectsOutput('=== Game Maintenance Report ===')
            ->expectsOutput('Starting game maintenance...')
            ->expectsOutput('Debug mode enabled')
            ->expectsOutput('Game maintenance completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_maintenance_with_dry_run()
    {
        $this
            ->artisan('game:maintenance', ['--dry-run' => true])
            ->expectsOutput('=== Game Maintenance Report ===')
            ->expectsOutput('Starting game maintenance...')
            ->expectsOutput('Dry run mode enabled')
            ->expectsOutput('Game maintenance completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_maintenance_with_quiet_mode()
    {
        $this
            ->artisan('game:maintenance', ['--quiet' => true])
            ->expectsOutput('=== Game Maintenance Report ===')
            ->expectsOutput('Starting game maintenance...')
            ->expectsOutput('Quiet mode enabled')
            ->expectsOutput('Game maintenance completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_maintenance_errors()
    {
        // Mock database error
        DB::shouldReceive('table')->andThrow(new \Exception('Database error'));

        $this
            ->artisan('game:maintenance')
            ->expectsOutput('=== Game Maintenance Report ===')
            ->expectsOutput('Error during maintenance: Database error')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_run_maintenance_with_retry_on_failure()
    {
        $this
            ->artisan('game:maintenance', ['--retry' => 3])
            ->expectsOutput('=== Game Maintenance Report ===')
            ->expectsOutput('Starting game maintenance...')
            ->expectsOutput('Retry attempts set to: 3')
            ->expectsOutput('Game maintenance completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_maintenance_with_timeout()
    {
        $this
            ->artisan('game:maintenance', ['--timeout' => '300'])
            ->expectsOutput('=== Game Maintenance Report ===')
            ->expectsOutput('Starting game maintenance...')
            ->expectsOutput('Timeout set to: 300 seconds')
            ->expectsOutput('Game maintenance completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new GameMaintenanceCommand();
        $this->assertEquals('game:maintenance', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new GameMaintenanceCommand();
        $this->assertEquals('Perform system maintenance and optimization', $command->getDescription());
    }
}
