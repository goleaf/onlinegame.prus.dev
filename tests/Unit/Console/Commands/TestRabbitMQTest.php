<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\TestRabbitMQ;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class TestRabbitMQTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_test_rabbitmq_command()
    {
        $command = new TestRabbitMQ();

        $this->assertInstanceOf(TestRabbitMQ::class, $command);
    }

    /**
     * @test
     */
    public function it_can_execute_test_rabbitmq_command()
    {
        $command = new TestRabbitMQ();
        $result = $command->handle();

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_test_rabbitmq_command_with_options()
    {
        $command = new TestRabbitMQ();
        $result = $command->handle([
            '--type' => 'all',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_test_rabbitmq_command_with_connection_test()
    {
        $command = new TestRabbitMQ();
        $result = $command->handle([
            '--type' => 'connection',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_test_rabbitmq_command_with_queue_test()
    {
        $command = new TestRabbitMQ();
        $result = $command->handle([
            '--type' => 'queue',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_test_rabbitmq_command_with_exchange_test()
    {
        $command = new TestRabbitMQ();
        $result = $command->handle([
            '--type' => 'exchange',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_test_rabbitmq_command_with_message_test()
    {
        $command = new TestRabbitMQ();
        $result = $command->handle([
            '--type' => 'message',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_test_rabbitmq_command_with_consumer_test()
    {
        $command = new TestRabbitMQ();
        $result = $command->handle([
            '--type' => 'consumer',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_test_rabbitmq_command_with_producer_test()
    {
        $command = new TestRabbitMQ();
        $result = $command->handle([
            '--type' => 'producer',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_test_rabbitmq_command_with_performance_test()
    {
        $command = new TestRabbitMQ();
        $result = $command->handle([
            '--type' => 'performance',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_test_rabbitmq_command_with_reliability_test()
    {
        $command = new TestRabbitMQ();
        $result = $command->handle([
            '--type' => 'reliability',
            '--verbose' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_test_rabbitmq_command_with_quiet()
    {
        $command = new TestRabbitMQ();
        $result = $command->handle([
            '--quiet' => true,
            '--type' => 'all',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_test_rabbitmq_command_with_no_interaction()
    {
        $command = new TestRabbitMQ();
        $result = $command->handle([
            '--no-interaction' => true,
            '--type' => 'all',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_test_rabbitmq_command_with_force()
    {
        $command = new TestRabbitMQ();
        $result = $command->handle([
            '--force' => true,
            '--type' => 'all',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_test_rabbitmq_command_with_help()
    {
        $command = new TestRabbitMQ();
        $result = $command->handle(['--help' => true]);

        $this->assertIsInt($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
