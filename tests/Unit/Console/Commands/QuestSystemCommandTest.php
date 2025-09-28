<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\QuestSystemCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class QuestSystemCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_quest_system_command()
    {
        $command = new QuestSystemCommand();

        $this->assertInstanceOf(QuestSystemCommand::class, $command);
    }

    /**
     * @test
     */
    public function it_can_execute_quest_system_command()
    {
        $command = new QuestSystemCommand();
        $result = $command->handle();

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_quest_system_command_with_options()
    {
        $command = new QuestSystemCommand();
        $result = $command->handle([
            '--action' => 'create',
            '--type' => 'tutorial',
            '--player' => '1',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_quest_system_command_with_create_action()
    {
        $command = new QuestSystemCommand();
        $result = $command->handle([
            '--action' => 'create',
            '--type' => 'daily',
            '--player' => '1',
            '--title' => 'Test Quest',
            '--description' => 'This is a test quest',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_quest_system_command_with_list_action()
    {
        $command = new QuestSystemCommand();
        $result = $command->handle([
            '--action' => 'list',
            '--player' => '1',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_quest_system_command_with_accept_action()
    {
        $command = new QuestSystemCommand();
        $result = $command->handle([
            '--action' => 'accept',
            '--quest' => '1',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_quest_system_command_with_complete_action()
    {
        $command = new QuestSystemCommand();
        $result = $command->handle([
            '--action' => 'complete',
            '--quest' => '1',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_quest_system_command_with_abandon_action()
    {
        $command = new QuestSystemCommand();
        $result = $command->handle([
            '--action' => 'abandon',
            '--quest' => '1',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_quest_system_command_with_reward_action()
    {
        $command = new QuestSystemCommand();
        $result = $command->handle([
            '--action' => 'reward',
            '--quest' => '1',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_quest_system_command_with_reset_action()
    {
        $command = new QuestSystemCommand();
        $result = $command->handle([
            '--action' => 'reset',
            '--quest' => '1',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_quest_system_command_with_delete_action()
    {
        $command = new QuestSystemCommand();
        $result = $command->handle([
            '--action' => 'delete',
            '--quest' => '1',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_quest_system_command_with_verbose()
    {
        $command = new QuestSystemCommand();
        $result = $command->handle([
            '--verbose' => true,
            '--action' => 'list',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_quest_system_command_with_quiet()
    {
        $command = new QuestSystemCommand();
        $result = $command->handle([
            '--quiet' => true,
            '--action' => 'list',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_quest_system_command_with_no_interaction()
    {
        $command = new QuestSystemCommand();
        $result = $command->handle([
            '--no-interaction' => true,
            '--action' => 'create',
            '--type' => 'tutorial',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_quest_system_command_with_force()
    {
        $command = new QuestSystemCommand();
        $result = $command->handle([
            '--force' => true,
            '--action' => 'delete',
            '--quest' => '1',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_quest_system_command_with_help()
    {
        $command = new QuestSystemCommand();
        $result = $command->handle(['--help' => true]);

        $this->assertIsInt($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
