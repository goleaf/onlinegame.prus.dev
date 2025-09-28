<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\SmartCacheGameCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SmartCacheGameCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_smart_cache_game_command()
    {
        $command = new SmartCacheGameCommand();

        $this->assertInstanceOf(SmartCacheGameCommand::class, $command);
    }

    /**
     * @test
     */
    public function it_can_execute_smart_cache_game_command()
    {
        $command = new SmartCacheGameCommand();
        $result = $command->handle();

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_smart_cache_game_command_with_options()
    {
        $command = new SmartCacheGameCommand();
        $result = $command->handle([
            '--action' => 'warm',
            '--type' => 'all',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_smart_cache_game_command_with_warm_action()
    {
        $command = new SmartCacheGameCommand();
        $result = $command->handle([
            '--action' => 'warm',
            '--type' => 'players',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_smart_cache_game_command_with_clear_action()
    {
        $command = new SmartCacheGameCommand();
        $result = $command->handle([
            '--action' => 'clear',
            '--type' => 'villages',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_smart_cache_game_command_with_refresh_action()
    {
        $command = new SmartCacheGameCommand();
        $result = $command->handle([
            '--action' => 'refresh',
            '--type' => 'alliances',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_smart_cache_game_command_with_invalidate_action()
    {
        $command = new SmartCacheGameCommand();
        $result = $command->handle([
            '--action' => 'invalidate',
            '--type' => 'battles',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_smart_cache_game_command_with_stats_action()
    {
        $command = new SmartCacheGameCommand();
        $result = $command->handle([
            '--action' => 'stats',
            '--type' => 'all',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_smart_cache_game_command_with_optimize_action()
    {
        $command = new SmartCacheGameCommand();
        $result = $command->handle([
            '--action' => 'optimize',
            '--type' => 'all',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_smart_cache_game_command_with_cleanup_action()
    {
        $command = new SmartCacheGameCommand();
        $result = $command->handle([
            '--action' => 'cleanup',
            '--type' => 'all',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_smart_cache_game_command_with_verbose()
    {
        $command = new SmartCacheGameCommand();
        $result = $command->handle([
            '--verbose' => true,
            '--action' => 'warm',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_smart_cache_game_command_with_quiet()
    {
        $command = new SmartCacheGameCommand();
        $result = $command->handle([
            '--quiet' => true,
            '--action' => 'clear',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_smart_cache_game_command_with_no_interaction()
    {
        $command = new SmartCacheGameCommand();
        $result = $command->handle([
            '--no-interaction' => true,
            '--action' => 'refresh',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_smart_cache_game_command_with_force()
    {
        $command = new SmartCacheGameCommand();
        $result = $command->handle([
            '--force' => true,
            '--action' => 'invalidate',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_smart_cache_game_command_with_help()
    {
        $command = new SmartCacheGameCommand();
        $result = $command->handle(['--help' => true]);

        $this->assertIsInt($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
