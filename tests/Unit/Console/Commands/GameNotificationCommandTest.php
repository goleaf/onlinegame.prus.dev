<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\GameNotificationCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GameNotificationCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_run_game_notification()
    {
        $this
            ->artisan('game:notification')
            ->expectsOutput('=== Game Notification Report ===')
            ->expectsOutput('Starting game notification...')
            ->expectsOutput('Email notifications: COMPLETED')
            ->expectsOutput('Push notifications: COMPLETED')
            ->expectsOutput('Game notification completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_notification_with_specific_types()
    {
        $this
            ->artisan('game:notification', ['--types' => 'email,push'])
            ->expectsOutput('=== Game Notification Report ===')
            ->expectsOutput('Starting game notification...')
            ->expectsOutput('Running notification types: email, push')
            ->expectsOutput('Email notifications: COMPLETED')
            ->expectsOutput('Push notifications: COMPLETED')
            ->expectsOutput('Game notification completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_notification_with_verbose_output()
    {
        $this
            ->artisan('game:notification', ['--verbose' => true])
            ->expectsOutput('=== Game Notification Report ===')
            ->expectsOutput('Starting game notification...')
            ->expectsOutput('=== Email Notifications ===')
            ->expectsOutput('Sending email notifications...')
            ->expectsOutput('Processing email queue...')
            ->expectsOutput('=== Push Notifications ===')
            ->expectsOutput('Sending push notifications...')
            ->expectsOutput('Processing push queue...')
            ->expectsOutput('Game notification completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_notification_with_reporting()
    {
        $this
            ->artisan('game:notification', ['--report' => true])
            ->expectsOutput('=== Game Notification Report ===')
            ->expectsOutput('Starting game notification...')
            ->expectsOutput('Generating notification report...')
            ->expectsOutput('Game notification completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_notification_with_export()
    {
        $this
            ->artisan('game:notification', ['--export' => 'notifications.json'])
            ->expectsOutput('=== Game Notification Report ===')
            ->expectsOutput('Starting game notification...')
            ->expectsOutput('Exporting notification data to: notifications.json')
            ->expectsOutput('Game notification completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_notification_with_scheduling()
    {
        $this
            ->artisan('game:notification', ['--schedule' => 'daily'])
            ->expectsOutput('=== Game Notification Report ===')
            ->expectsOutput('Starting game notification...')
            ->expectsOutput('Scheduling daily notifications...')
            ->expectsOutput('Game notification completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_notification_with_cleanup()
    {
        $this
            ->artisan('game:notification', ['--cleanup' => true])
            ->expectsOutput('=== Game Notification Report ===')
            ->expectsOutput('Starting game notification...')
            ->expectsOutput('Cleaning up notification data...')
            ->expectsOutput('Game notification completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_notification_with_validation()
    {
        $this
            ->artisan('game:notification', ['--validate' => true])
            ->expectsOutput('=== Game Notification Report ===')
            ->expectsOutput('Starting game notification...')
            ->expectsOutput('Validating notification configuration...')
            ->expectsOutput('Game notification completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_notification_with_monitoring()
    {
        $this
            ->artisan('game:notification', ['--monitor' => true])
            ->expectsOutput('=== Game Notification Report ===')
            ->expectsOutput('Starting game notification...')
            ->expectsOutput('Monitoring notification progress...')
            ->expectsOutput('Game notification completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_notification_with_debugging()
    {
        $this
            ->artisan('game:notification', ['--debug' => true])
            ->expectsOutput('=== Game Notification Report ===')
            ->expectsOutput('Starting game notification...')
            ->expectsOutput('Debug mode enabled')
            ->expectsOutput('Game notification completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_notification_with_dry_run()
    {
        $this
            ->artisan('game:notification', ['--dry-run' => true])
            ->expectsOutput('=== Game Notification Report ===')
            ->expectsOutput('Starting game notification...')
            ->expectsOutput('Dry run mode enabled')
            ->expectsOutput('Game notification completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_notification_with_quiet_mode()
    {
        $this
            ->artisan('game:notification', ['--quiet' => true])
            ->expectsOutput('=== Game Notification Report ===')
            ->expectsOutput('Starting game notification...')
            ->expectsOutput('Quiet mode enabled')
            ->expectsOutput('Game notification completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_notification_errors()
    {
        // Mock database error
        DB::shouldReceive('table')->andThrow(new \Exception('Database error'));

        $this
            ->artisan('game:notification')
            ->expectsOutput('=== Game Notification Report ===')
            ->expectsOutput('Error during notification: Database error')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_run_notification_with_retry_on_failure()
    {
        $this
            ->artisan('game:notification', ['--retry' => 3])
            ->expectsOutput('=== Game Notification Report ===')
            ->expectsOutput('Starting game notification...')
            ->expectsOutput('Retry attempts set to: 3')
            ->expectsOutput('Game notification completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_notification_with_timeout()
    {
        $this
            ->artisan('game:notification', ['--timeout' => '300'])
            ->expectsOutput('=== Game Notification Report ===')
            ->expectsOutput('Starting game notification...')
            ->expectsOutput('Timeout set to: 300 seconds')
            ->expectsOutput('Game notification completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new GameNotificationCommand();
        $this->assertEquals('game:notification', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new GameNotificationCommand();
        $this->assertEquals('Send notifications to users and manage notification queues', $command->getDescription());
    }
}
