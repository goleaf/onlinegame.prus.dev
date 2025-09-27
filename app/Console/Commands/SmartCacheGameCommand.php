<?php

namespace App\Console\Commands;

use App\Services\SmartCacheGameOptimizer;
use Illuminate\Console\Command;

/**
 * Command to manage SmartCache game performance optimization
 */
class SmartCacheGameCommand extends Command
{
    protected $signature = 'smart-cache:game 
                            {action : Action to perform (optimize|warmup|metrics|invalidate|batch)}
                            {--user-id=* : User IDs to optimize for}
                            {--data-types=* : Data types to optimize}
                            {--operation=warmup : Batch operation type (warmup|invalidate|optimize)}
                            {--limit=100 : Limit for queries}';

    protected $description = 'Manage SmartCache game performance optimization with intelligent caching strategies';

    public function handle(SmartCacheGameOptimizer $optimizer): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'optimize' => $this->optimizeGameData($optimizer),
            'warmup' => $this->intelligentWarmup($optimizer),
            'metrics' => $this->showSmartCacheMetrics($optimizer),
            'invalidate' => $this->intelligentInvalidation($optimizer),
            'batch' => $this->batchOperations($optimizer),
            default => $this->showHelp(),
        };
    }

    protected function optimizeGameData(SmartCacheGameOptimizer $optimizer): int
    {
        $this->info('🚀 SmartCache Game Data Optimization...');
        $this->newLine();

        $userIds = $this->option('user-id') ?: ['1'];
        $dataTypes = $this->option('data-types') ?: [
            'user_data',
            'village_data',
            'troop_data',
            'building_data',
            'resource_data',
            'battle_data',
            'statistics'
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
        $this->info('SmartCache optimization completed in ' . round($executionTime * 1000, 2) . 'ms');

        return 0;
    }

    protected function intelligentWarmup(SmartCacheGameOptimizer $optimizer): int
    {
        $this->info('🔥 SmartCache Intelligent Warmup...');
        $this->newLine();

        $userIds = $this->option('user-id') ?: ['1'];

        try {
            $results = $optimizer->intelligentCacheWarmup($userIds);
            
            $this->info("✅ Users processed: {$results['users_processed']}");
            $this->info("✅ Cache entries created: {$results['cache_entries_created']}");
            $this->info('⏱️ Execution time: ' . round($results['execution_time'] * 1000, 2) . 'ms');

        } catch (\Exception $e) {
            $this->error("Failed to perform intelligent warmup: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    protected function showSmartCacheMetrics(SmartCacheGameOptimizer $optimizer): int
    {
        $this->info('📊 SmartCache Performance Metrics');
        $this->newLine();

        try {
            $metrics = $optimizer->getSmartCacheMetrics();

            // Cache Strategies
            $this->info('🎯 Cache Strategies:');
            foreach ($metrics['cache_strategies'] as $type => $strategy) {
                $this->line("  {$type}: TTL {$strategy['ttl']}min, Compression: " . ($strategy['compression'] ? 'Yes' : 'No'));
            }
            $this->newLine();

            // Performance Metrics
            if (!empty($metrics['performance_metrics'])) {
                $this->info('⚡ Performance Metrics:');
                foreach ($metrics['performance_metrics'] as $key => $value) {
                    $this->line("  {$key}: " . round($value * 1000, 2) . 'ms');
                }
                $this->newLine();
            }

            // Memory Usage
            $this->info('🧠 Memory Usage:');
            $memory = $metrics['memory_usage'];
            $this->line("  Current: {$memory['formatted_current']}");
            $this->line("  Peak: {$memory['formatted_peak']}");
            $this->newLine();

            // Cache Statistics
            $this->info('📈 Cache Statistics:');
            $cacheStats = $metrics['cache_statistics'];
            if (isset($cacheStats['error'])) {
                $this->warn("  Warning: {$cacheStats['error']}");
            } else {
                $this->line("  Strategies Count: {$cacheStats['strategies_count']}");
                $this->line("  Performance Metrics Count: {$cacheStats['performance_metrics_count']}");
                $this->line("  Cache Operations: " . round($cacheStats['cache_operations'] * 1000, 2) . 'ms');
            }
            $this->newLine();

            $this->info("📅 Generated at: {$metrics['timestamp']}");

        } catch (\Exception $e) {
            $this->error("Failed to retrieve SmartCache metrics: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    protected function intelligentInvalidation(SmartCacheGameOptimizer $optimizer): int
    {
        $this->info('🧹 SmartCache Intelligent Invalidation...');
        $this->newLine();

        $userIds = $this->option('user-id') ?: ['1'];
        $dataTypes = $this->option('data-types') ?: [];

        try {
            $totalInvalidated = 0;
            $totalTime = 0;

            foreach ($userIds as $userId) {
                $results = $optimizer->intelligentCacheInvalidation($userId, $dataTypes);
                $totalInvalidated += $results['invalidated_keys'];
                $totalTime += $results['execution_time'];
                
                $this->info("✅ User {$userId}: {$results['invalidated_keys']} keys invalidated");
            }

            $this->newLine();
            $this->info("✅ Total keys invalidated: {$totalInvalidated}");
            $this->info('⏱️ Total execution time: ' . round($totalTime * 1000, 2) . 'ms');

        } catch (\Exception $e) {
            $this->error("Failed to perform intelligent invalidation: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    protected function batchOperations(SmartCacheGameOptimizer $optimizer): int
    {
        $this->info('🔄 SmartCache Batch Operations...');
        $this->newLine();

        $userIds = $this->option('user-id') ?: ['1'];
        $operation = $this->option('operation') ?: 'warmup';

        try {
            $results = $optimizer->batchCacheOperations($userIds, $operation);
            
            $this->info("✅ Operation: {$results['operation']}");
            $this->info("✅ Users processed: {$results['users_processed']}");
            $this->info('⏱️ Execution time: ' . round($results['execution_time'] * 1000, 2) . 'ms');

        } catch (\Exception $e) {
            $this->error("Failed to perform batch operations: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    protected function showHelp(): int
    {
        $this->info('🎮 SmartCache Game Performance Optimization Commands');
        $this->newLine();

        $this->line('Available actions:');
        $this->line('  optimize    - Optimize game data with intelligent caching strategies');
        $this->line('  warmup      - Intelligent cache warmup with predictive loading');
        $this->line('  metrics     - Show comprehensive SmartCache performance metrics');
        $this->line('  invalidate  - Intelligent cache invalidation');
        $this->line('  batch       - Batch cache operations for multiple users');
        $this->newLine();

        $this->line('Examples:');
        $this->line('  php artisan smart-cache:game optimize --user-id=1 --data-types=user_data,village_data');
        $this->line('  php artisan smart-cache:game warmup --user-id=1,2,3');
        $this->line('  php artisan smart-cache:game metrics');
        $this->line('  php artisan smart-cache:game invalidate --user-id=1 --data-types=user_data');
        $this->line('  php artisan smart-cache:game batch --user-id=1,2,3 --operation=warmup');
        $this->newLine();

        $this->line('Options:');
        $this->line('  --user-id=*     User IDs to optimize for (default: 1)');
        $this->line('  --data-types=*  Data types to optimize');
        $this->line('  --operation=    Batch operation type (warmup|invalidate|optimize)');
        $this->line('  --limit=100     Limit for queries (default: 100)');

        return 0;
    }
}

