<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class CleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:all {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up temporary files, caches, and optimize the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Starting comprehensive cleanup...');

        if (!$this->option('force')) {
            if (!$this->confirm('This will clean up temporary files and caches. Continue?')) {
                $this->info('Cleanup cancelled.');
                return 0;
            }
        }

        $this->cleanupTempFiles();
        $this->cleanupCaches();
        $this->cleanupLogs();
        $this->cleanupStorage();
        $this->optimizeApplication();

        $this->info('✅ Cleanup completed successfully!');
        
        return 0;
    }

    /**
     * Clean up temporary files
     */
    private function cleanupTempFiles(): void
    {
        $this->info('🗂️ Cleaning temporary files...');

        $tempDirs = [
            storage_path('app/temp'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            base_path('bootstrap/cache'),
        ];

        $deletedCount = 0;
        foreach ($tempDirs as $dir) {
            if (File::exists($dir)) {
                $files = File::allFiles($dir);
                foreach ($files as $file) {
                    if ($file->getExtension() === 'php' && $file->getFilename() !== '.gitignore') {
                        File::delete($file->getPathname());
                        $deletedCount++;
                    }
                }
            }
        }

        $this->info("Deleted {$deletedCount} temporary files.");
    }

    /**
     * Clean up application caches
     */
    private function cleanupCaches(): void
    {
        $this->info('💾 Cleaning application caches...');

        try {
            Cache::flush();
            $this->info('Application cache cleared.');
        } catch (\Exception $e) {
            $this->warn('Could not clear application cache: ' . $e->getMessage());
        }

        // Clear specific caches
        $cacheKeys = [
            'config_cache',
            'route_cache',
            'view_cache',
            'event_cache',
        ];

        foreach ($cacheKeys as $key) {
            try {
                Cache::forget($key);
            } catch (\Exception $e) {
                // Ignore cache errors
            }
        }

        $this->info('Specific caches cleared.');
    }

    /**
     * Clean up log files
     */
    private function cleanupLogs(): void
    {
        $this->info('📝 Cleaning log files...');

        $logPath = storage_path('logs');
        if (File::exists($logPath)) {
            $logFiles = File::glob($logPath . '/*.log');
            $deletedCount = 0;

            foreach ($logFiles as $logFile) {
                $fileSize = File::size($logFile);
                // Delete log files larger than 10MB or older than 7 days
                if ($fileSize > 10 * 1024 * 1024 || File::lastModified($logFile) < strtotime('-7 days')) {
                    File::delete($logFile);
                    $deletedCount++;
                }
            }

            $this->info("Deleted {$deletedCount} log files.");
        }
    }

    /**
     * Clean up storage files
     */
    private function cleanupStorage(): void
    {
        $this->info('💿 Cleaning storage files...');

        // Clean up old uploaded files
        $publicPath = storage_path('app/public');
        if (File::exists($publicPath)) {
            $directories = File::directories($publicPath);
            $deletedCount = 0;

            foreach ($directories as $dir) {
                $files = File::files($dir);
                foreach ($files as $file) {
                    // Delete files older than 30 days
                    if (File::lastModified($file) < strtotime('-30 days')) {
                        File::delete($file);
                        $deletedCount++;
                    }
                }
            }

            $this->info("Deleted {$deletedCount} old storage files.");
        }
    }

    /**
     * Optimize application
     */
    private function optimizeApplication(): void
    {
        $this->info('⚡ Optimizing application...');

        $commands = [
            'config:cache',
            'route:cache',
            'view:cache',
            'event:cache',
        ];

        foreach ($commands as $command) {
            try {
                $this->call($command);
                $this->info("✓ {$command} completed.");
            } catch (\Exception $e) {
                $this->warn("✗ {$command} failed: " . $e->getMessage());
            }
        }
    }
}
