<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\BassetOptimizeCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BassetOptimizeCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_optimize_assets()
    {
        Storage::fake('public');

        // Create test CSS and JS files
        Storage::disk('public')->put('css/app.css', 'body { margin: 0; padding: 0; }');
        Storage::disk('public')->put('js/app.js', 'console.log("Hello World");');

        $this
            ->artisan('basset:optimize')
            ->expectsOutput('Starting asset optimization...')
            ->expectsOutput('=== Asset Optimization Report ===')
            ->expectsOutput('CSS Files Processed: ')
            ->expectsOutput('JS Files Processed: ')
            ->expectsOutput('Total Size Before: ')
            ->expectsOutput('Total Size After: ')
            ->expectsOutput('Space Saved: ')
            ->expectsOutput('Asset optimization completed!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_optimize_css_only()
    {
        Storage::fake('public');
        Storage::disk('public')->put('css/app.css', 'body { margin: 0; padding: 0; }');

        $this
            ->artisan('basset:optimize', ['--css-only' => true])
            ->expectsOutput('Starting asset optimization...')
            ->expectsOutput('Optimizing CSS files only...')
            ->expectsOutput('=== Asset Optimization Report ===')
            ->expectsOutput('CSS Files Processed: ')
            ->expectsOutput('Asset optimization completed!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_optimize_js_only()
    {
        Storage::fake('public');
        Storage::disk('public')->put('js/app.js', 'console.log("Hello World");');

        $this
            ->artisan('basset:optimize', ['--js-only' => true])
            ->expectsOutput('Starting asset optimization...')
            ->expectsOutput('Optimizing JS files only...')
            ->expectsOutput('=== Asset Optimization Report ===')
            ->expectsOutput('JS Files Processed: ')
            ->expectsOutput('Asset optimization completed!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_optimize_with_gzip_compression()
    {
        Storage::fake('public');
        Storage::disk('public')->put('css/app.css', 'body { margin: 0; padding: 0; }');
        Storage::disk('public')->put('js/app.js', 'console.log("Hello World");');

        $this
            ->artisan('basset:optimize', ['--gzip' => true])
            ->expectsOutput('Starting asset optimization...')
            ->expectsOutput('Enabling Gzip compression...')
            ->expectsOutput('=== Asset Optimization Report ===')
            ->expectsOutput('CSS Files Processed: ')
            ->expectsOutput('JS Files Processed: ')
            ->expectsOutput('Gzip Files Created: ')
            ->expectsOutput('Asset optimization completed!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_optimize_with_versioning()
    {
        Storage::fake('public');
        Storage::disk('public')->put('css/app.css', 'body { margin: 0; padding: 0; }');
        Storage::disk('public')->put('js/app.js', 'console.log("Hello World");');

        $this
            ->artisan('basset:optimize', ['--version' => true])
            ->expectsOutput('Starting asset optimization...')
            ->expectsOutput('Enabling asset versioning...')
            ->expectsOutput('=== Asset Optimization Report ===')
            ->expectsOutput('CSS Files Processed: ')
            ->expectsOutput('JS Files Processed: ')
            ->expectsOutput('Versioned Files Created: ')
            ->expectsOutput('Asset optimization completed!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_optimize_with_all_options()
    {
        Storage::fake('public');
        Storage::disk('public')->put('css/app.css', 'body { margin: 0; padding: 0; }');
        Storage::disk('public')->put('js/app.js', 'console.log("Hello World");');

        $this
            ->artisan('basset:optimize', [
                '--gzip' => true,
                '--version' => true,
                '--clean' => true,
            ])
            ->expectsOutput('Starting asset optimization...')
            ->expectsOutput('Enabling Gzip compression...')
            ->expectsOutput('Enabling asset versioning...')
            ->expectsOutput('Cleaning old assets...')
            ->expectsOutput('=== Asset Optimization Report ===')
            ->expectsOutput('CSS Files Processed: ')
            ->expectsOutput('JS Files Processed: ')
            ->expectsOutput('Gzip Files Created: ')
            ->expectsOutput('Versioned Files Created: ')
            ->expectsOutput('Old Files Cleaned: ')
            ->expectsOutput('Asset optimization completed!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_no_assets_found()
    {
        Storage::fake('public');

        $this
            ->artisan('basset:optimize')
            ->expectsOutput('Starting asset optimization...')
            ->expectsOutput('No assets found to optimize.')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_show_detailed_progress()
    {
        Storage::fake('public');
        Storage::disk('public')->put('css/app.css', 'body { margin: 0; padding: 0; }');
        Storage::disk('public')->put('js/app.js', 'console.log("Hello World");');

        $this
            ->artisan('basset:optimize', ['--verbose' => true])
            ->expectsOutput('Starting asset optimization...')
            ->expectsOutput('Processing CSS files...')
            ->expectsOutput('Processing JS files...')
            ->expectsOutput('=== Asset Optimization Report ===')
            ->expectsOutput('Asset optimization completed!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_clean_old_assets()
    {
        Storage::fake('public');
        Storage::disk('public')->put('css/app.css', 'body { margin: 0; padding: 0; }');
        Storage::disk('public')->put('css/app.old.css', 'body { margin: 0; }');

        $this
            ->artisan('basset:optimize', ['--clean' => true])
            ->expectsOutput('Starting asset optimization...')
            ->expectsOutput('Cleaning old assets...')
            ->expectsOutput('=== Asset Optimization Report ===')
            ->expectsOutput('Old Files Cleaned: ')
            ->expectsOutput('Asset optimization completed!')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new BassetOptimizeCommand();
        $this->assertEquals('basset:optimize', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new BassetOptimizeCommand();
        $this->assertEquals('Optimize and compress CSS/JS assets using Basset', $command->getDescription());
    }
}
