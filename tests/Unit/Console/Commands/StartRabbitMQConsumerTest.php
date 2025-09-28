<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\StartRabbitMQConsumer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StartRabbitMQConsumerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_start_rabbitmq_consumer_command()
    {
        $command = new StartRabbitMQConsumer();

        $this->assertInstanceOf(StartRabbitMQConsumer::class, $command);
    }

    /**
     * @test
     */
    public function it_can_execute_start_rabbitmq_consumer_command()
    {
        $command = new StartRabbitMQConsumer();
        $result = $command->handle();

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_start_rabbitmq_consumer_command_with_options()
    {
        $command = new StartRabbitMQConsumer();
        $result = $command->handle([
            '--queue' => 'game_events',
            '--timeout' => '60',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_start_rabbitmq_consumer_command_with_game_events_queue()
    {
        $command = new StartRabbitMQConsumer();
        $result = $command->handle([
            '--queue' => 'game_events',
            '--timeout' => '60',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_start_rabbitmq_consumer_command_with_notifications_queue()
    {
        $command = new StartRabbitMQConsumer();
        $result = $command->handle([
            '--queue' => 'notifications',
            '--timeout' => '60',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_start_rabbitmq_consumer_command_with_battles_queue()
    {
        $command = new StartRabbitMQConsumer();
        $result = $command->handle([
            '--queue' => 'battles',
            '--timeout' => '60',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_start_rabbitmq_consumer_command_with_movements_queue()
    {
        $command = new StartRabbitMQConsumer();
        $result = $command->handle([
            '--queue' => 'movements',
            '--timeout' => '60',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_start_rabbitmq_consumer_command_with_quests_queue()
    {
        $command = new StartRabbitMQConsumer();
        $result = $command->handle([
            '--queue' => 'quests',
            '--timeout' => '60',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_start_rabbitmq_consumer_command_with_achievements_queue()
    {
        $command = new StartRabbitMQConsumer();
        $result = $command->handle([
            '--queue' => 'achievements',
            '--timeout' => '60',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_start_rabbitmq_consumer_command_with_artifacts_queue()
    {
        $command = new StartRabbitMQConsumer();
        $result = $command->handle([
            '--queue' => 'artifacts',
            '--timeout' => '60',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_start_rabbitmq_consumer_command_with_verbose()
    {
        $command = new StartRabbitMQConsumer();
        $result = $command->handle([
            '--verbose' => true,
            '--queue' => 'game_events',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_start_rabbitmq_consumer_command_with_quiet()
    {
        $command = new StartRabbitMQConsumer();
        $result = $command->handle([
            '--quiet' => true,
            '--queue' => 'notifications',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_start_rabbitmq_consumer_command_with_no_interaction()
    {
        $command = new StartRabbitMQConsumer();
        $result = $command->handle([
            '--no-interaction' => true,
            '--queue' => 'battles',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_start_rabbitmq_consumer_command_with_force()
    {
        $command = new StartRabbitMQConsumer();
        $result = $command->handle([
            '--force' => true,
            '--queue' => 'movements',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_start_rabbitmq_consumer_command_with_help()
    {
        $command = new StartRabbitMQConsumer();
        $result = $command->handle(['--help' => true]);

        $this->assertIsInt($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
