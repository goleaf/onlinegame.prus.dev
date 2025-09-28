<?php

namespace App\Console\Commands;

use App\Services\CacheEvictionService;
use Illuminate\Console\Command;

class CacheEvictCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:evict-custom 
                            {store? : The cache store to evict (optional, evicts all stores if not specified)}
                            {--stats : Show cache statistics before and after eviction}
                            {--force : Force eviction without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Efficiently remove expired cache items using custom implementation';

    /**
     * The cache eviction service
     *
     * @var CacheEvictionService
     */
    protected $cacheEvictionService;

    /**
     * Create a new command instance.
     */
    public function __construct(CacheEvictionService $cacheEvictionService)
    {
        parent::__construct();
        $this->cacheEvictionService = $cacheEvictionService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $store = $this->argument('store');
        $showStats = $this->option('stats');
        $force = $this->option('force');

        $this->info('🧹 Laravel Cache Eviction Tool');
        $this->newLine();

        // Show initial statistics if requested
        if ($showStats) {
            $this->showCacheStats('Before Eviction');
        }

        // Confirm eviction unless forced
        if (! $force && ! $this->confirm('Do you want to proceed with cache eviction?')) {
            $this->info('Cache eviction cancelled.');

            return 0;
        }

        $this->newLine();
        $this->info('Starting cache eviction...');
        $this->newLine();

        $startTime = microtime(true);

        try {
            if ($store) {
                // Evict specific store
                $result = $this->cacheEvictionService->evictStore($store);
                $this->displayStoreResult($store, $result);
            } else {
                // Evict all stores
                $results = $this->cacheEvictionService->evictAllStores();
                $this->displayAllResults($results);
            }

            $totalDuration = microtime(true) - $startTime;
            $this->newLine();
            $this->info('✅ Cache eviction completed in '.round($totalDuration, 3).' seconds');

            // Show final statistics if requested
            if ($showStats) {
                $this->newLine();
                $this->showCacheStats('After Eviction');
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Cache eviction failed: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Display results for a single store
     */
    private function displayStoreResult(string $storeName, array $result): void
    {
        if ($result['success']) {
            $this->info("✅ Store '{$storeName}':");
            $this->line("   Items removed: {$result['items_removed']}");
            $this->line("   Size freed: {$result['size_freed']}");
            $this->line("   Duration: {$result['duration']}s");

            if (isset($result['items_before']) && isset($result['items_after'])) {
                $this->line("   Items before: {$result['items_before']}");
                $this->line("   Items after: {$result['items_after']}");
            }
        } else {
            $this->error("❌ Store '{$storeName}': {$result['error']}");
        }
    }

    /**
     * Display results for all stores
     */
    private function displayAllResults(array $results): void
    {
        $totalItemsRemoved = 0;
        $totalDuration = 0;
        $successfulStores = 0;
        $failedStores = 0;

        foreach ($results as $storeName => $result) {
            if ($result['success']) {
                $this->displayStoreResult($storeName, $result);
                $totalItemsRemoved += $result['items_removed'];
                $totalDuration += $result['duration'];
                $successfulStores++;
            } else {
                $this->error("❌ Store '{$storeName}': {$result['error']}");
                $failedStores++;
            }
            $this->newLine();
        }

        // Summary
        $this->info('📊 Summary:');
        $this->line("   Successful stores: {$successfulStores}");
        if ($failedStores > 0) {
            $this->line("   Failed stores: {$failedStores}");
        }
        $this->line("   Total items removed: {$totalItemsRemoved}");
        $this->line('   Total duration: '.round($totalDuration, 3).'s');
    }

    /**
     * Show cache statistics
     */
    private function showCacheStats(string $title): void
    {
        $this->info("📊 Cache Statistics - {$title}");
        $this->newLine();

        try {
            $stats = $this->cacheEvictionService->getCacheStats();

            $headers = ['Store', 'Driver', 'Total Items', 'Expired Items', 'Active Items', 'Size'];
            $rows = [];

            foreach ($stats as $storeName => $storeStats) {
                if (isset($storeStats['error'])) {
                    $rows[] = [
                        $storeName,
                        'Error',
                        'N/A',
                        'N/A',
                        'N/A',
                        $storeStats['error'],
                    ];
                } else {
                    $rows[] = [
                        $storeName,
                        $storeStats['driver'],
                        $storeStats['total_items'] ?? 'N/A',
                        $storeStats['expired_items'] ?? 'N/A',
                        $storeStats['active_items'] ?? 'N/A',
                        $storeStats['total_size'] ?? 'N/A',
                    ];
                }
            }

            $this->table($headers, $rows);
        } catch (\Exception $e) {
            $this->error('Failed to retrieve cache statistics: '.$e->getMessage());
        }

        $this->newLine();
    }
}
