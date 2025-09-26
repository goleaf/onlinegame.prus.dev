<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class FormatCodeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'code:format 
                            {--check : Check formatting without making changes}
                            {--path= : Specific path to format (default: all configured paths)}
                            {--diff : Show diff when checking formatting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Format PHP code using PHP CS Fixer with PSR-12 standards';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 PHP Code Formatter');
        $this->newLine();

        // Check if PHP CS Fixer is available
        if (! $this->isPhpCsFixerAvailable()) {
            $this->error('❌ PHP CS Fixer is not available. Please install it globally:');
            $this->line('   composer global require friendsofphp/php-cs-fixer');

            return 1;
        }

        $isCheck = $this->option('check');
        $path = $this->option('path');
        $showDiff = $this->option('diff');

        // Build the command
        $command = $this->buildCommand($isCheck, $path, $showDiff);

        if ($isCheck) {
            $this->info('🔍 Checking code formatting...');
        } else {
            $this->info('✨ Formatting code...');
        }

        $this->newLine();

        // Execute the command
        $result = Process::run($command);

        // Show output if there's any
        if ($result->output()) {
            $this->newLine();
            $this->line($result->output());
        }

        // Handle different exit codes
        $exitCode = $result->exitCode();

        if ($isCheck) {
            // For check mode, exit code 8 means files need formatting
            if ($exitCode === 8) {
                $this->warn('⚠️  Code formatting check found issues!');
                $this->line('   Some files need formatting. Run without --check to fix them.');

                return 1;
            } elseif ($exitCode === 0) {
                $this->info('✅ Code formatting check completed successfully!');
                $this->line('   All files are properly formatted.');

                return 0;
            } else {
                $this->error('❌ Code formatting check failed!');
                if ($result->errorOutput()) {
                    $this->line($result->errorOutput());
                }

                return $exitCode;
            }
        } else {
            // For format mode, exit code 0 means success
            if ($exitCode === 0) {
                $this->info('✅ Code formatting completed successfully!');
                $this->line('   All files have been formatted according to PSR-12 standards.');

                return 0;
            } else {
                $this->error('❌ Code formatting failed!');
                if ($result->errorOutput()) {
                    $this->line($result->errorOutput());
                }

                return $exitCode;
            }
        }
    }

    /**
     * Check if PHP CS Fixer is available
     */
    private function isPhpCsFixerAvailable(): bool
    {
        // Try to find php-cs-fixer in common locations
        $possiblePaths = [
            'php-cs-fixer',
            '/root/.config/composer/vendor/bin/php-cs-fixer',
            base_path('vendor/bin/php-cs-fixer'),
        ];

        foreach ($possiblePaths as $path) {
            $result = Process::run("which {$path}");
            if ($result->successful()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build the PHP CS Fixer command
     */
    private function buildCommand(bool $isCheck, ?string $path, bool $showDiff): string
    {
        // Find the correct php-cs-fixer path
        $phpCsFixerPath = $this->getPhpCsFixerPath();
        $command = $phpCsFixerPath;

        if ($isCheck) {
            $command .= ' fix --dry-run';

            if ($showDiff) {
                $command .= ' --diff';
            }
        } else {
            $command .= ' fix';
        }

        // Add path if specified
        if ($path) {
            $command .= ' ' . escapeshellarg($path);
        }

        return $command;
    }

    /**
     * Get the PHP CS Fixer executable path
     */
    private function getPhpCsFixerPath(): string
    {
        $possiblePaths = [
            'php-cs-fixer',
            '/root/.config/composer/vendor/bin/php-cs-fixer',
            base_path('vendor/bin/php-cs-fixer'),
        ];

        foreach ($possiblePaths as $path) {
            $result = Process::run("which {$path}");
            if ($result->successful()) {
                return $path;
            }
        }

        // Fallback to php-cs-fixer if none found
        return 'php-cs-fixer';
    }
}
