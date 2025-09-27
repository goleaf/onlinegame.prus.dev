<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class OptimizationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'optimize:all {--force : Force optimization without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run comprehensive optimization for the entire application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting comprehensive optimization...');

        if (!$this->option('force')) {
            if (!$this->confirm('This will optimize the entire application. Continue?')) {
                $this->info('Optimization cancelled.');
                return 0;
            }
        }

        $this->optimizeFramework();
        $this->optimizeDatabase();
        $this->optimizeAssets();
        $this->optimizePerformance();
        $this->optimizeSecurity();

        $this->info('✅ Comprehensive optimization completed!');
        
        return 0;
    }

    /**
     * Optimize Laravel framework
     */
    private function optimizeFramework(): void
    {
        $this->info('⚙️ Optimizing Laravel framework...');

        $commands = [
            'config:cache' => 'Caching configuration',
            'route:cache' => 'Caching routes',
            'view:cache' => 'Caching views',
            'event:cache' => 'Caching events',
        ];

        foreach ($commands as $command => $description) {
            try {
                $this->info("  {$description}...");
                Artisan::call($command);
                $this->info("  ✓ {$description} completed.");
            } catch (\Exception $e) {
                $this->warn("  ✗ {$description} failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Optimize database
     */
    private function optimizeDatabase(): void
    {
        $this->info('🗄️ Optimizing database...');

        try {
            // Analyze tables for optimization
            $tables = DB::select('SHOW TABLES');
            $optimizedCount = 0;

            foreach ($tables as $table) {
                $tableName = array_values((array) $table)[0];
                try {
                    DB::statement("OPTIMIZE TABLE `{$tableName}`");
                    $optimizedCount++;
                } catch (\Exception $e) {
                    // Continue with other tables
                }
            }

            $this->info("  ✓ Optimized {$optimizedCount} database tables.");
        } catch (\Exception $e) {
            $this->warn("  ✗ Database optimization failed: " . $e->getMessage());
        }
    }

    /**
     * Optimize assets
     */
    private function optimizeAssets(): void
    {
        $this->info('📦 Optimizing assets...');

        try {
            // Run Basset optimization
            Artisan::call('basset:optimize');
            $this->info("  ✓ Basset assets optimized.");

            // Clear and rebuild asset cache
            if (File::exists(public_path('mix-manifest.json'))) {
                File::delete(public_path('mix-manifest.json'));
                $this->info("  ✓ Asset manifest cleared.");
            }

        } catch (\Exception $e) {
            $this->warn("  ✗ Asset optimization failed: " . $e->getMessage());
        }
    }

    /**
     * Optimize performance
     */
    private function optimizePerformance(): void
    {
        $this->info('⚡ Optimizing performance...');

        try {
            // Clear all caches
            Cache::flush();
            $this->info("  ✓ Application cache cleared.");

            // Clear game-specific caches
            if (class_exists(\App\Services\GameCacheService::class)) {
                \App\Services\GameCacheService::clearAllGameCache();
                $this->info("  ✓ Game cache cleared.");
            }

            // Optimize OPcache if available
            if (function_exists('opcache_reset')) {
                opcache_reset();
                $this->info("  ✓ OPcache cleared.");
            }

            // Run garbage collection
            gc_collect_cycles();
            $this->info("  ✓ Garbage collection completed.");

            // Process completed trainings
            if (class_exists(\App\Services\Game\UnitTrainingService::class)) {
                $trainingService = app(\App\Services\Game\UnitTrainingService::class);
                $result = $trainingService->processCompletedTrainings();
                if ($result['success']) {
                    $this->info("  ✓ Processed {$result['processed']} completed trainings.");
                }
            }

            // Generate performance report
            if (class_exists(\App\Services\GamePerformanceMonitor::class)) {
                $monitor = new \App\Services\GamePerformanceMonitor();
                $report = $monitor->generatePerformanceReport();
                $this->info("  ✓ Performance report generated.");
            }

        } catch (\Exception $e) {
            $this->warn("  ✗ Performance optimization failed: " . $e->getMessage());
        }
    }

    /**
     * Optimize security
     */
    private function optimizeSecurity(): void
    {
        $this->info('🔒 Optimizing security...');

        try {
            // Generate application key if not set
            if (empty(config('app.key'))) {
                Artisan::call('key:generate');
                $this->info("  ✓ Application key generated.");
            }

            // Clear session data
            $sessionPath = storage_path('framework/sessions');
            if (File::exists($sessionPath)) {
                $files = File::files($sessionPath);
                foreach ($files as $file) {
                    if (File::lastModified($file) < strtotime('-24 hours')) {
                        File::delete($file);
                    }
                }
                $this->info("  ✓ Old session files cleared.");
            }

        } catch (\Exception $e) {
            $this->warn("  ✗ Security optimization failed: " . $e->getMessage());
        }
    }
}
