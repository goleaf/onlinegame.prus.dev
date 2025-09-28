<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\OptimizationCommand;
use App\Services\GamePerformanceOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OptimizationCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_run_all_optimizations()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock->shouldReceive('optimizeAll')->andReturn([
                'cache_optimized' => true,
                'database_optimized' => true,
                'assets_optimized' => true,
                'performance_improved' => '25%',
            ]);
        });

        $this
            ->artisan('optimize:all')
            ->expectsOutput('⚡ Performance Optimization Tool')
            ->expectsOutput('Running all optimizations...')
            ->expectsOutput('Cache optimization: ✅')
            ->expectsOutput('Database optimization: ✅')
            ->expectsOutput('Assets optimization: ✅')
            ->expectsOutput('Performance improved by: 25%')
            ->expectsOutput('All optimizations completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_optimize_cache()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock->shouldReceive('optimizeCache')->andReturn([
                'cache_cleared' => true,
                'cache_warmed' => true,
                'cache_size_reduced' => '50MB',
            ]);
        });

        Cache::shouldReceive('flush')->andReturn(true);
        Cache::shouldReceive('put')->andReturn(true);

        $this
            ->artisan('optimize:cache')
            ->expectsOutput('⚡ Performance Optimization Tool')
            ->expectsOutput('Optimizing cache...')
            ->expectsOutput('Cache cleared: ✅')
            ->expectsOutput('Cache warmed: ✅')
            ->expectsOutput('Cache size reduced: 50MB')
            ->expectsOutput('Cache optimization completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_optimize_database()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock->shouldReceive('optimizeDatabase')->andReturn([
                'queries_optimized' => 15,
                'indexes_created' => 5,
                'tables_optimized' => 10,
                'performance_improved' => '30%',
            ]);
        });

        DB::shouldReceive('statement')->andReturn(true);

        $this
            ->artisan('optimize:database')
            ->expectsOutput('⚡ Performance Optimization Tool')
            ->expectsOutput('Optimizing database...')
            ->expectsOutput('Queries optimized: 15')
            ->expectsOutput('Indexes created: 5')
            ->expectsOutput('Tables optimized: 10')
            ->expectsOutput('Performance improved by: 30%')
            ->expectsOutput('Database optimization completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_optimize_assets()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock->shouldReceive('optimizeAssets')->andReturn([
                'css_minified' => 8,
                'js_minified' => 12,
                'images_optimized' => 25,
                'total_size_reduced' => '2.5MB',
            ]);
        });

        $this
            ->artisan('optimize:assets')
            ->expectsOutput('⚡ Performance Optimization Tool')
            ->expectsOutput('Optimizing assets...')
            ->expectsOutput('CSS files minified: 8')
            ->expectsOutput('JS files minified: 12')
            ->expectsOutput('Images optimized: 25')
            ->expectsOutput('Total size reduced: 2.5MB')
            ->expectsOutput('Assets optimization completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_optimize_with_verbose_output()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock->shouldReceive('optimizeAll')->andReturn([
                'cache_optimized' => true,
                'database_optimized' => true,
                'assets_optimized' => true,
                'performance_improved' => '25%',
                'details' => [
                    'cache_hits' => 95,
                    'database_queries' => 50,
                    'asset_compression' => 80,
                ],
            ]);
        });

        $this
            ->artisan('optimize:all', ['--verbose' => true])
            ->expectsOutput('⚡ Performance Optimization Tool')
            ->expectsOutput('Running all optimizations...')
            ->expectsOutput('Cache optimization: ✅')
            ->expectsOutput('Database optimization: ✅')
            ->expectsOutput('Assets optimization: ✅')
            ->expectsOutput('Performance improved by: 25%')
            ->expectsOutput('Details:')
            ->expectsOutput('  Cache hit rate: 95%')
            ->expectsOutput('  Database queries: 50')
            ->expectsOutput('  Asset compression: 80%')
            ->expectsOutput('All optimizations completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_optimize_with_dry_run()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock->shouldReceive('getOptimizationPreview')->andReturn([
                'cache_operations' => 5,
                'database_operations' => 10,
                'asset_operations' => 15,
                'estimated_improvement' => '20%',
            ]);
        });

        $this
            ->artisan('optimize:all', ['--dry-run' => true])
            ->expectsOutput('⚡ Performance Optimization Tool')
            ->expectsOutput('Dry run mode - no optimizations will be applied')
            ->expectsOutput('Cache operations: 5')
            ->expectsOutput('Database operations: 10')
            ->expectsOutput('Asset operations: 15')
            ->expectsOutput('Estimated improvement: 20%')
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
                ->shouldReceive('optimizeAll')
                ->with(['backup' => true])
                ->andReturn([
                    'cache_optimized' => true,
                    'database_optimized' => true,
                    'assets_optimized' => true,
                    'performance_improved' => '25%',
                    'backup_created' => true,
                ]);
        });

        $this
            ->artisan('optimize:all', ['--backup' => true])
            ->expectsOutput('⚡ Performance Optimization Tool')
            ->expectsOutput('Running all optimizations...')
            ->expectsOutput('Cache optimization: ✅')
            ->expectsOutput('Database optimization: ✅')
            ->expectsOutput('Assets optimization: ✅')
            ->expectsOutput('Performance improved by: 25%')
            ->expectsOutput('Backup created successfully')
            ->expectsOutput('All optimizations completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_optimize_with_specific_options()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock
                ->shouldReceive('optimizeAll')
                ->with(['cache' => true, 'database' => false, 'assets' => true])
                ->andReturn([
                    'cache_optimized' => true,
                    'database_optimized' => false,
                    'assets_optimized' => true,
                    'performance_improved' => '15%',
                ]);
        });

        $this
            ->artisan('optimize:all', [
                '--cache' => true,
                '--no-database' => true,
                '--assets' => true,
            ])
            ->expectsOutput('⚡ Performance Optimization Tool')
            ->expectsOutput('Running selected optimizations...')
            ->expectsOutput('Cache optimization: ✅')
            ->expectsOutput('Database optimization: ⏭️')
            ->expectsOutput('Assets optimization: ✅')
            ->expectsOutput('Performance improved by: 15%')
            ->expectsOutput('Selected optimizations completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_optimization_failure()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock
                ->shouldReceive('optimizeAll')
                ->andThrow(new \Exception('Optimization failed'));
        });

        $this
            ->artisan('optimize:all')
            ->expectsOutput('⚡ Performance Optimization Tool')
            ->expectsOutput('Running all optimizations...')
            ->expectsOutput('Optimization failed: Optimization failed')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_cache_optimization_failure()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock
                ->shouldReceive('optimizeCache')
                ->andThrow(new \Exception('Cache optimization failed'));
        });

        $this
            ->artisan('optimize:cache')
            ->expectsOutput('⚡ Performance Optimization Tool')
            ->expectsOutput('Optimizing cache...')
            ->expectsOutput('Cache optimization failed: Cache optimization failed')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_database_optimization_failure()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock
                ->shouldReceive('optimizeDatabase')
                ->andThrow(new \Exception('Database optimization failed'));
        });

        $this
            ->artisan('optimize:database')
            ->expectsOutput('⚡ Performance Optimization Tool')
            ->expectsOutput('Optimizing database...')
            ->expectsOutput('Database optimization failed: Database optimization failed')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_assets_optimization_failure()
    {
        $this->mock(GamePerformanceOptimizer::class, function ($mock): void {
            $mock
                ->shouldReceive('optimizeAssets')
                ->andThrow(new \Exception('Assets optimization failed'));
        });

        $this
            ->artisan('optimize:assets')
            ->expectsOutput('⚡ Performance Optimization Tool')
            ->expectsOutput('Optimizing assets...')
            ->expectsOutput('Assets optimization failed: Assets optimization failed')
            ->assertExitCode(1);
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
                    'total_optimizations' => 50,
                    'successful_optimizations' => 45,
                    'failed_optimizations' => 5,
                    'average_improvement' => '22%',
                    'last_optimization' => '2023-01-01 12:00:00',
                ]);
        });

        $this
            ->artisan('optimize:stats')
            ->expectsOutput('⚡ Performance Optimization Tool')
            ->expectsOutput('Optimization Statistics:')
            ->expectsOutput('Total optimizations: 50')
            ->expectsOutput('Successful optimizations: 45')
            ->expectsOutput('Failed optimizations: 5')
            ->expectsOutput('Average improvement: 22%')
            ->expectsOutput('Last optimization: 2023-01-01 12:00:00')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new OptimizationCommand();
        $this->assertEquals('optimize:all', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new OptimizationCommand();
        $this->assertEquals('Run performance optimizations', $command->getDescription());
    }
}
