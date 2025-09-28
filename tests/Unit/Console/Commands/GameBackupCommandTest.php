<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\GameBackupCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GameBackupCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_run_game_backup()
    {
        $this
            ->artisan('game:backup')
            ->expectsOutput('=== Game Backup Report ===')
            ->expectsOutput('Starting game backup...')
            ->expectsOutput('Database backup: COMPLETED')
            ->expectsOutput('File backup: COMPLETED')
            ->expectsOutput('Game backup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_backup_with_specific_tables()
    {
        $this
            ->artisan('game:backup', ['--tables' => 'users,players,villages'])
            ->expectsOutput('=== Game Backup Report ===')
            ->expectsOutput('Starting game backup...')
            ->expectsOutput('Backing up specific tables: users, players, villages')
            ->expectsOutput('Database backup: COMPLETED')
            ->expectsOutput('Game backup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_backup_with_compression()
    {
        $this
            ->artisan('game:backup', ['--compress' => true])
            ->expectsOutput('=== Game Backup Report ===')
            ->expectsOutput('Starting game backup...')
            ->expectsOutput('Compression enabled')
            ->expectsOutput('Database backup: COMPLETED')
            ->expectsOutput('Game backup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_backup_with_encryption()
    {
        $this
            ->artisan('game:backup', ['--encrypt' => true])
            ->expectsOutput('=== Game Backup Report ===')
            ->expectsOutput('Starting game backup...')
            ->expectsOutput('Encryption enabled')
            ->expectsOutput('Database backup: COMPLETED')
            ->expectsOutput('Game backup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_backup_with_verbose_output()
    {
        $this
            ->artisan('game:backup', ['--verbose' => true])
            ->expectsOutput('=== Game Backup Report ===')
            ->expectsOutput('Starting game backup...')
            ->expectsOutput('=== Database Backup ===')
            ->expectsOutput('Backing up database tables...')
            ->expectsOutput('Verifying backup integrity...')
            ->expectsOutput('=== File Backup ===')
            ->expectsOutput('Backing up game files...')
            ->expectsOutput('Verifying file integrity...')
            ->expectsOutput('Game backup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_backup_with_specific_destination()
    {
        $this
            ->artisan('game:backup', ['--destination' => '/backups/game'])
            ->expectsOutput('=== Game Backup Report ===')
            ->expectsOutput('Starting game backup...')
            ->expectsOutput('Backup destination: /backups/game')
            ->expectsOutput('Database backup: COMPLETED')
            ->expectsOutput('Game backup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_backup_with_retention_policy()
    {
        $this
            ->artisan('game:backup', ['--retention' => '30'])
            ->expectsOutput('=== Game Backup Report ===')
            ->expectsOutput('Starting game backup...')
            ->expectsOutput('Retention policy: 30 days')
            ->expectsOutput('Database backup: COMPLETED')
            ->expectsOutput('Game backup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_backup_with_verification()
    {
        $this
            ->artisan('game:backup', ['--verify' => true])
            ->expectsOutput('=== Game Backup Report ===')
            ->expectsOutput('Starting game backup...')
            ->expectsOutput('Backup verification enabled')
            ->expectsOutput('Database backup: COMPLETED')
            ->expectsOutput('Game backup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_backup_with_scheduling()
    {
        $this
            ->artisan('game:backup', ['--schedule' => 'daily'])
            ->expectsOutput('=== Game Backup Report ===')
            ->expectsOutput('Starting game backup...')
            ->expectsOutput('Scheduling daily backup...')
            ->expectsOutput('Game backup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_backup_with_cleanup()
    {
        $this
            ->artisan('game:backup', ['--cleanup' => true])
            ->expectsOutput('=== Game Backup Report ===')
            ->expectsOutput('Starting game backup...')
            ->expectsOutput('Cleaning up old backups...')
            ->expectsOutput('Game backup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_backup_with_validation()
    {
        $this
            ->artisan('game:backup', ['--validate' => true])
            ->expectsOutput('=== Game Backup Report ===')
            ->expectsOutput('Starting game backup...')
            ->expectsOutput('Validating backup configuration...')
            ->expectsOutput('Game backup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_backup_with_monitoring()
    {
        $this
            ->artisan('game:backup', ['--monitor' => true])
            ->expectsOutput('=== Game Backup Report ===')
            ->expectsOutput('Starting game backup...')
            ->expectsOutput('Monitoring backup progress...')
            ->expectsOutput('Game backup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_backup_with_debugging()
    {
        $this
            ->artisan('game:backup', ['--debug' => true])
            ->expectsOutput('=== Game Backup Report ===')
            ->expectsOutput('Starting game backup...')
            ->expectsOutput('Debug mode enabled')
            ->expectsOutput('Game backup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_backup_with_dry_run()
    {
        $this
            ->artisan('game:backup', ['--dry-run' => true])
            ->expectsOutput('=== Game Backup Report ===')
            ->expectsOutput('Starting game backup...')
            ->expectsOutput('Dry run mode enabled')
            ->expectsOutput('Game backup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_backup_with_quiet_mode()
    {
        $this
            ->artisan('game:backup', ['--quiet' => true])
            ->expectsOutput('=== Game Backup Report ===')
            ->expectsOutput('Starting game backup...')
            ->expectsOutput('Quiet mode enabled')
            ->expectsOutput('Game backup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_backup_errors()
    {
        // Mock database error
        DB::shouldReceive('table')->andThrow(new \Exception('Database error'));

        $this
            ->artisan('game:backup')
            ->expectsOutput('=== Game Backup Report ===')
            ->expectsOutput('Error during backup: Database error')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_run_backup_with_retry_on_failure()
    {
        $this
            ->artisan('game:backup', ['--retry' => 3])
            ->expectsOutput('=== Game Backup Report ===')
            ->expectsOutput('Starting game backup...')
            ->expectsOutput('Retry attempts set to: 3')
            ->expectsOutput('Game backup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_backup_with_timeout()
    {
        $this
            ->artisan('game:backup', ['--timeout' => '300'])
            ->expectsOutput('=== Game Backup Report ===')
            ->expectsOutput('Starting game backup...')
            ->expectsOutput('Timeout set to: 300 seconds')
            ->expectsOutput('Game backup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new GameBackupCommand();
        $this->assertEquals('game:backup', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new GameBackupCommand();
        $this->assertEquals('Create comprehensive backup of game data and files', $command->getDescription());
    }
}
