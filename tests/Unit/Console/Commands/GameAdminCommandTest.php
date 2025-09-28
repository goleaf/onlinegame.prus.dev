<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\GameAdminCommand;
use App\Services\GameErrorHandler;
use App\Services\GamePerformanceMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GameAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    private GameAdminCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new GameAdminCommand();
    }

    /**
     * @test
     */
    public function it_can_show_game_stats()
    {
        // Create test data
        DB::table('users')->insert([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'last_activity_at' => now()->subDays(3),
        ]);

        DB::table('users')->insert([
            'name' => 'Active User',
            'email' => 'active@example.com',
            'password' => bcrypt('password'),
            'last_activity_at' => now()->subHours(1),
        ]);

        DB::table('villages')->insert([
            'name' => 'Test Village',
            'player_id' => 1,
            'world_id' => 1,
            'updated_at' => now()->subHours(1),
        ]);

        DB::table('alliances')->insert([
            'name' => 'Test Alliance',
            'world_id' => 1,
        ]);

        DB::table('battles')->insert([
            'attacker_id' => 1,
            'defender_id' => 2,
            'world_id' => 1,
            'created_at' => now()->subHours(1),
        ]);

        $this
            ->artisan('game:admin', ['action' => 'stats'])
            ->expectsOutput('=== Game Statistics ===')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_show_performance_report()
    {
        GamePerformanceMonitor::shouldReceive('generatePerformanceReport')
            ->once()
            ->andReturn([
                'performance_stats' => [
                    'queries' => [
                        'total' => 100,
                        'average_time' => 0.05,
                        'slow_queries' => 5,
                    ],
                    'responses' => [
                        'total' => 1000,
                        'average_time' => 0.1,
                        'slow_responses' => 10,
                    ],
                    'memory' => [
                        'current_usage_mb' => 50,
                        'peak_usage_mb' => 100,
                    ],
                ],
                'concurrent_users' => 25,
                'recommendations' => ['Optimize database queries'],
            ]);

        $this
            ->artisan('game:admin', ['action' => 'performance'])
            ->expectsOutput('=== Performance Report ===')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_give_resources_with_valid_options()
    {
        // Create test village
        DB::table('villages')->insert([
            'id' => 1,
            'name' => 'Test Village',
            'player_id' => 1,
            'world_id' => 1,
            'wood' => 1000,
            'clay' => 1000,
            'iron' => 1000,
            'crop' => 1000,
        ]);

        GameErrorHandler::shouldReceive('logGameAction')
            ->once()
            ->with('admin_give_resources', \Mockery::type('array'));

        $this
            ->artisan('game:admin', [
                'action' => 'give-resources',
                '--player' => '1',
                '--village' => '1',
                '--amount' => '5000',
                '--type' => 'wood',
            ])
            ->expectsOutput('Added 5000 wood to village 1')
            ->assertExitCode(0);

        $village = DB::table('villages')->where('id', 1)->first();
        $this->assertEquals(6000, $village->wood);
    }

    /**
     * @test
     */
    public function it_fails_to_give_resources_with_missing_options()
    {
        $this
            ->artisan('game:admin', [
                'action' => 'give-resources',
                '--player' => '1',
            ])
            ->expectsOutput('All options are required: --player, --village, --amount, --type')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_fails_to_give_resources_with_invalid_type()
    {
        $this
            ->artisan('game:admin', [
                'action' => 'give-resources',
                '--player' => '1',
                '--village' => '1',
                '--amount' => '5000',
                '--type' => 'invalid',
            ])
            ->expectsOutput('Invalid resource type. Valid types: wood, clay, iron, crop')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_fails_to_give_resources_to_nonexistent_village()
    {
        $this
            ->artisan('game:admin', [
                'action' => 'give-resources',
                '--player' => '1',
                '--village' => '999',
                '--amount' => '5000',
                '--type' => 'wood',
            ])
            ->expectsOutput('Village not found or does not belong to player')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_reset_player_with_confirmation()
    {
        // Create test data
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'points' => 1000,
            'rank' => 5,
        ]);

        DB::table('villages')->insert([
            'id' => 1,
            'name' => 'Test Village',
            'player_id' => 1,
            'world_id' => 1,
            'wood' => 5000,
            'clay' => 5000,
            'iron' => 5000,
            'crop' => 5000,
        ]);

        DB::table('battles')->insert([
            'id' => 1,
            'attacker_id' => 1,
            'defender_id' => 2,
            'world_id' => 1,
        ]);

        GameErrorHandler::shouldReceive('logGameAction')
            ->once()
            ->with('admin_reset_player', \Mockery::type('array'));

        $this
            ->artisan('game:admin', [
                'action' => 'reset-player',
                '--player' => '1',
            ])
            ->expectsConfirmation('Are you sure you want to reset player 1? This action cannot be undone.', 'yes')
            ->expectsOutput('Player 1 has been reset successfully')
            ->assertExitCode(0);

        $user = DB::table('users')->where('id', 1)->first();
        $this->assertEquals(0, $user->points);
        $this->assertNull($user->rank);

        $village = DB::table('villages')->where('id', 1)->first();
        $this->assertEquals(1000, $village->wood);
        $this->assertEquals(1000, $village->clay);
        $this->assertEquals(1000, $village->iron);
        $this->assertEquals(1000, $village->crop);

        $battleCount = DB::table('battles')->where('attacker_id', 1)->count();
        $this->assertEquals(0, $battleCount);
    }

    /**
     * @test
     */
    public function it_cancels_reset_player_without_confirmation()
    {
        $this
            ->artisan('game:admin', [
                'action' => 'reset-player',
                '--player' => '1',
            ])
            ->expectsConfirmation('Are you sure you want to reset player 1? This action cannot be undone.', 'no')
            ->expectsOutput('Operation cancelled')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_fails_to_reset_player_without_player_id()
    {
        $this
            ->artisan('game:admin', [
                'action' => 'reset-player',
            ])
            ->expectsOutput('Player ID is required: --player')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_cleanup_old_data()
    {
        // Create old data
        DB::table('battles')->insert([
            'attacker_id' => 1,
            'defender_id' => 2,
            'world_id' => 1,
            'created_at' => now()->subDays(35),
        ]);

        DB::table('movements')->insert([
            'player_id' => 1,
            'world_id' => 1,
            'created_at' => now()->subDays(10),
        ]);

        DB::table('activity_log')->insert([
            'user_id' => 1,
            'action' => 'test',
            'created_at' => now()->subDays(20),
        ]);

        GameErrorHandler::shouldReceive('logGameAction')
            ->once()
            ->with('admin_cleanup', \Mockery::type('array'));

        $this
            ->artisan('game:admin', ['action' => 'cleanup'])
            ->expectsOutput('Starting cleanup of old data...')
            ->expectsOutput('Cleanup completed:')
            ->assertExitCode(0);

        $battleCount = DB::table('battles')->count();
        $movementCount = DB::table('movements')->count();
        $logCount = DB::table('activity_log')->count();

        $this->assertEquals(0, $battleCount);
        $this->assertEquals(0, $movementCount);
        $this->assertEquals(0, $logCount);
    }

    /**
     * @test
     */
    public function it_can_create_game_backup()
    {
        GameErrorHandler::shouldReceive('logGameAction')
            ->once()
            ->with('admin_backup', \Mockery::type('array'));

        $this
            ->artisan('game:admin', ['action' => 'backup'])
            ->expectsOutput('Creating game backup...')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_shows_help_for_unknown_action()
    {
        $this
            ->artisan('game:admin', ['action' => 'unknown'])
            ->expectsOutput('Unknown action: unknown')
            ->expectsOutput('Available actions:')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_database_errors_gracefully()
    {
        DB::shouldReceive('table')
            ->andThrow(new \Exception('Database error'));

        GameErrorHandler::shouldReceive('handleGameError')
            ->once()
            ->with(\Mockery::type('Exception'), \Mockery::type('array'));

        $this
            ->artisan('game:admin', ['action' => 'stats'])
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_give_resources_database_error()
    {
        DB::shouldReceive('table')
            ->andThrow(new \Exception('Database error'));

        GameErrorHandler::shouldReceive('handleGameError')
            ->once()
            ->with(\Mockery::type('Exception'), \Mockery::type('array'));

        $this
            ->artisan('game:admin', [
                'action' => 'give-resources',
                '--player' => '1',
                '--village' => '1',
                '--amount' => '5000',
                '--type' => 'wood',
            ])
            ->expectsOutput('Failed to give resources: Database error')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_reset_player_database_error()
    {
        DB::shouldReceive('beginTransaction')
            ->andThrow(new \Exception('Database error'));

        GameErrorHandler::shouldReceive('handleGameError')
            ->once()
            ->with(\Mockery::type('Exception'), \Mockery::type('array'));

        $this
            ->artisan('game:admin', [
                'action' => 'reset-player',
                '--player' => '1',
            ])
            ->expectsConfirmation('Are you sure you want to reset player 1? This action cannot be undone.', 'yes')
            ->expectsOutput('Failed to reset player: Database error')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_cleanup_database_error()
    {
        DB::shouldReceive('table')
            ->andThrow(new \Exception('Database error'));

        GameErrorHandler::shouldReceive('handleGameError')
            ->once()
            ->with(\Mockery::type('Exception'), \Mockery::type('array'));

        $this
            ->artisan('game:admin', ['action' => 'cleanup'])
            ->expectsOutput('Cleanup failed: Database error')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_backup_database_error()
    {
        GameErrorHandler::shouldReceive('handleGameError')
            ->once()
            ->with(\Mockery::type('Exception'), \Mockery::type('array'));

        $this
            ->artisan('game:admin', ['action' => 'backup'])
            ->expectsOutput('Backup failed: Database error')
            ->assertExitCode(0);
    }
}
