<?php

namespace App\Console\Commands;

use App\Services\GamePerformanceOptimizer;
use Illuminate\Console\Command;

/**
 * Command to manage game performance optimization using Laravel 12.29.0+ features
 */
class GamePerformanceCommand extends Command
{
    protected $signature = 'game:performance 
                            {action : Action to perform (optimize|metrics|cleanup|warmup)}
                            {--user-id=* : User IDs to optimize for}
                            {--data-types=* : Data types to optimize (user_stats,village_data,troop_data,building_data,resource_data)}
                            {--limit=100 : Limit for queries}';

    protected $description = 'Manage game performance optimization using Laravel 12.29.0+ features';

    public function handle(GamePerformanceOptimizer $optimizer): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'optimize' => $this->optimizeGameData($optimizer),
            'metrics' => $this->showPerformanceMetrics($optimizer),
            'cleanup' => $this->cleanupExpiredData($optimizer),
            'warmup' => $this->warmupCache($optimizer),
            default => $this->showHelp(),
        };
    }

    protected function optimizeGameData(GamePerformanceOptimizer $optimizer): int
    {
        $this->info('🚀 Optimizing Game Data Performance...');
        $this->newLine();

        $userIds = $this->option('user-id') ?: ['1'];  // Default to user 1 for demo
        $dataTypes = $this->option('data-types') ?: [
            'user_stats',
            'village_data',
            'troop_data',
            'building_data',
            'resource_data'
        ];

        $startTime = microtime(true);

        foreach ($userIds as $userId) {
            $this->line("Optimizing data for user: {$userId}");

            try {
                $results = $optimizer->optimizeGameData($userId, $dataTypes);

                foreach ($results as $type => $data) {
                    $count = is_array($data) ? count($data) : 1;
                    $this->info("  ✅ {$type}: {$count} records optimized");
                }
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to optimize data for user {$userId}: {$e->getMessage()}");
            }
        }

        $executionTime = microtime(true) - $startTime;
        $this->newLine();
        $this->info('Optimization completed in ' . round($executionTime * 1000, 2) . 'ms');

        return 0;
    }

    protected function showPerformanceMetrics(GamePerformanceOptimizer $optimizer): int
    {
        $this->info('📊 Game Performance Metrics');
        $this->newLine();

        try {
            $metrics = $optimizer->getPerformanceMetrics();

            // Cache Performance
            $this->info('💾 Cache Performance:');
            $cacheStats = $metrics['cache_performance'];
            $this->line("  Memory Used: {$cacheStats['memory_used']}");
            $this->line("  Keys Count: {$cacheStats['keys_count']}");
            $this->line("  Hit Rate: {$cacheStats['hit_rate']}%");
            $this->line('  Compression: ' . ($cacheStats['compression_enabled'] ? 'Enabled' : 'Disabled'));
            $this->newLine();

            // Session Performance
            $this->info('🔐 Session Performance:');
            $sessionStats = $metrics['session_performance'];
            $this->line("  Session Count: {$sessionStats['session_count']}");
            $this->line("  Memory Used: {$sessionStats['memory_used']}");
            $this->line("  Lifetime: {$sessionStats['lifetime']} minutes");
            $this->line("  Driver: {$sessionStats['driver']}");
            $this->line('  Compression: ' . ($sessionStats['compression_enabled'] ? 'Enabled' : 'Disabled'));
            $this->newLine();

            // Memory Usage
            $this->info('🧠 Memory Usage:');
            $memory = $metrics['memory_usage'];
            $this->line("  Current: {$memory['formatted_current']}");
            $this->line("  Peak: {$memory['formatted_peak']}");
            $this->newLine();

            // Database Metrics
            $this->info('🗄️ Database Metrics:');
            $dbMetrics = $metrics['database_metrics'];
            if (isset($dbMetrics['error'])) {
                $this->warn("  Warning: {$dbMetrics['error']}");
            } else {
                $this->line("  Total Queries: {$dbMetrics['total_queries']}");
                $this->line("  Active Connections: {$dbMetrics['active_connections']}");
                $this->line('  Execution Time: ' . round($dbMetrics['query_execution_time'], 2) . 'ms');
            }
            $this->newLine();

            // Optimization Metrics
            if (!empty($metrics['optimization_metrics'])) {
                $this->info('⚡ Optimization Metrics:');
                foreach ($metrics['optimization_metrics'] as $key => $value) {
                    $this->line("  {$key}: " . round($value * 1000, 2) . 'ms');
                }
                $this->newLine();
            }

            $this->info("📅 Generated at: {$metrics['timestamp']}");
        } catch (\Exception $e) {
            $this->error("Failed to retrieve performance metrics: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    protected function cleanupExpiredData(GamePerformanceOptimizer $optimizer): int
    {
        $this->info('🧹 Cleaning up expired data...');
        $this->newLine();

        try {
            $results = $optimizer->cleanupExpiredData();

            $this->info("✅ Cache cleanup: {$results['cache_cleaned']} operations completed");
            $this->info("✅ Session cleanup: {$results['sessions_cleaned']} sessions removed");
            $this->info('⏱️ Execution time: ' . round($results['execution_time'] * 1000, 2) . 'ms');
        } catch (\Exception $e) {
            $this->error("Failed to cleanup expired data: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    protected function warmupCache(GamePerformanceOptimizer $optimizer): int
    {
        $this->info('🔥 Warming up cache...');
        $this->newLine();

        $userIds = $this->option('user-id') ?: ['1'];  // Default to user 1 for demo

        try {
            $startTime = microtime(true);
            $optimizer->warmUpGameCache($userIds);
            $executionTime = microtime(true) - $startTime;

            $this->info('✅ Cache warmed up for ' . count($userIds) . ' users');
            $this->info('⏱️ Execution time: ' . round($executionTime * 1000, 2) . 'ms');
        } catch (\Exception $e) {
            $this->error("Failed to warm up cache: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    protected function showHelp(): int
    {
        $this->info('🎮 Game Performance Optimization Commands');
        $this->newLine();

        $this->line('Available actions:');
        $this->line('  optimize  - Optimize game data loading with enhanced caching');
        $this->line('  metrics   - Show comprehensive performance metrics');
        $this->line('  cleanup   - Clean up expired cache and session data');
        $this->line('  warmup    - Warm up cache for frequently accessed data');
        $this->newLine();

        $this->line('Examples:');
        $this->line('  php artisan game:performance optimize --user-id=1 --data-types=user_stats,village_data');
        $this->line('  php artisan game:performance metrics');
        $this->line('  php artisan game:performance cleanup');
        $this->line('  php artisan game:performance warmup --user-id=1,2,3');
        $this->newLine();

        $this->line('Options:');
        $this->line('  --user-id=*     User IDs to optimize for (default: 1)');
        $this->line('  --data-types=*  Data types to optimize');
        $this->line('  --limit=100     Limit for queries (default: 100)');

        return 0;
    }
}
