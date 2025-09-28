<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\GameSecurityCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GameSecurityCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_run_game_security()
    {
        $this
            ->artisan('game:security')
            ->expectsOutput('=== Game Security Report ===')
            ->expectsOutput('Starting game security scan...')
            ->expectsOutput('Vulnerability scan: COMPLETED')
            ->expectsOutput('Threat analysis: COMPLETED')
            ->expectsOutput('Game security scan completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_security_with_specific_checks()
    {
        $this
            ->artisan('game:security', ['--checks' => 'vulnerabilities,threats'])
            ->expectsOutput('=== Game Security Report ===')
            ->expectsOutput('Starting game security scan...')
            ->expectsOutput('Running security checks: vulnerabilities, threats')
            ->expectsOutput('Vulnerability scan: COMPLETED')
            ->expectsOutput('Threat analysis: COMPLETED')
            ->expectsOutput('Game security scan completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_security_with_verbose_output()
    {
        $this
            ->artisan('game:security', ['--verbose' => true])
            ->expectsOutput('=== Game Security Report ===')
            ->expectsOutput('Starting game security scan...')
            ->expectsOutput('=== Vulnerability Scan ===')
            ->expectsOutput('Scanning for security vulnerabilities...')
            ->expectsOutput('Checking for SQL injection vulnerabilities...')
            ->expectsOutput('Checking for XSS vulnerabilities...')
            ->expectsOutput('=== Threat Analysis ===')
            ->expectsOutput('Analyzing potential security threats...')
            ->expectsOutput('Checking for suspicious activities...')
            ->expectsOutput('Game security scan completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_security_with_reporting()
    {
        $this
            ->artisan('game:security', ['--report' => true])
            ->expectsOutput('=== Game Security Report ===')
            ->expectsOutput('Starting game security scan...')
            ->expectsOutput('Generating security report...')
            ->expectsOutput('Game security scan completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_security_with_export()
    {
        $this
            ->artisan('game:security', ['--export' => 'security.json'])
            ->expectsOutput('=== Game Security Report ===')
            ->expectsOutput('Starting game security scan...')
            ->expectsOutput('Exporting security data to: security.json')
            ->expectsOutput('Game security scan completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_security_with_scheduling()
    {
        $this
            ->artisan('game:security', ['--schedule' => 'daily'])
            ->expectsOutput('=== Game Security Report ===')
            ->expectsOutput('Starting game security scan...')
            ->expectsOutput('Scheduling daily security scan...')
            ->expectsOutput('Game security scan completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_security_with_cleanup()
    {
        $this
            ->artisan('game:security', ['--cleanup' => true])
            ->expectsOutput('=== Game Security Report ===')
            ->expectsOutput('Starting game security scan...')
            ->expectsOutput('Cleaning up security data...')
            ->expectsOutput('Game security scan completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_security_with_validation()
    {
        $this
            ->artisan('game:security', ['--validate' => true])
            ->expectsOutput('=== Game Security Report ===')
            ->expectsOutput('Starting game security scan...')
            ->expectsOutput('Validating security configuration...')
            ->expectsOutput('Game security scan completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_security_with_monitoring()
    {
        $this
            ->artisan('game:security', ['--monitor' => true])
            ->expectsOutput('=== Game Security Report ===')
            ->expectsOutput('Starting game security scan...')
            ->expectsOutput('Monitoring security scan progress...')
            ->expectsOutput('Game security scan completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_security_with_debugging()
    {
        $this
            ->artisan('game:security', ['--debug' => true])
            ->expectsOutput('=== Game Security Report ===')
            ->expectsOutput('Starting game security scan...')
            ->expectsOutput('Debug mode enabled')
            ->expectsOutput('Game security scan completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_security_with_dry_run()
    {
        $this
            ->artisan('game:security', ['--dry-run' => true])
            ->expectsOutput('=== Game Security Report ===')
            ->expectsOutput('Starting game security scan...')
            ->expectsOutput('Dry run mode enabled')
            ->expectsOutput('Game security scan completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_security_with_quiet_mode()
    {
        $this
            ->artisan('game:security', ['--quiet' => true])
            ->expectsOutput('=== Game Security Report ===')
            ->expectsOutput('Starting game security scan...')
            ->expectsOutput('Quiet mode enabled')
            ->expectsOutput('Game security scan completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_security_errors()
    {
        // Mock database error
        DB::shouldReceive('table')->andThrow(new \Exception('Database error'));

        $this
            ->artisan('game:security')
            ->expectsOutput('=== Game Security Report ===')
            ->expectsOutput('Error during security scan: Database error')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_run_security_with_retry_on_failure()
    {
        $this
            ->artisan('game:security', ['--retry' => 3])
            ->expectsOutput('=== Game Security Report ===')
            ->expectsOutput('Starting game security scan...')
            ->expectsOutput('Retry attempts set to: 3')
            ->expectsOutput('Game security scan completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_security_with_timeout()
    {
        $this
            ->artisan('game:security', ['--timeout' => '300'])
            ->expectsOutput('=== Game Security Report ===')
            ->expectsOutput('Starting game security scan...')
            ->expectsOutput('Timeout set to: 300 seconds')
            ->expectsOutput('Game security scan completed successfully!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new GameSecurityCommand();
        $this->assertEquals('game:security', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new GameSecurityCommand();
        $this->assertEquals('Perform comprehensive security scan and threat analysis', $command->getDescription());
    }
}
