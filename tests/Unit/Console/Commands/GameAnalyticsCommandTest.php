<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\GameAnalyticsCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GameAnalyticsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_run_game_analytics()
    {
        $this
            ->artisan('game:analytics')
            ->expectsOutput('=== Game Analytics Report ===')
            ->expectsOutput('Starting game analytics...')
            ->expectsOutput('User analytics: COMPLETED')
            ->expectsOutput('Performance analytics: COMPLETED')
            ->expectsOutput('Game analytics completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_specific_metrics()
    {
        $this
            ->artisan('game:analytics', ['--metrics' => 'users,performance'])
            ->expectsOutput('=== Game Analytics Report ===')
            ->expectsOutput('Starting game analytics...')
            ->expectsOutput('Running analytics metrics: users, performance')
            ->expectsOutput('User analytics: COMPLETED')
            ->expectsOutput('Performance analytics: COMPLETED')
            ->expectsOutput('Game analytics completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_verbose_output()
    {
        $this
            ->artisan('game:analytics', ['--verbose' => true])
            ->expectsOutput('=== Game Analytics Report ===')
            ->expectsOutput('Starting game analytics...')
            ->expectsOutput('=== User Analytics ===')
            ->expectsOutput('Analyzing user behavior...')
            ->expectsOutput('Processing user data...')
            ->expectsOutput('=== Performance Analytics ===')
            ->expectsOutput('Analyzing system performance...')
            ->expectsOutput('Processing performance data...')
            ->expectsOutput('Game analytics completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_reporting()
    {
        $this
            ->artisan('game:analytics', ['--report' => true])
            ->expectsOutput('=== Game Analytics Report ===')
            ->expectsOutput('Starting game analytics...')
            ->expectsOutput('Generating analytics report...')
            ->expectsOutput('Game analytics completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_export()
    {
        $this
            ->artisan('game:analytics', ['--export' => 'analytics.json'])
            ->expectsOutput('=== Game Analytics Report ===')
            ->expectsOutput('Starting game analytics...')
            ->expectsOutput('Exporting analytics data to: analytics.json')
            ->expectsOutput('Game analytics completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_scheduling()
    {
        $this
            ->artisan('game:analytics', ['--schedule' => 'daily'])
            ->expectsOutput('=== Game Analytics Report ===')
            ->expectsOutput('Starting game analytics...')
            ->expectsOutput('Scheduling daily analytics...')
            ->expectsOutput('Game analytics completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_cleanup()
    {
        $this
            ->artisan('game:analytics', ['--cleanup' => true])
            ->expectsOutput('=== Game Analytics Report ===')
            ->expectsOutput('Starting game analytics...')
            ->expectsOutput('Cleaning up analytics data...')
            ->expectsOutput('Game analytics completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_validation()
    {
        $this
            ->artisan('game:analytics', ['--validate' => true])
            ->expectsOutput('=== Game Analytics Report ===')
            ->expectsOutput('Starting game analytics...')
            ->expectsOutput('Validating analytics configuration...')
            ->expectsOutput('Game analytics completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_monitoring()
    {
        $this
            ->artisan('game:analytics', ['--monitor' => true])
            ->expectsOutput('=== Game Analytics Report ===')
            ->expectsOutput('Starting game analytics...')
            ->expectsOutput('Monitoring analytics progress...')
            ->expectsOutput('Game analytics completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_debugging()
    {
        $this
            ->artisan('game:analytics', ['--debug' => true])
            ->expectsOutput('=== Game Analytics Report ===')
            ->expectsOutput('Starting game analytics...')
            ->expectsOutput('Debug mode enabled')
            ->expectsOutput('Game analytics completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_dry_run()
    {
        $this
            ->artisan('game:analytics', ['--dry-run' => true])
            ->expectsOutput('=== Game Analytics Report ===')
            ->expectsOutput('Starting game analytics...')
            ->expectsOutput('Dry run mode enabled')
            ->expectsOutput('Game analytics completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_quiet_mode()
    {
        $this
            ->artisan('game:analytics', ['--quiet' => true])
            ->expectsOutput('=== Game Analytics Report ===')
            ->expectsOutput('Starting game analytics...')
            ->expectsOutput('Quiet mode enabled')
            ->expectsOutput('Game analytics completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_analytics_errors()
    {
        // Mock database error
        DB::shouldReceive('table')->andThrow(new \Exception('Database error'));

        $this
            ->artisan('game:analytics')
            ->expectsOutput('=== Game Analytics Report ===')
            ->expectsOutput('Error during analytics: Database error')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_retry_on_failure()
    {
        $this
            ->artisan('game:analytics', ['--retry' => 3])
            ->expectsOutput('=== Game Analytics Report ===')
            ->expectsOutput('Starting game analytics...')
            ->expectsOutput('Retry attempts set to: 3')
            ->expectsOutput('Game analytics completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_timeout()
    {
        $this
            ->artisan('game:analytics', ['--timeout' => '300'])
            ->expectsOutput('=== Game Analytics Report ===')
            ->expectsOutput('Starting game analytics...')
            ->expectsOutput('Timeout set to: 300 seconds')
            ->expectsOutput('Game analytics completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new GameAnalyticsCommand();
        $this->assertEquals('game:analytics', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new GameAnalyticsCommand();
        $this->assertEquals('Generate comprehensive analytics and performance reports', $command->getDescription());
    }
}
