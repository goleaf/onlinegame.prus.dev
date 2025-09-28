<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\ProcessGameTick;
use App\Services\GameTickService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessGameTickTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_process_game_tick()
    {
        $this->mock(GameTickService::class, function ($mock): void {
            $mock->shouldReceive('processTick')->andReturn([
                'villages_processed' => 100,
                'resources_produced' => 5000,
                'training_completed' => 25,
                'buildings_completed' => 10,
            ]);
        });

        $this
            ->artisan('game:tick')
            ->expectsOutput('⏰ Game Tick Processor')
            ->expectsOutput('Processing game tick...')
            ->expectsOutput('Villages processed: 100')
            ->expectsOutput('Resources produced: 5000')
            ->expectsOutput('Training completed: 25')
            ->expectsOutput('Buildings completed: 10')
            ->expectsOutput('Game tick processed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_process_game_tick_with_verbose_output()
    {
        $this->mock(GameTickService::class, function ($mock): void {
            $mock->shouldReceive('processTick')->andReturn([
                'villages_processed' => 150,
                'resources_produced' => 7500,
                'training_completed' => 30,
                'buildings_completed' => 15,
                'details' => [
                    'wood_produced' => 2000,
                    'clay_produced' => 1500,
                    'iron_produced' => 1000,
                    'crop_produced' => 1000,
                ],
            ]);
        });

        $this
            ->artisan('game:tick', ['--verbose' => true])
            ->expectsOutput('⏰ Game Tick Processor')
            ->expectsOutput('Processing game tick...')
            ->expectsOutput('Villages processed: 150')
            ->expectsOutput('Resources produced: 7500')
            ->expectsOutput('Training completed: 30')
            ->expectsOutput('Buildings completed: 15')
            ->expectsOutput('Details:')
            ->expectsOutput('  Wood produced: 2000')
            ->expectsOutput('  Clay produced: 1500')
            ->expectsOutput('  Iron produced: 1000')
            ->expectsOutput('  Crop produced: 1000')
            ->expectsOutput('Game tick processed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_process_game_tick_with_specific_villages()
    {
        $this->mock(GameTickService::class, function ($mock): void {
            $mock
                ->shouldReceive('processTick')
                ->with(['village_ids' => [1, 2, 3]])
                ->andReturn([
                    'villages_processed' => 3,
                    'resources_produced' => 150,
                    'training_completed' => 1,
                    'buildings_completed' => 0,
                ]);
        });

        $this
            ->artisan('game:tick', ['--villages' => '1,2,3'])
            ->expectsOutput('⏰ Game Tick Processor')
            ->expectsOutput('Processing game tick...')
            ->expectsOutput('Villages processed: 3')
            ->expectsOutput('Resources produced: 150')
            ->expectsOutput('Training completed: 1')
            ->expectsOutput('Buildings completed: 0')
            ->expectsOutput('Game tick processed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_process_game_tick_with_dry_run()
    {
        $this->mock(GameTickService::class, function ($mock): void {
            $mock->shouldReceive('getTickPreview')->andReturn([
                'estimated_villages' => 100,
                'estimated_resources' => 5000,
                'estimated_training' => 25,
                'estimated_buildings' => 10,
            ]);
        });

        $this
            ->artisan('game:tick', ['--dry-run' => true])
            ->expectsOutput('⏰ Game Tick Processor')
            ->expectsOutput('Dry run mode - no actual processing will occur')
            ->expectsOutput('Estimated villages: 100')
            ->expectsOutput('Estimated resources: 5000')
            ->expectsOutput('Estimated training: 25')
            ->expectsOutput('Estimated buildings: 10')
            ->expectsOutput('Dry run completed')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_process_game_tick_with_force_flag()
    {
        $this->mock(GameTickService::class, function ($mock): void {
            $mock
                ->shouldReceive('processTick')
                ->with(['force' => true])
                ->andReturn([
                    'villages_processed' => 200,
                    'resources_produced' => 10000,
                    'training_completed' => 50,
                    'buildings_completed' => 20,
                ]);
        });

        $this
            ->artisan('game:tick', ['--force' => true])
            ->expectsOutput('⏰ Game Tick Processor')
            ->expectsOutput('Processing game tick...')
            ->expectsOutput('Villages processed: 200')
            ->expectsOutput('Resources produced: 10000')
            ->expectsOutput('Training completed: 50')
            ->expectsOutput('Buildings completed: 20')
            ->expectsOutput('Game tick processed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_process_game_tick_with_backup()
    {
        $this->mock(GameTickService::class, function ($mock): void {
            $mock
                ->shouldReceive('processTick')
                ->with(['backup' => true])
                ->andReturn([
                    'villages_processed' => 100,
                    'resources_produced' => 5000,
                    'training_completed' => 25,
                    'buildings_completed' => 10,
                    'backup_created' => true,
                ]);
        });

        $this
            ->artisan('game:tick', ['--backup' => true])
            ->expectsOutput('⏰ Game Tick Processor')
            ->expectsOutput('Processing game tick...')
            ->expectsOutput('Villages processed: 100')
            ->expectsOutput('Resources produced: 5000')
            ->expectsOutput('Training completed: 25')
            ->expectsOutput('Buildings completed: 10')
            ->expectsOutput('Backup created successfully')
            ->expectsOutput('Game tick processed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_process_game_tick_with_specific_operations()
    {
        $this->mock(GameTickService::class, function ($mock): void {
            $mock
                ->shouldReceive('processTick')
                ->with(['operations' => ['resources', 'training']])
                ->andReturn([
                    'villages_processed' => 100,
                    'resources_produced' => 5000,
                    'training_completed' => 25,
                    'buildings_completed' => 0,
                ]);
        });

        $this
            ->artisan('game:tick', ['--operations' => 'resources,training'])
            ->expectsOutput('⏰ Game Tick Processor')
            ->expectsOutput('Processing game tick...')
            ->expectsOutput('Villages processed: 100')
            ->expectsOutput('Resources produced: 5000')
            ->expectsOutput('Training completed: 25')
            ->expectsOutput('Buildings completed: 0')
            ->expectsOutput('Game tick processed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_game_tick_failure()
    {
        $this->mock(GameTickService::class, function ($mock): void {
            $mock
                ->shouldReceive('processTick')
                ->andThrow(new \Exception('Game tick processing failed'));
        });

        $this
            ->artisan('game:tick')
            ->expectsOutput('⏰ Game Tick Processor')
            ->expectsOutput('Processing game tick...')
            ->expectsOutput('Game tick processing failed: Game tick processing failed')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_invalid_village_ids()
    {
        $this
            ->artisan('game:tick', ['--villages' => 'invalid,ids'])
            ->expectsOutput('⏰ Game Tick Processor')
            ->expectsOutput('Invalid village IDs. Using all villages.')
            ->expectsOutput('Processing game tick...')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_invalid_operations()
    {
        $this
            ->artisan('game:tick', ['--operations' => 'invalid,operations'])
            ->expectsOutput('⏰ Game Tick Processor')
            ->expectsOutput('Invalid operations. Using all operations.')
            ->expectsOutput('Processing game tick...')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_show_game_tick_statistics()
    {
        $this
            ->artisan('game:tick', ['--stats' => true])
            ->expectsOutput('⏰ Game Tick Processor')
            ->expectsOutput('Game Tick Statistics:')
            ->expectsOutput('Total ticks processed: 0')
            ->expectsOutput('Average villages per tick: 0')
            ->expectsOutput('Average resources per tick: 0')
            ->expectsOutput('Last tick: Never')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_show_game_tick_history()
    {
        $this
            ->artisan('game:tick', ['--history' => true])
            ->expectsOutput('⏰ Game Tick Processor')
            ->expectsOutput('Game Tick History:')
            ->expectsOutput('No tick history available')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new ProcessGameTick();
        $this->assertEquals('game:tick', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new ProcessGameTick();
        $this->assertEquals('Process game tick for all villages', $command->getDescription());
    }
}
