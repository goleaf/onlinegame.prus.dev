<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\TournamentCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TournamentCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_tournament_command()
    {
        $command = new TournamentCommand();

        $this->assertInstanceOf(TournamentCommand::class, $command);
    }

    /**
     * @test
     */
    public function it_can_execute_tournament_command()
    {
        $command = new TournamentCommand();
        $result = $command->handle();

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_tournament_command_with_options()
    {
        $command = new TournamentCommand();
        $result = $command->handle([
            '--action' => 'create',
            '--type' => 'battle',
            '--name' => 'Test Tournament',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_tournament_command_with_create_action()
    {
        $command = new TournamentCommand();
        $result = $command->handle([
            '--action' => 'create',
            '--type' => 'battle',
            '--name' => 'Test Tournament',
            '--description' => 'This is a test tournament',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_tournament_command_with_list_action()
    {
        $command = new TournamentCommand();
        $result = $command->handle([
            '--action' => 'list',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_tournament_command_with_join_action()
    {
        $command = new TournamentCommand();
        $result = $command->handle([
            '--action' => 'join',
            '--tournament' => '1',
            '--player' => '1',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_tournament_command_with_leave_action()
    {
        $command = new TournamentCommand();
        $result = $command->handle([
            '--action' => 'leave',
            '--tournament' => '1',
            '--player' => '1',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_tournament_command_with_start_action()
    {
        $command = new TournamentCommand();
        $result = $command->handle([
            '--action' => 'start',
            '--tournament' => '1',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_tournament_command_with_end_action()
    {
        $command = new TournamentCommand();
        $result = $command->handle([
            '--action' => 'end',
            '--tournament' => '1',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_tournament_command_with_cancel_action()
    {
        $command = new TournamentCommand();
        $result = $command->handle([
            '--action' => 'cancel',
            '--tournament' => '1',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_tournament_command_with_results_action()
    {
        $command = new TournamentCommand();
        $result = $command->handle([
            '--action' => 'results',
            '--tournament' => '1',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_tournament_command_with_verbose()
    {
        $command = new TournamentCommand();
        $result = $command->handle([
            '--verbose' => true,
            '--action' => 'list',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_tournament_command_with_quiet()
    {
        $command = new TournamentCommand();
        $result = $command->handle([
            '--quiet' => true,
            '--action' => 'list',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_tournament_command_with_no_interaction()
    {
        $command = new TournamentCommand();
        $result = $command->handle([
            '--no-interaction' => true,
            '--action' => 'create',
            '--type' => 'battle',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_tournament_command_with_force()
    {
        $command = new TournamentCommand();
        $result = $command->handle([
            '--force' => true,
            '--action' => 'cancel',
            '--tournament' => '1',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_tournament_command_with_help()
    {
        $command = new TournamentCommand();
        $result = $command->handle(['--help' => true]);

        $this->assertIsInt($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
