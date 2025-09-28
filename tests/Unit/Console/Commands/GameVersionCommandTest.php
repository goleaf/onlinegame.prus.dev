<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\GameVersionCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GameVersionCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_show_current_version()
    {
        $this
            ->artisan('game:version')
            ->expectsOutput('=== Game Version Information ===')
            ->expectsOutput('Current Version: ')
            ->expectsOutput('Build Number: ')
            ->expectsOutput('Release Date: ')
            ->expectsOutput('Git Commit: ')
            ->expectsOutput('Environment: ')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_show_detailed_version_info()
    {
        $this
            ->artisan('game:version', ['--detailed' => true])
            ->expectsOutput('=== Detailed Game Version Information ===')
            ->expectsOutput('Current Version: ')
            ->expectsOutput('Build Number: ')
            ->expectsOutput('Release Date: ')
            ->expectsOutput('Git Commit: ')
            ->expectsOutput('Git Branch: ')
            ->expectsOutput('Environment: ')
            ->expectsOutput('PHP Version: ')
            ->expectsOutput('Laravel Version: ')
            ->expectsOutput('Database Version: ')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_check_for_updates()
    {
        $this
            ->artisan('game:version', ['--check-updates' => true])
            ->expectsOutput('=== Game Version Information ===')
            ->expectsOutput('Checking for updates...')
            ->expectsOutput('Current Version: ')
            ->expectsOutput('Latest Version: ')
            ->expectsOutput('Update Available: ')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_show_version_history()
    {
        $this
            ->artisan('game:version', ['--history' => true])
            ->expectsOutput('=== Game Version Information ===')
            ->expectsOutput('Current Version: ')
            ->expectsOutput('=== Version History ===')
            ->expectsOutput('Version ')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_show_changelog()
    {
        $this
            ->artisan('game:version', ['--changelog' => true])
            ->expectsOutput('=== Game Version Information ===')
            ->expectsOutput('Current Version: ')
            ->expectsOutput('=== Changelog ===')
            ->expectsOutput('Version ')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_export_version_info()
    {
        $this
            ->artisan('game:version', ['--export' => 'version.json'])
            ->expectsOutput('=== Game Version Information ===')
            ->expectsOutput('Current Version: ')
            ->expectsOutput('Version information exported to: version.json')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_show_component_versions()
    {
        $this
            ->artisan('game:version', ['--components' => true])
            ->expectsOutput('=== Game Version Information ===')
            ->expectsOutput('Current Version: ')
            ->expectsOutput('=== Component Versions ===')
            ->expectsOutput('Core Game Engine: ')
            ->expectsOutput('Battle System: ')
            ->expectsOutput('Resource System: ')
            ->expectsOutput('Alliance System: ')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_validate_version_consistency()
    {
        $this
            ->artisan('game:version', ['--validate' => true])
            ->expectsOutput('=== Game Version Information ===')
            ->expectsOutput('Current Version: ')
            ->expectsOutput('Validating version consistency...')
            ->expectsOutput('Version validation: ')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_show_build_information()
    {
        $this
            ->artisan('game:version', ['--build' => true])
            ->expectsOutput('=== Game Version Information ===')
            ->expectsOutput('Current Version: ')
            ->expectsOutput('=== Build Information ===')
            ->expectsOutput('Build Number: ')
            ->expectsOutput('Build Date: ')
            ->expectsOutput('Build Machine: ')
            ->expectsOutput('Build User: ')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_show_deployment_info()
    {
        $this
            ->artisan('game:version', ['--deployment' => true])
            ->expectsOutput('=== Game Version Information ===')
            ->expectsOutput('Current Version: ')
            ->expectsOutput('=== Deployment Information ===')
            ->expectsOutput('Deployed At: ')
            ->expectsOutput('Deployed By: ')
            ->expectsOutput('Deployment Method: ')
            ->expectsOutput('Server Environment: ')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_show_performance_metrics()
    {
        $this
            ->artisan('game:version', ['--performance' => true])
            ->expectsOutput('=== Game Version Information ===')
            ->expectsOutput('Current Version: ')
            ->expectsOutput('=== Performance Metrics ===')
            ->expectsOutput('Memory Usage: ')
            ->expectsOutput('CPU Usage: ')
            ->expectsOutput('Database Connections: ')
            ->expectsOutput('Cache Hit Rate: ')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_show_security_info()
    {
        $this
            ->artisan('game:version', ['--security' => true])
            ->expectsOutput('=== Game Version Information ===')
            ->expectsOutput('Current Version: ')
            ->expectsOutput('=== Security Information ===')
            ->expectsOutput('Security Level: ')
            ->expectsOutput('Last Security Update: ')
            ->expectsOutput('Security Patches: ')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_missing_version_file()
    {
        // Mock missing version file
        File::shouldReceive('exists')->andReturn(false);

        $this
            ->artisan('game:version')
            ->expectsOutput('=== Game Version Information ===')
            ->expectsOutput('Warning: Version file not found')
            ->expectsOutput('Using default version information')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_compare_versions()
    {
        $this
            ->artisan('game:version', [
                '--compare' => '1.0.0',
                '--target' => '1.1.0',
            ])
            ->expectsOutput('=== Game Version Information ===')
            ->expectsOutput('Current Version: ')
            ->expectsOutput('=== Version Comparison ===')
            ->expectsOutput('Source Version: 1.0.0')
            ->expectsOutput('Target Version: 1.1.0')
            ->expectsOutput('Comparison Result: ')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_show_license_info()
    {
        $this
            ->artisan('game:version', ['--license' => true])
            ->expectsOutput('=== Game Version Information ===')
            ->expectsOutput('Current Version: ')
            ->expectsOutput('=== License Information ===')
            ->expectsOutput('License Type: ')
            ->expectsOutput('License Expires: ')
            ->expectsOutput('License Holder: ')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new GameVersionCommand();
        $this->assertEquals('game:version', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new GameVersionCommand();
        $this->assertEquals('Display game version information', $command->getDescription());
    }
}
