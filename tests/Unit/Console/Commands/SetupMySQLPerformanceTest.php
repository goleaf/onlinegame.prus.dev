<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\SetupMySQLPerformance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SetupMySQLPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_setup_mysql_performance_command()
    {
        $command = new SetupMySQLPerformance();

        $this->assertInstanceOf(SetupMySQLPerformance::class, $command);
    }

    /**
     * @test
     */
    public function it_can_execute_setup_mysql_performance_command()
    {
        $command = new SetupMySQLPerformance();
        $result = $command->handle();

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_setup_mysql_performance_command_with_options()
    {
        $command = new SetupMySQLPerformance();
        $result = $command->handle([
            '--type' => 'all',
            '--force' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_setup_mysql_performance_command_with_indexes()
    {
        $command = new SetupMySQLPerformance();
        $result = $command->handle([
            '--type' => 'indexes',
            '--force' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_setup_mysql_performance_command_with_foreign_keys()
    {
        $command = new SetupMySQLPerformance();
        $result = $command->handle([
            '--type' => 'foreign_keys',
            '--force' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_setup_mysql_performance_command_with_constraints()
    {
        $command = new SetupMySQLPerformance();
        $result = $command->handle([
            '--type' => 'constraints',
            '--force' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_setup_mysql_performance_command_with_triggers()
    {
        $command = new SetupMySQLPerformance();
        $result = $command->handle([
            '--type' => 'triggers',
            '--force' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_setup_mysql_performance_command_with_views()
    {
        $command = new SetupMySQLPerformance();
        $result = $command->handle([
            '--type' => 'views',
            '--force' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_setup_mysql_performance_command_with_procedures()
    {
        $command = new SetupMySQLPerformance();
        $result = $command->handle([
            '--type' => 'procedures',
            '--force' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_setup_mysql_performance_command_with_functions()
    {
        $command = new SetupMySQLPerformance();
        $result = $command->handle([
            '--type' => 'functions',
            '--force' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_setup_mysql_performance_command_with_events()
    {
        $command = new SetupMySQLPerformance();
        $result = $command->handle([
            '--type' => 'events',
            '--force' => true,
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_setup_mysql_performance_command_with_verbose()
    {
        $command = new SetupMySQLPerformance();
        $result = $command->handle([
            '--verbose' => true,
            '--type' => 'all',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_setup_mysql_performance_command_with_quiet()
    {
        $command = new SetupMySQLPerformance();
        $result = $command->handle([
            '--quiet' => true,
            '--type' => 'all',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_setup_mysql_performance_command_with_no_interaction()
    {
        $command = new SetupMySQLPerformance();
        $result = $command->handle([
            '--no-interaction' => true,
            '--type' => 'all',
        ]);

        $this->assertIsInt($result);
    }

    /**
     * @test
     */
    public function it_can_execute_setup_mysql_performance_command_with_help()
    {
        $command = new SetupMySQLPerformance();
        $result = $command->handle(['--help' => true]);

        $this->assertIsInt($result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
