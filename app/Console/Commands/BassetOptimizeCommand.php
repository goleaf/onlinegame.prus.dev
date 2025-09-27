<?php

namespace App\Console\Commands;

use App\Helpers\BassetHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class BassetOptimizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'basset:optimize {--force : Force re-download of all assets} {--clean : Clean old cached assets}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize Basset assets by pre-caching common assets and cleaning up old files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Basset optimization...');

        if ($this->option('clean')) {
            $this->cleanOldAssets();
        }

        $this->preCacheCommonAssets();
        $this->runInternalization();
        $this->showStats();

        $this->info('✅ Basset optimization completed!');
    }

    /**
     * Pre-cache common assets
     */
    private function preCacheCommonAssets(): void
    {
        $this->info('📦 Pre-caching common assets...');

        $assets = BassetHelper::getCommonAssets();
        $bar = $this->output->createProgressBar(count($assets));

        foreach ($assets as $key => $url) {
            try {
                // Force download if --force option is used
                if ($this->option('force')) {
                    $this->forceDownloadAsset($url);
                } else {
                    basset($url);
                }
                $bar->advance();
            } catch (\Exception $e) {
                $this->warn("Failed to cache {$key}: {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine();
    }

    /**
     * Force download an asset
     */
    private function forceDownloadAsset(string $url): void
    {
        $disk = Storage::disk(config('backpack.basset.disk', 'public'));
        $path = config('backpack.basset.path', 'basset');

        // Remove existing file if it exists
        $filename = $this->getAssetFilename($url);
        $filePath = "{$path}/{$filename}";

        if ($disk->exists($filePath)) {
            $disk->delete($filePath);
        }

        // Download the asset
        basset($url);
    }

    /**
     * Get filename for asset
     */
    private function getAssetFilename(string $url): string
    {
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        $query = $parsedUrl['query'] ?? '';

        $filename = str_replace('/', '_', trim($path, '/'));

        if ($query) {
            $filename .= '_' . md5($query);
        }

        return $filename;
    }

    /**
     * Run Basset internalization
     */
    private function runInternalization(): void
    {
        $this->info('🔄 Running Basset internalization...');

        $this->call('basset:internalize');
    }

    /**
     * Clean old cached assets
     */
    private function cleanOldAssets(): void
    {
        $this->info('🧹 Cleaning old cached assets...');

        $disk = Storage::disk(config('backpack.basset.disk', 'public'));
        $path = config('backpack.basset.path', 'basset');

        if (!$disk->exists($path)) {
            $this->info('No cached assets found.');
            return;
        }

        $files = $disk->files($path);
        $deletedCount = 0;

        foreach ($files as $file) {
            if ($file !== "{$path}/.basset") {
                $disk->delete($file);
                $deletedCount++;
            }
        }

        $this->info("Deleted {$deletedCount} old cached assets.");
    }

    /**
     * Show optimization statistics
     */
    private function showStats(): void
    {
        $this->info('📊 Basset Statistics:');

        $disk = Storage::disk(config('backpack.basset.disk', 'public'));
        $path = config('backpack.basset.path', 'basset');

        if (!$disk->exists($path)) {
            $this->info('No cached assets found.');
            return;
        }

        $files = $disk->files($path);
        $totalSize = 0;

        foreach ($files as $file) {
            if ($file !== "{$path}/.basset") {
                $totalSize += $disk->size($file);
            }
        }

        $this->table(
            ['Metric', 'Value'],
            [
                ['Cached Assets', count($files) - 1],  // Exclude .basset file
                ['Total Size', $this->formatBytes($totalSize)],
                ['Storage Path', $path],
                ['Storage Disk', config('backpack.basset.disk', 'public')],
            ]
        );
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
