<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\GameUpdateCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GameUpdateCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_run_game_update()
    {
        $this
            ->artisan('game:update')
            ->expectsOutput('=== Game Update Report ===')
            ->expectsOutput('Starting game update...')
            ->expectsOutput('Database migration: COMPLETED')
            ->expectsOutput('Cache update: COMPLETED')
            ->expectsOutput('Game update completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_update_with_specific_tasks()
    {
        $this
            ->artisan('game:update', ['--tasks' => 'migration,cache'])
            ->expectsOutput('=== Game Update Report ===')
            ->expectsOutput('Starting game update...')
            ->expectsOutput('Running update tasks: migration, cache')
            ->expectsOutput('Database migration: COMPLETED')
            ->expectsOutput('Cache update: COMPLETED')
            ->expectsOutput('Game update completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_update_with_force()
    {
        $this
            ->artisan('game:update', ['--force' => true])
            ->expectsOutput('=== Game Update Report ===')
            ->expectsOutput('Starting game update...')
            ->expectsOutput('Force mode enabled')
            ->expectsOutput('Game update completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_update_with_verbose_output()
    {
        $this
            ->artisan('game:update', ['--verbose' => true])
            ->expectsOutput('=== Game Update Report ===')
            ->expectsOutput('Starting game update...')
            ->expectsOutput('=== Database Migration ===')
            ->expectsOutput('Running database migrations...')
            ->expectsOutput('Verifying migration status...')
            ->expectsOutput('=== Cache Update ===')
            ->expectsOutput('Updating application cache...')
            ->expectsOutput('Clearing expired cache entries...')
            ->expectsOutput('Game update completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_update_with_reporting()
    {
        $this
            ->artisan('game:update', ['--report' => true])
            ->expectsOutput('=== Game Update Report ===')
            ->expectsOutput('Starting game update...')
            ->expectsOutput('Generating update report...')
            ->expectsOutput('Game update completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_update_with_export()
    {
        $this
            ->artisan('game:update', ['--export' => 'update.json'])
            ->expectsOutput('=== Game Update Report ===')
            ->expectsOutput('Starting game update...')
            ->expectsOutput('Exporting update data to: update.json')
            ->expectsOutput('Game update completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_update_with_scheduling()
    {
        $this
            ->artisan('game:update', ['--schedule' => 'maintenance'])
            ->expectsOutput('=== Game Update Report ===')
            ->expectsOutput('Starting game update...')
            ->expectsOutput('Scheduling maintenance update...')
            ->expectsOutput('Game update completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_update_with_cleanup()
    {
        $this
            ->artisan('game:update', ['--cleanup' => true])
            ->expectsOutput('=== Game Update Report ===')
            ->expectsOutput('Starting game update...')
            ->expectsOutput('Cleaning up update data...')
            ->expectsOutput('Game update completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_update_with_validation()
    {
        $this
            ->artisan('game:update', ['--validate' => true])
            ->expectsOutput('=== Game Update Report ===')
            ->expectsOutput('Starting game update...')
            ->expectsOutput('Validating update configuration...')
            ->expectsOutput('Game update completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_update_with_monitoring()
    {
        $this
            ->artisan('game:update', ['--monitor' => true])
            ->expectsOutput('=== Game Update Report ===')
            ->expectsOutput('Starting game update...')
            ->expectsOutput('Monitoring update progress...')
            ->expectsOutput('Game update completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_update_with_debugging()
    {
        $this
            ->artisan('game:update', ['--debug' => true])
            ->expectsOutput('=== Game Update Report ===')
            ->expectsOutput('Starting game update...')
            ->expectsOutput('Debug mode enabled')
            ->expectsOutput('Game update completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_update_with_dry_run()
    {
        $this
            ->artisan('game:update', ['--dry-run' => true])
            ->expectsOutput('=== Game Update Report ===')
            ->expectsOutput('Starting game update...')
            ->expectsOutput('Dry run mode enabled')
            ->expectsOutput('Game update completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_update_with_quiet_mode()
    {
        $this
            ->artisan('game:update', ['--quiet' => true])
            ->expectsOutput('=== Game Update Report ===')
            ->expectsOutput('Starting game update...')
            ->expectsOutput('Quiet mode enabled')
            ->expectsOutput('Game update completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_update_errors()
    {
        // Mock database error
        DB::shouldReceive('table')->andThrow(new \Exception('Database error'));

        $this
            ->artisan('game:update')
            ->expectsOutput('=== Game Update Report ===')
            ->expectsOutput('Error during update: Database error')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_run_update_with_retry_on_failure()
    {
        $this
            ->artisan('game:update', ['--retry' => 3])
            ->expectsOutput('=== Game Update Report ===')
            ->expectsOutput('Starting game update...')
            ->expectsOutput('Retry attempts set to: 3')
            ->expectsOutput('Game update completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_update_with_timeout()
    {
        $this
            ->artisan('game:update', ['--timeout' => '300'])
            ->expectsOutput('=== Game Update Report ===')
            ->expectsOutput('Starting game update...')
            ->expectsOutput('Timeout set to: 300 seconds')
            ->expectsOutput('Game update completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new GameUpdateCommand();
        $this->assertEquals('game:update', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new GameUpdateCommand();
        $this->assertEquals('Update game system and database schema', $command->getDescription());
    }
}
