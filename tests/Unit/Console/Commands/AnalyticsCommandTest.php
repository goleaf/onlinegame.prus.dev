<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\AnalyticsCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_run_analytics()
    {
        $this
            ->artisan('analytics:generate')
            ->expectsOutput('=== Analytics Generation ===')
            ->expectsOutput('Starting analytics generation...')
            ->expectsOutput('User analytics: COMPLETED')
            ->expectsOutput('System analytics: COMPLETED')
            ->expectsOutput('Analytics generation completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_specific_types()
    {
        $this
            ->artisan('analytics:generate', ['--types' => 'users,system'])
            ->expectsOutput('=== Analytics Generation ===')
            ->expectsOutput('Starting analytics generation...')
            ->expectsOutput('Running analytics types: users, system')
            ->expectsOutput('User analytics: COMPLETED')
            ->expectsOutput('System analytics: COMPLETED')
            ->expectsOutput('Analytics generation completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_verbose_output()
    {
        $this
            ->artisan('analytics:generate', ['--verbose' => true])
            ->expectsOutput('=== Analytics Generation ===')
            ->expectsOutput('Starting analytics generation...')
            ->expectsOutput('=== User Analytics ===')
            ->expectsOutput('Processing user data...')
            ->expectsOutput('Calculating user metrics...')
            ->expectsOutput('=== System Analytics ===')
            ->expectsOutput('Processing system data...')
            ->expectsOutput('Calculating system metrics...')
            ->expectsOutput('Analytics generation completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_export()
    {
        $this
            ->artisan('analytics:generate', ['--export' => 'analytics.json'])
            ->expectsOutput('=== Analytics Generation ===')
            ->expectsOutput('Starting analytics generation...')
            ->expectsOutput('Exporting analytics to: analytics.json')
            ->expectsOutput('Analytics generation completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_date_range()
    {
        $this
            ->artisan('analytics:generate', ['--from' => '2024-01-01', '--to' => '2024-12-31'])
            ->expectsOutput('=== Analytics Generation ===')
            ->expectsOutput('Starting analytics generation...')
            ->expectsOutput('Date range: 2024-01-01 to 2024-12-31')
            ->expectsOutput('Analytics generation completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_aggregation()
    {
        $this
            ->artisan('analytics:generate', ['--aggregate' => 'daily'])
            ->expectsOutput('=== Analytics Generation ===')
            ->expectsOutput('Starting analytics generation...')
            ->expectsOutput('Aggregation level: daily')
            ->expectsOutput('Analytics generation completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_filtering()
    {
        $this
            ->artisan('analytics:generate', ['--filter' => 'active_users'])
            ->expectsOutput('=== Analytics Generation ===')
            ->expectsOutput('Starting analytics generation...')
            ->expectsOutput('Filter applied: active_users')
            ->expectsOutput('Analytics generation completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_analytics_with_debugging()
    {
        $this
            ->artisan('analytics:generate', ['--debug' => true])
            ->expectsOutput('=== Analytics Generation ===')
            ->expectsOutput('Starting analytics generation...')
            ->expectsOutput('Debug mode enabled')
            ->expectsOutput('Analytics generation completed successfully!')
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
            ->artisan('analytics:generate')
            ->expectsOutput('=== Analytics Generation ===')
            ->expectsOutput('Error during analytics generation: Database error')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new AnalyticsCommand();
        $this->assertEquals('analytics:generate', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new AnalyticsCommand();
        $this->assertEquals('Generate comprehensive analytics and reports', $command->getDescription());
    }
}
