<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\GameTestCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameTestCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_run_game_tests()
    {
        $this
            ->artisan('game:test')
            ->expectsOutput('=== Game System Tests ===')
            ->expectsOutput('Running game functionality tests...')
            ->expectsOutput('Database connectivity: OK')
            ->expectsOutput('Game services: OK')
            ->expectsOutput('Battle system: OK')
            ->expectsOutput('Resource system: OK')
            ->expectsOutput('Alliance system: OK')
            ->expectsOutput('All tests completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_specific_test_category()
    {
        $this
            ->artisan('game:test', ['--category' => 'battle'])
            ->expectsOutput('=== Game System Tests ===')
            ->expectsOutput('Running battle system tests...')
            ->expectsOutput('Battle calculation: OK')
            ->expectsOutput('Troop movement: OK')
            ->expectsOutput('Defense calculation: OK')
            ->expectsOutput('Battle tests completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_tests_with_verbose_output()
    {
        $this
            ->artisan('game:test', ['--verbose' => true])
            ->expectsOutput('=== Game System Tests ===')
            ->expectsOutput('Running game functionality tests...')
            ->expectsOutput('Testing database connectivity...')
            ->expectsOutput('✓ Database connection established')
            ->expectsOutput('Testing game services...')
            ->expectsOutput('✓ ResourceProductionService working')
            ->expectsOutput('✓ CombatService working')
            ->expectsOutput('✓ AllianceService working')
            ->expectsOutput('Testing battle system...')
            ->expectsOutput('✓ Battle calculations correct')
            ->expectsOutput('✓ Troop movements valid')
            ->expectsOutput('✓ Defense calculations accurate')
            ->expectsOutput('All tests completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_tests_with_performance_metrics()
    {
        $this
            ->artisan('game:test', ['--performance' => true])
            ->expectsOutput('=== Game System Tests ===')
            ->expectsOutput('Running game functionality tests...')
            ->expectsOutput('=== Performance Metrics ===')
            ->expectsOutput('Database query time: ')
            ->expectsOutput('Battle calculation time: ')
            ->expectsOutput('Resource calculation time: ')
            ->expectsOutput('Memory usage: ')
            ->expectsOutput('All tests completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_stress_tests()
    {
        $this
            ->artisan('game:test', ['--stress' => true])
            ->expectsOutput('=== Game System Tests ===')
            ->expectsOutput('Running stress tests...')
            ->expectsOutput('Testing with 1000 concurrent battles...')
            ->expectsOutput('Testing with 10000 resource calculations...')
            ->expectsOutput('Testing with 500 alliance operations...')
            ->expectsOutput('Stress tests completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_tests_with_mock_data()
    {
        $this
            ->artisan('game:test', ['--mock' => true])
            ->expectsOutput('=== Game System Tests ===')
            ->expectsOutput('Running tests with mock data...')
            ->expectsOutput('Creating test players...')
            ->expectsOutput('Creating test villages...')
            ->expectsOutput('Creating test alliances...')
            ->expectsOutput('Running game tests with mock data...')
            ->expectsOutput('All tests completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_tests_with_cleanup()
    {
        $this
            ->artisan('game:test', ['--cleanup' => true])
            ->expectsOutput('=== Game System Tests ===')
            ->expectsOutput('Running game functionality tests...')
            ->expectsOutput('Cleaning up test data...')
            ->expectsOutput('Removing test players...')
            ->expectsOutput('Removing test villages...')
            ->expectsOutput('Removing test alliances...')
            ->expectsOutput('All tests completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_tests_with_report_generation()
    {
        $this
            ->artisan('game:test', ['--report' => true])
            ->expectsOutput('=== Game System Tests ===')
            ->expectsOutput('Running game functionality tests...')
            ->expectsOutput('Generating test report...')
            ->expectsOutput('Report saved to: ')
            ->expectsOutput('All tests completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_tests_with_parallel_execution()
    {
        $this
            ->artisan('game:test', ['--parallel' => true])
            ->expectsOutput('=== Game System Tests ===')
            ->expectsOutput('Running tests in parallel...')
            ->expectsOutput('Parallel execution enabled')
            ->expectsOutput('All tests completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_tests_with_timeout()
    {
        $this
            ->artisan('game:test', ['--timeout' => '300'])
            ->expectsOutput('=== Game System Tests ===')
            ->expectsOutput('Running game functionality tests...')
            ->expectsOutput('Timeout set to: 300 seconds')
            ->expectsOutput('All tests completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_tests_with_memory_limit()
    {
        $this
            ->artisan('game:test', ['--memory-limit' => '512M'])
            ->expectsOutput('=== Game System Tests ===')
            ->expectsOutput('Running game functionality tests...')
            ->expectsOutput('Memory limit set to: 512M')
            ->expectsOutput('All tests completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_tests_with_environment_check()
    {
        $this
            ->artisan('game:test', ['--env-check' => true])
            ->expectsOutput('=== Game System Tests ===')
            ->expectsOutput('Checking environment...')
            ->expectsOutput('PHP version: OK')
            ->expectsOutput('Laravel version: OK')
            ->expectsOutput('Database: OK')
            ->expectsOutput('Cache: OK')
            ->expectsOutput('Queue: OK')
            ->expectsOutput('Running game functionality tests...')
            ->expectsOutput('All tests completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_tests_with_dependency_check()
    {
        $this
            ->artisan('game:test', ['--deps' => true])
            ->expectsOutput('=== Game System Tests ===')
            ->expectsOutput('Checking dependencies...')
            ->expectsOutput('Game services: OK')
            ->expectsOutput('External APIs: OK')
            ->expectsOutput('Third-party packages: OK')
            ->expectsOutput('Running game functionality tests...')
            ->expectsOutput('All tests completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_test_failures_gracefully()
    {
        // Mock a test failure
        $this
            ->artisan('game:test')
            ->expectsOutput('=== Game System Tests ===')
            ->expectsOutput('Running game functionality tests...')
            ->expectsOutput('Database connectivity: FAILED')
            ->expectsOutput('Error: Database connection failed')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_run_tests_with_retry_on_failure()
    {
        $this
            ->artisan('game:test', ['--retry' => 3])
            ->expectsOutput('=== Game System Tests ===')
            ->expectsOutput('Running game functionality tests...')
            ->expectsOutput('Retry attempts set to: 3')
            ->expectsOutput('All tests completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_tests_with_coverage_report()
    {
        $this
            ->artisan('game:test', ['--coverage' => true])
            ->expectsOutput('=== Game System Tests ===')
            ->expectsOutput('Running game functionality tests...')
            ->expectsOutput('Generating coverage report...')
            ->expectsOutput('Coverage report saved to: ')
            ->expectsOutput('All tests completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new GameTestCommand();
        $this->assertEquals('game:test', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new GameTestCommand();
        $this->assertEquals('Run comprehensive game system tests', $command->getDescription());
    }
}
