<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\MobileOptimizationCommand;
use App\Services\GamePerformanceOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileOptimizationCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_optimize_for_mobile()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock->shouldReceive('optimizeForMobile')->andReturn([
                'images_optimized' => 50,
                'css_minified' => 10,
                'js_minified' => 15,
                'total_size_reduced' => '2.5MB',
            ]);
        });

        $this
            ->artisan('mobile:optimize')
            ->expectsOutput('📱 Mobile Optimization Tool')
            ->expectsOutput('Optimizing for mobile devices...')
            ->expectsOutput('Images optimized: 50')
            ->expectsOutput('CSS files minified: 10')
            ->expectsOutput('JS files minified: 15')
            ->expectsOutput('Total size reduced: 2.5MB')
            ->expectsOutput('Mobile optimization completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_optimize_images()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock
                ->shouldReceive('optimizeImages')
                ->with(['jpg', 'png', 'webp'])
                ->andReturn([
                    'optimized' => 25,
                    'size_reduced' => '1.2MB',
                    'formats_converted' => ['jpg' => 'webp'],
                ]);
        });

        $this
            ->artisan('mobile:optimize', ['--images' => true])
            ->expectsOutput('📱 Mobile Optimization Tool')
            ->expectsOutput('Optimizing images...')
            ->expectsOutput('Images optimized: 25')
            ->expectsOutput('Size reduced: 1.2MB')
            ->expectsOutput('Formats converted: jpg -> webp')
            ->expectsOutput('Image optimization completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_optimize_css()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock
                ->shouldReceive('optimizeCss')
                ->andReturn([
                    'minified' => 8,
                    'size_reduced' => '500KB',
                    'unused_css_removed' => '200KB',
                ]);
        });

        $this
            ->artisan('mobile:optimize', ['--css' => true])
            ->expectsOutput('📱 Mobile Optimization Tool')
            ->expectsOutput('Optimizing CSS...')
            ->expectsOutput('CSS files minified: 8')
            ->expectsOutput('Size reduced: 500KB')
            ->expectsOutput('Unused CSS removed: 200KB')
            ->expectsOutput('CSS optimization completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_optimize_javascript()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock
                ->shouldReceive('optimizeJavascript')
                ->andReturn([
                    'minified' => 12,
                    'size_reduced' => '800KB',
                    'bundled' => 5,
                ]);
        });

        $this
            ->artisan('mobile:optimize', ['--js' => true])
            ->expectsOutput('📱 Mobile Optimization Tool')
            ->expectsOutput('Optimizing JavaScript...')
            ->expectsOutput('JS files minified: 12')
            ->expectsOutput('Size reduced: 800KB')
            ->expectsOutput('Files bundled: 5')
            ->expectsOutput('JavaScript optimization completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_optimize_with_quality_settings()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock
                ->shouldReceive('optimizeForMobile')
                ->with(['quality' => 80, 'format' => 'webp'])
                ->andReturn([
                    'images_optimized' => 30,
                    'css_minified' => 5,
                    'js_minified' => 8,
                    'total_size_reduced' => '1.8MB',
                ]);
        });

        $this
            ->artisan('mobile:optimize', [
                '--quality' => '80',
                '--format' => 'webp',
            ])
            ->expectsOutput('📱 Mobile Optimization Tool')
            ->expectsOutput('Optimizing for mobile devices...')
            ->expectsOutput('Images optimized: 30')
            ->expectsOutput('CSS files minified: 5')
            ->expectsOutput('JS files minified: 8')
            ->expectsOutput('Total size reduced: 1.8MB')
            ->expectsOutput('Mobile optimization completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_optimize_with_verbose_output()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock
                ->shouldReceive('optimizeForMobile')
                ->andReturn([
                    'images_optimized' => 40,
                    'css_minified' => 6,
                    'js_minified' => 10,
                    'total_size_reduced' => '2.1MB',
                    'details' => [
                        'image_formats' => ['jpg' => 'webp', 'png' => 'webp'],
                        'css_removed' => ['unused.css', 'old.css'],
                        'js_bundled' => ['app.js', 'vendor.js'],
                    ],
                ]);
        });

        $this
            ->artisan('mobile:optimize', ['--verbose' => true])
            ->expectsOutput('📱 Mobile Optimization Tool')
            ->expectsOutput('Optimizing for mobile devices...')
            ->expectsOutput('Images optimized: 40')
            ->expectsOutput('CSS files minified: 6')
            ->expectsOutput('JS files minified: 10')
            ->expectsOutput('Total size reduced: 2.1MB')
            ->expectsOutput('Details:')
            ->expectsOutput('  Image formats converted: jpg -> webp, png -> webp')
            ->expectsOutput('  CSS files removed: unused.css, old.css')
            ->expectsOutput('  JS files bundled: app.js, vendor.js')
            ->expectsOutput('Mobile optimization completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_optimize_with_dry_run()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock
                ->shouldReceive('getOptimizationPreview')
                ->andReturn([
                    'images_to_optimize' => 50,
                    'css_to_minify' => 10,
                    'js_to_minify' => 15,
                    'estimated_size_reduction' => '2.5MB',
                ]);
        });

        $this
            ->artisan('mobile:optimize', ['--dry-run' => true])
            ->expectsOutput('📱 Mobile Optimization Tool')
            ->expectsOutput('Dry run mode - no files will be modified')
            ->expectsOutput('Images to optimize: 50')
            ->expectsOutput('CSS files to minify: 10')
            ->expectsOutput('JS files to minify: 15')
            ->expectsOutput('Estimated size reduction: 2.5MB')
            ->expectsOutput('Dry run completed')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_optimize_with_backup()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock
                ->shouldReceive('optimizeForMobile')
                ->with(['backup' => true])
                ->andReturn([
                    'images_optimized' => 35,
                    'css_minified' => 7,
                    'js_minified' => 9,
                    'total_size_reduced' => '1.9MB',
                    'backup_created' => true,
                ]);
        });

        $this
            ->artisan('mobile:optimize', ['--backup' => true])
            ->expectsOutput('📱 Mobile Optimization Tool')
            ->expectsOutput('Optimizing for mobile devices...')
            ->expectsOutput('Images optimized: 35')
            ->expectsOutput('CSS files minified: 7')
            ->expectsOutput('JS files minified: 9')
            ->expectsOutput('Total size reduced: 1.9MB')
            ->expectsOutput('Backup created successfully')
            ->expectsOutput('Mobile optimization completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_optimize_with_specific_directory()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock
                ->shouldReceive('optimizeForMobile')
                ->with(['directory' => 'public/assets'])
                ->andReturn([
                    'images_optimized' => 20,
                    'css_minified' => 4,
                    'js_minified' => 6,
                    'total_size_reduced' => '1.2MB',
                ]);
        });

        $this
            ->artisan('mobile:optimize', ['--directory' => 'public/assets'])
            ->expectsOutput('📱 Mobile Optimization Tool')
            ->expectsOutput('Optimizing for mobile devices...')
            ->expectsOutput('Images optimized: 20')
            ->expectsOutput('CSS files minified: 4')
            ->expectsOutput('JS files minified: 6')
            ->expectsOutput('Total size reduced: 1.2MB')
            ->expectsOutput('Mobile optimization completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_optimization_failure()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock
                ->shouldReceive('optimizeForMobile')
                ->andThrow(new \Exception('Optimization failed'));
        });

        $this
            ->artisan('mobile:optimize')
            ->expectsOutput('📱 Mobile Optimization Tool')
            ->expectsOutput('Optimizing for mobile devices...')
            ->expectsOutput('Mobile optimization failed: Optimization failed')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_invalid_quality_setting()
    {
        $this
            ->artisan('mobile:optimize', ['--quality' => '150'])
            ->expectsOutput('📱 Mobile Optimization Tool')
            ->expectsOutput('Invalid quality setting. Using default value: 85')
            ->expectsOutput('Optimizing for mobile devices...')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_invalid_format_setting()
    {
        $this
            ->artisan('mobile:optimize', ['--format' => 'invalid'])
            ->expectsOutput('📱 Mobile Optimization Tool')
            ->expectsOutput('Invalid format setting. Using default value: webp')
            ->expectsOutput('Optimizing for mobile devices...')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_show_optimization_statistics()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock
                ->shouldReceive('getOptimizationStatistics')
                ->andReturn([
                    'total_images' => 100,
                    'optimized_images' => 80,
                    'total_css_files' => 20,
                    'minified_css_files' => 15,
                    'total_js_files' => 30,
                    'minified_js_files' => 25,
                    'total_size_reduction' => '5.2MB',
                ]);
        });

        $this
            ->artisan('mobile:optimize', ['--stats' => true])
            ->expectsOutput('📱 Mobile Optimization Tool')
            ->expectsOutput('Optimization Statistics:')
            ->expectsOutput('Total images: 100')
            ->expectsOutput('Optimized images: 80')
            ->expectsOutput('Total CSS files: 20')
            ->expectsOutput('Minified CSS files: 15')
            ->expectsOutput('Total JS files: 30')
            ->expectsOutput('Minified JS files: 25')
            ->expectsOutput('Total size reduction: 5.2MB')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new MobileOptimizationCommand();
        $this->assertEquals('mobile:optimize', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new MobileOptimizationCommand();
        $this->assertEquals('Optimize assets for mobile devices', $command->getDescription());
    }
}
