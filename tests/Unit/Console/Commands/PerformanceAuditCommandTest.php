<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\PerformanceAuditCommand;
use App\Services\GamePerformanceMonitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceAuditCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_run_performance_audit()
    {
        $this->mock(GamePerformanceMonitor::class, function ($mock): void {
            $mock->shouldReceive('runPerformanceAudit')->andReturn([
                'database_queries' => 150,
                'slow_queries' => 5,
                'cache_hits' => 95,
                'memory_usage' => '128MB',
                'response_time' => 0.5,
                'recommendations' => [
                    'Add database indexes',
                    'Optimize cache configuration',
                    'Reduce memory usage',
                ],
            ]);
        });

        $this
            ->artisan('performance:audit')
            ->expectsOutput('🔍 Performance Audit Tool')
            ->expectsOutput('Running performance audit...')
            ->expectsOutput('Database queries: 150')
            ->expectsOutput('Slow queries: 5')
            ->expectsOutput('Cache hit rate: 95%')
            ->expectsOutput('Memory usage: 128MB')
            ->expectsOutput('Response time: 0.5s')
            ->expectsOutput('Recommendations:')
            ->expectsOutput('  • Add database indexes')
            ->expectsOutput('  • Optimize cache configuration')
            ->expectsOutput('  • Reduce memory usage')
            ->expectsOutput('Performance audit completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_audit_with_specific_metrics()
    {
        $this->mock(GamePerformanceMonitor::class, function ($mock): void {
            $mock
                ->shouldReceive('runPerformanceAudit')
                ->with(['database', 'cache'])
                ->andReturn([
                    'database_queries' => 100,
                    'slow_queries' => 2,
                    'cache_hits' => 90,
                    'recommendations' => [
                        'Optimize database queries',
                        'Improve cache strategy',
                    ],
                ]);
        });

        $this
            ->artisan('performance:audit', ['--metrics' => 'database,cache'])
            ->expectsOutput('🔍 Performance Audit Tool')
            ->expectsOutput('Running performance audit...')
            ->expectsOutput('Database queries: 100')
            ->expectsOutput('Slow queries: 2')
            ->expectsOutput('Cache hit rate: 90%')
            ->expectsOutput('Recommendations:')
            ->expectsOutput('  • Optimize database queries')
            ->expectsOutput('  • Improve cache strategy')
            ->expectsOutput('Performance audit completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_audit_with_verbose_output()
    {
        $this->mock(GamePerformanceMonitor::class, function ($mock): void {
            $mock->shouldReceive('runPerformanceAudit')->andReturn([
                'database_queries' => 200,
                'slow_queries' => 8,
                'cache_hits' => 85,
                'memory_usage' => '256MB',
                'response_time' => 1.2,
                'details' => [
                    'database' => [
                        'total_queries' => 200,
                        'slow_queries' => 8,
                        'average_query_time' => 0.3,
                    ],
                    'cache' => [
                        'hit_rate' => 85,
                        'miss_rate' => 15,
                        'total_operations' => 1000,
                    ],
                ],
                'recommendations' => [
                    'Add database indexes',
                    'Optimize cache configuration',
                ],
            ]);
        });

        $this
            ->artisan('performance:audit', ['--verbose' => true])
            ->expectsOutput('🔍 Performance Audit Tool')
            ->expectsOutput('Running performance audit...')
            ->expectsOutput('Database queries: 200')
            ->expectsOutput('Slow queries: 8')
            ->expectsOutput('Cache hit rate: 85%')
            ->expectsOutput('Memory usage: 256MB')
            ->expectsOutput('Response time: 1.2s')
            ->expectsOutput('Details:')
            ->expectsOutput('  Database: 200 queries, 8 slow, avg: 0.3s')
            ->expectsOutput('  Cache: 85% hit rate, 15% miss rate, 1000 operations')
            ->expectsOutput('Recommendations:')
            ->expectsOutput('  • Add database indexes')
            ->expectsOutput('  • Optimize cache configuration')
            ->expectsOutput('Performance audit completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_audit_with_output_file()
    {
        $this->mock(GamePerformanceMonitor::class, function ($mock): void {
            $mock->shouldReceive('runPerformanceAudit')->andReturn([
                'database_queries' => 120,
                'slow_queries' => 3,
                'cache_hits' => 92,
                'memory_usage' => '160MB',
                'response_time' => 0.8,
                'recommendations' => [
                    'Optimize database queries',
                    'Improve cache configuration',
                ],
            ]);
        });

        $this
            ->artisan('performance:audit', ['--output' => 'audit.json'])
            ->expectsOutput('🔍 Performance Audit Tool')
            ->expectsOutput('Running performance audit...')
            ->expectsOutput('Database queries: 120')
            ->expectsOutput('Slow queries: 3')
            ->expectsOutput('Cache hit rate: 92%')
            ->expectsOutput('Memory usage: 160MB')
            ->expectsOutput('Response time: 0.8s')
            ->expectsOutput('Recommendations:')
            ->expectsOutput('  • Optimize database queries')
            ->expectsOutput('  • Improve cache configuration')
            ->expectsOutput('Performance audit completed successfully')
            ->expectsOutput('Results saved to: audit.json')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_audit_with_thresholds()
    {
        $this->mock(GamePerformanceMonitor::class, function ($mock): void {
            $mock
                ->shouldReceive('runPerformanceAudit')
                ->with([], ['slow_query_threshold' => 1.0, 'memory_threshold' => 200])
                ->andReturn([
                    'database_queries' => 80,
                    'slow_queries' => 1,
                    'cache_hits' => 98,
                    'memory_usage' => '150MB',
                    'response_time' => 0.3,
                    'recommendations' => [
                        'Performance is within acceptable limits',
                    ],
                ]);
        });

        $this
            ->artisan('performance:audit', [
                '--slow-query-threshold' => '1.0',
                '--memory-threshold' => '200',
            ])
            ->expectsOutput('🔍 Performance Audit Tool')
            ->expectsOutput('Running performance audit...')
            ->expectsOutput('Database queries: 80')
            ->expectsOutput('Slow queries: 1')
            ->expectsOutput('Cache hit rate: 98%')
            ->expectsOutput('Memory usage: 150MB')
            ->expectsOutput('Response time: 0.3s')
            ->expectsOutput('Recommendations:')
            ->expectsOutput('  • Performance is within acceptable limits')
            ->expectsOutput('Performance audit completed successfully')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_run_audit_with_dry_run()
    {
        $this->mock(GamePerformanceMonitor::class, function ($mock): void {
            $mock->shouldReceive('getAuditPreview')->andReturn([
                'estimated_queries' => 100,
                'estimated_slow_queries' => 3,
                'estimated_cache_hits' => 90,
                'estimated_memory_usage' => '128MB',
                'estimated_response_time' => 0.5,
            ]);
        });

        $this
            ->artisan('performance:audit', ['--dry-run' => true])
            ->expectsOutput('🔍 Performance Audit Tool')
            ->expectsOutput('Dry run mode - no actual audit will be performed')
            ->expectsOutput('Estimated database queries: 100')
            ->expectsOutput('Estimated slow queries: 3')
            ->expectsOutput('Estimated cache hit rate: 90%')
            ->expectsOutput('Estimated memory usage: 128MB')
            ->expectsOutput('Estimated response time: 0.5s')
            ->expectsOutput('Dry run completed')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_audit_failure()
    {
        $this->mock(GamePerformanceMonitor::class, function ($mock): void {
            $mock
                ->shouldReceive('runPerformanceAudit')
                ->andThrow(new \Exception('Audit failed'));
        });

        $this
            ->artisan('performance:audit')
            ->expectsOutput('🔍 Performance Audit Tool')
            ->expectsOutput('Running performance audit...')
            ->expectsOutput('Performance audit failed: Audit failed')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_invalid_metrics()
    {
        $this
            ->artisan('performance:audit', ['--metrics' => 'invalid,unknown'])
            ->expectsOutput('🔍 Performance Audit Tool')
            ->expectsOutput('Invalid metrics specified. Using default metrics.')
            ->expectsOutput('Running performance audit...')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_invalid_thresholds()
    {
        $this
            ->artisan('performance:audit', [
                '--slow-query-threshold' => 'invalid',
                '--memory-threshold' => 'invalid',
            ])
            ->expectsOutput('🔍 Performance Audit Tool')
            ->expectsOutput('Invalid threshold values. Using default thresholds.')
            ->expectsOutput('Running performance audit...')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_show_audit_history()
    {
        $this->mock(GamePerformanceMonitor::class, function ($mock): void {
            $mock
                ->shouldReceive('getAuditHistory')
                ->with(10)
                ->andReturn([
                    ['date' => '2023-01-01', 'queries' => 100, 'slow_queries' => 2, 'cache_hits' => 95],
                    ['date' => '2023-01-02', 'queries' => 120, 'slow_queries' => 3, 'cache_hits' => 90],
                    ['date' => '2023-01-03', 'queries' => 80, 'slow_queries' => 1, 'cache_hits' => 98],
                ]);
        });

        $this
            ->artisan('performance:audit', ['--history' => true])
            ->expectsOutput('🔍 Performance Audit Tool')
            ->expectsOutput('Performance Audit History:')
            ->expectsOutput('Date: 2023-01-01, Queries: 100, Slow: 2, Cache: 95%')
            ->expectsOutput('Date: 2023-01-02, Queries: 120, Slow: 3, Cache: 90%')
            ->expectsOutput('Date: 2023-01-03, Queries: 80, Slow: 1, Cache: 98%')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_show_audit_statistics()
    {
        $this->mock(GamePerformanceMonitor::class, function ($mock): void {
            $mock
                ->shouldReceive('getAuditStatistics')
                ->andReturn([
                    'total_audits' => 50,
                    'average_queries' => 110,
                    'average_slow_queries' => 2.5,
                    'average_cache_hits' => 92,
                    'performance_trend' => 'improving',
                ]);
        });

        $this
            ->artisan('performance:audit', ['--stats' => true])
            ->expectsOutput('🔍 Performance Audit Tool')
            ->expectsOutput('Performance Audit Statistics:')
            ->expectsOutput('Total audits: 50')
            ->expectsOutput('Average queries: 110')
            ->expectsOutput('Average slow queries: 2.5')
            ->expectsOutput('Average cache hit rate: 92%')
            ->expectsOutput('Performance trend: improving')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_has_correct_signature()
    {
        $command = new PerformanceAuditCommand();
        $this->assertEquals('performance:audit', $command->getName());
    }

    /**
     * @test
     */
    public function it_has_correct_description()
    {
        $command = new PerformanceAuditCommand();
        $this->assertEquals('Run performance audit and analysis', $command->getDescription());
    }
}
