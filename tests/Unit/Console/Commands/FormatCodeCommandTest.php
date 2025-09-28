<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\FormatCodeCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class FormatCodeCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_format_all_code()
    {
        Process::fake([
            'vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --verbose --diff' => Process::result('Fixed 5 files'),
        ]);

        $this
            ->artisan('code:format')
            ->expectsOutput('Starting code formatting...')
            ->expectsOutput('Running PHP CS Fixer...')
            ->expectsOutput('Code formatting completed!')
            ->expectsOutput('Fixed 5 files')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_format_specific_path()
    {
        Process::fake([
            'vendor/bin/php-cs-fixer fix app/Models --config=.php-cs-fixer.php --verbose --diff' => Process::result('Fixed 3 files'),
        ]);

        $this
            ->artisan('code:format', ['--path' => 'app/Models'])
            ->expectsOutput('Starting code formatting...')
            ->expectsOutput('Formatting path: app/Models')
            ->expectsOutput('Running PHP CS Fixer...')
            ->expectsOutput('Code formatting completed!')
            ->expectsOutput('Fixed 3 files')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_dry_run_format()
    {
        Process::fake([
            'vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --verbose --diff --dry-run' => Process::result('Would fix 2 files'),
        ]);

        $this
            ->artisan('code:format', ['--dry-run' => true])
            ->expectsOutput('Starting code formatting...')
            ->expectsOutput('DRY RUN MODE - No files will be modified')
            ->expectsOutput('Running PHP CS Fixer...')
            ->expectsOutput('Code formatting completed!')
            ->expectsOutput('Would fix 2 files')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_format_with_custom_config()
    {
        Process::fake([
            'vendor/bin/php-cs-fixer fix --config=custom-config.php --verbose --diff' => Process::result('Fixed 1 file'),
        ]);

        $this
            ->artisan('code:format', ['--config' => 'custom-config.php'])
            ->expectsOutput('Starting code formatting...')
            ->expectsOutput('Using custom config: custom-config.php')
            ->expectsOutput('Running PHP CS Fixer...')
            ->expectsOutput('Code formatting completed!')
            ->expectsOutput('Fixed 1 file')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_format_with_specific_rules()
    {
        Process::fake([
            'vendor/bin/php-cs-fixer fix --rules=@PSR12,array_syntax --verbose --diff' => Process::result('Fixed 4 files'),
        ]);

        $this
            ->artisan('code:format', ['--rules' => '@PSR12,array_syntax'])
            ->expectsOutput('Starting code formatting...')
            ->expectsOutput('Using custom rules: @PSR12,array_syntax')
            ->expectsOutput('Running PHP CS Fixer...')
            ->expectsOutput('Code formatting completed!')
            ->expectsOutput('Fixed 4 files')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_format_without_diff()
    {
        Process::fake([
            'vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --verbose' => Process::result('Fixed 2 files'),
        ]);

        $this
            ->artisan('code:format', ['--no-diff' => true])
            ->expectsOutput('Starting code formatting...')
            ->expectsOutput('Running PHP CS Fixer...')
            ->expectsOutput('Code formatting completed!')
            ->expectsOutput('Fixed 2 files')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_format_with_cache_clear()
    {
        Process::fake([
            'vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --verbose --diff' => Process::result('Fixed 3 files'),
        ]);

        $this
            ->artisan('code:format', ['--clear-cache' => true])
            ->expectsOutput('Starting code formatting...')
            ->expectsOutput('Clearing PHP CS Fixer cache...')
            ->expectsOutput('Running PHP CS Fixer...')
            ->expectsOutput('Code formatting completed!')
            ->expectsOutput('Fixed 3 files')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_php_cs_fixer_not_found()
    {
        Process::fake([
            'vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --verbose --diff' => Process::result('', 127),
        ]);

        $this
            ->artisan('code:format')
            ->expectsOutput('Starting code formatting...')
            ->expectsOutput('Running PHP CS Fixer...')
            ->expectsOutput('Error: PHP CS Fixer not found. Please install it via Composer.')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_php_cs_fixer_error()
    {
        Process::fake([
            'vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --verbose --diff' => Process::result('Syntax error in file', 1),
        ]);

        $this
            ->artisan('code:format')
            ->expectsOutput('Starting code formatting...')
            ->expectsOutput('Running PHP CS Fixer...')
            ->expectsOutput('PHP CS Fixer encountered an error:')
            ->expectsOutput('Syntax error in file')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_can_format_with_progress_indicator()
    {
        Process::fake([
            'vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --verbose --diff' => Process::result('Fixed 10 files'),
        ]);

        $this
            ->artisan('code:format', ['--progress' => true])
            ->expectsOutput('Starting code formatting...')
            ->expectsOutput('Running PHP CS Fixer...')
            ->expectsOutput('Code formatting completed!')
            ->expectsOutput('Fixed 10 files')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_format_with_stop_on_violation()
    {
        Process::fake([
            'vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --verbose --diff --stop-on-violation' => Process::result('Stopped on first violation'),
        ]);

        $this
            ->artisan('code:format', ['--stop-on-violation' => true])
            ->expectsOutput('Starting code formatting...')
            ->expectsOutput('Stopping on first violation...')
            ->expectsOutput('Running PHP CS Fixer...')
            ->expectsOutput('Code formatting completed!')
            ->expectsOutput('Stopped on first violation')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_format_and_show_statistics()
    {
        Process::fake([
            'vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.php --verbose --diff' => Process::result("Fixed 5 files\nTime: 2.5s\nMemory: 12MB"),
        ]);

        $this
            ->artisan('code:format', ['--stats' => true])
            ->expectsOutput('Starting code formatting...')
            ->expectsOutput('Running PHP CS Fixer...')
            ->expectsOutput('=== Formatting Statistics ===')
            ->expectsOutput('Code formatting completed!')
            ->expectsOutput("Fixed 5 files\nTime: 2.5s\nMemory: 12MB")
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new FormatCodeCommand();
        $this->assertEquals('code:format', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new FormatCodeCommand();
        $this->assertEquals('Format PHP code using PHP CS Fixer', $command->getDescription());
    }
}
