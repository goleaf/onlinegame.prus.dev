<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\CleanupCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CleanupCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_run_full_cleanup()
    {
        // Create test data
        DB::table('activity_log')->insert([
            ['description' => 'Old log 1', 'created_at' => now()->subDays(31), 'updated_at' => now()],
            ['description' => 'Old log 2', 'created_at' => now()->subDays(32), 'updated_at' => now()],
            ['description' => 'Recent log', 'created_at' => now()->subDays(1), 'updated_at' => now()],
        ]);

        Storage::fake('local');
        Storage::put('temp/old_file.txt', 'content');
        Storage::put('logs/old.log', 'log content');

        $this
            ->artisan('cleanup:run')
            ->expectsOutput('Starting cleanup process...')
            ->expectsOutput('=== Cleanup Summary ===')
            ->expectsOutput('Old logs cleaned: ')
            ->expectsOutput('Temp files cleaned: ')
            ->expectsOutput('Cache cleared: Yes')
            ->expectsOutput('Cleanup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_cleanup_old_logs_only()
    {
        // Create test data
        DB::table('activity_log')->insert([
            ['description' => 'Old log 1', 'created_at' => now()->subDays(31), 'updated_at' => now()],
            ['description' => 'Old log 2', 'created_at' => now()->subDays(32), 'updated_at' => now()],
            ['description' => 'Recent log', 'created_at' => now()->subDays(1), 'updated_at' => now()],
        ]);

        $this
            ->artisan('cleanup:run', ['--logs-only' => true])
            ->expectsOutput('Starting cleanup process...')
            ->expectsOutput('Cleaning old logs only...')
            ->expectsOutput('=== Cleanup Summary ===')
            ->expectsOutput('Old logs cleaned: 2')
            ->expectsOutput('Cleanup completed successfully!')
            ->assertExitCode(0);

        // Verify only old logs are deleted
        $this->assertEquals(1, DB::table('activity_log')->count());
    }

    /**
     * @test
     */
    public function it_can_cleanup_temp_files_only()
    {
        Storage::fake('local');
        Storage::put('temp/file1.txt', 'content1');
        Storage::put('temp/file2.txt', 'content2');
        Storage::put('uploads/permanent.txt', 'permanent content');

        $this
            ->artisan('cleanup:run', ['--temp-only' => true])
            ->expectsOutput('Starting cleanup process...')
            ->expectsOutput('Cleaning temp files only...')
            ->expectsOutput('=== Cleanup Summary ===')
            ->expectsOutput('Temp files cleaned: ')
            ->expectsOutput('Cleanup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_cleanup_with_custom_days()
    {
        // Create test data with different ages
        DB::table('activity_log')->insert([
            ['description' => 'Very old log', 'created_at' => now()->subDays(8), 'updated_at' => now()],
            ['description' => 'Old log', 'created_at' => now()->subDays(6), 'updated_at' => now()],
            ['description' => 'Recent log', 'created_at' => now()->subDays(1), 'updated_at' => now()],
        ]);

        $this
            ->artisan('cleanup:run', ['--days' => '7'])
            ->expectsOutput('Starting cleanup process...')
            ->expectsOutput('Using custom retention period: 7 days')
            ->expectsOutput('=== Cleanup Summary ===')
            ->expectsOutput('Old logs cleaned: 1')
            ->expectsOutput('Cleanup completed successfully!')
            ->assertExitCode(0);

        // Verify only logs older than 7 days are deleted
        $this->assertEquals(2, DB::table('activity_log')->count());
    }

    /**
     * @test
     */
    public function it_can_cleanup_with_confirmation()
    {
        DB::table('activity_log')->insert([
            ['description' => 'Old log', 'created_at' => now()->subDays(31), 'updated_at' => now()],
        ]);

        $this
            ->artisan('cleanup:run', ['--confirm' => true])
            ->expectsConfirmation('Are you sure you want to run cleanup?', 'yes')
            ->expectsOutput('Starting cleanup process...')
            ->expectsOutput('=== Cleanup Summary ===')
            ->expectsOutput('Cleanup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_cancel_cleanup_with_confirmation()
    {
        DB::table('activity_log')->insert([
            ['description' => 'Old log', 'created_at' => now()->subDays(31), 'updated_at' => now()],
        ]);

        $this
            ->artisan('cleanup:run', ['--confirm' => true])
            ->expectsConfirmation('Are you sure you want to run cleanup?', 'no')
            ->expectsOutput('Cleanup cancelled.')
            ->assertExitCode(0);

        // Verify data is not deleted
        $this->assertEquals(1, DB::table('activity_log')->count());
    }

    /**
     * @test
     */
    public function it_can_run_dry_run_cleanup()
    {
        DB::table('activity_log')->insert([
            ['description' => 'Old log', 'created_at' => now()->subDays(31), 'updated_at' => now()],
        ]);

        Storage::fake('local');
        Storage::put('temp/old_file.txt', 'content');

        $this
            ->artisan('cleanup:run', ['--dry-run' => true])
            ->expectsOutput('Starting cleanup process...')
            ->expectsOutput('DRY RUN MODE - No data will be deleted')
            ->expectsOutput('=== Cleanup Summary (Dry Run) ===')
            ->expectsOutput('Would clean old logs: ')
            ->expectsOutput('Would clean temp files: ')
            ->expectsOutput('Dry run completed successfully!')
            ->assertExitCode(0);

        // Verify data is not deleted in dry run
        $this->assertEquals(1, DB::table('activity_log')->count());
        $this->assertTrue(Storage::exists('temp/old_file.txt'));
    }

    /**
     * @test
     */
    public function it_can_cleanup_with_verbose_output()
    {
        DB::table('activity_log')->insert([
            ['description' => 'Old log', 'created_at' => now()->subDays(31), 'updated_at' => now()],
        ]);

        $this
            ->artisan('cleanup:run', ['--verbose' => true])
            ->expectsOutput('Starting cleanup process...')
            ->expectsOutput('Cleaning activity logs older than 30 days...')
            ->expectsOutput('Cleaning temporary files...')
            ->expectsOutput('Clearing application cache...')
            ->expectsOutput('=== Cleanup Summary ===')
            ->expectsOutput('Cleanup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_cleanup_errors_gracefully()
    {
        // Mock database error
        DB::shouldReceive('table')->andThrow(new \Exception('Database error'));

        $this
            ->artisan('cleanup:run')
            ->expectsOutput('Starting cleanup process...')
            ->expectsOutput('Error during cleanup: Database error')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_cleanup_specific_tables()
    {
        DB::table('activity_log')->insert([
            ['description' => 'Old log', 'created_at' => now()->subDays(31), 'updated_at' => now()],
        ]);

        $this
            ->artisan('cleanup:run', ['--tables' => 'activity_log'])
            ->expectsOutput('Starting cleanup process...')
            ->expectsOutput('Cleaning specific tables: activity_log')
            ->expectsOutput('=== Cleanup Summary ===')
            ->expectsOutput('Old logs cleaned: ')
            ->expectsOutput('Cleanup completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new CleanupCommand();
        $this->assertEquals('cleanup:run', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new CleanupCommand();
        $this->assertEquals('Clean up old data, logs, and temporary files', $command->getDescription());
    }
}
