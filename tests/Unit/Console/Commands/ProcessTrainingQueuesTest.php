<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\ProcessTrainingQueues;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProcessTrainingQueuesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_process_training_queues_command()
    {
        $command = new ProcessTrainingQueues();

        $this->assertInstanceOf(ProcessTrainingQueues::class, $command);
    }

    /**
     * @test
     */
    public function it_can_execute_process_training_queues_command()
    {
        $command = new ProcessTrainingQueues();
        $result = $command->handle();

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_process_training_queues_command_with_options()
    {
        $command = new ProcessTrainingQueues();
        $result = $command->handle([
            '--type' => 'all',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_process_training_queues_command_with_infantry()
    {
        $command = new ProcessTrainingQueues();
        $result = $command->handle([
            '--type' => 'infantry',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_process_training_queues_command_with_archers()
    {
        $command = new ProcessTrainingQueues();
        $result = $command->handle([
            '--type' => 'archers',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_process_training_queues_command_with_cavalry()
    {
        $command = new ProcessTrainingQueues();
        $result = $command->handle([
            '--type' => 'cavalry',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_process_training_queues_command_with_siege()
    {
        $command = new ProcessTrainingQueues();
        $result = $command->handle([
            '--type' => 'siege',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_process_training_queues_command_with_heroes()
    {
        $command = new ProcessTrainingQueues();
        $result = $command->handle([
            '--type' => 'heroes',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_process_training_queues_command_with_spies()
    {
        $command = new ProcessTrainingQueues();
        $result = $command->handle([
            '--type' => 'spies',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_process_training_queues_command_with_scouts()
    {
        $command = new ProcessTrainingQueues();
        $result = $command->handle([
            '--type' => 'scouts',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_process_training_queues_command_with_workers()
    {
        $command = new ProcessTrainingQueues();
        $result = $command->handle([
            '--type' => 'workers',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_process_training_queues_command_with_merchants()
    {
        $command = new ProcessTrainingQueues();
        $result = $command->handle([
            '--type' => 'merchants',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_process_training_queues_command_with_quiet()
    {
        $command = new ProcessTrainingQueues();
        $result = $command->handle([
            '--quiet' => true,
            '--type' => 'all',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_process_training_queues_command_with_no_interaction()
    {
        $command = new ProcessTrainingQueues();
        $result = $command->handle([
            '--no-interaction' => true,
            '--type' => 'all',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_process_training_queues_command_with_force()
    {
        $command = new ProcessTrainingQueues();
        $result = $command->handle([
            '--force' => true,
            '--type' => 'all',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_process_training_queues_command_with_help()
    {
        $command = new ProcessTrainingQueues();
        $result = $command->handle(['--help' => true]);

        $this->assertIsInt($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
