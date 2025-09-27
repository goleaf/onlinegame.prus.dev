<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use SmartCache\Facades\SmartCache;

class UpdaterService
{
    protected string $repositoryUrl;
    protected string $currentVersion;
    protected string $latestVersion;
    protected array $updateSteps = [];
    protected bool $maintenanceMode = false;

    public function __construct()
    {
        $this->repositoryUrl = config('app.updater.repository_url', 'https://github.com/your-org/your-repo.git');
        $this->currentVersion = $this->getCurrentVersion();
    }

    /**
     * Get current application version
     */
    public function getCurrentVersion(): string
    {
        $cacheKey = 'updater_current_version_' . now()->format('Y-m-d-H');
        
        return SmartCache::remember($cacheKey, now()->addHours(1), function () {
            try {
                $result = Process::run('git describe --tags --abbrev=0');
                return $result->successful() ? trim($result->output()) : '1.0.0';
            } catch (\Exception $e) {
                Log::warning('Could not get current version from git', ['error' => $e->getMessage()]);
                return '1.0.0';
            }
        });
    }

    /**
     * Check if a new version is available
     */
    public function checkForUpdates(): array
    {
        $cacheKey = 'updater_check_' . now()->format('Y-m-d-H-i');
        
        return SmartCache::remember($cacheKey, now()->addMinutes(5), function () {
            try {
                // Fetch latest tags from remote
                Process::run('git fetch --tags');

                $result = Process::run('git describe --tags --abbrev=0 origin/main');
                $this->latestVersion = $result->successful() ? trim($result->output()) : $this->currentVersion;

                $isUpdateAvailable = version_compare($this->latestVersion, $this->currentVersion, '>');

                return [
                    'current_version' => $this->currentVersion,
                    'latest_version' => $this->latestVersion,
                    'update_available' => $isUpdateAvailable,
                    'behind_commits' => $this->getCommitsBehind(),
                ];
            } catch (\Exception $e) {
                Log::error('Error checking for updates', ['error' => $e->getMessage()]);
                return [
                    'current_version' => $this->currentVersion,
                    'latest_version' => $this->currentVersion,
                    'update_available' => false,
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Get number of commits behind
     */
    protected function getCommitsBehind(): int
    {
        try {
            $result = Process::run('git rev-list --count HEAD..origin/main');
            return $result->successful() ? (int) trim($result->output()) : 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Perform the update
     */
    public function performUpdate(): array
    {
        $this->updateSteps = [];

        try {
            $this->addStep('Starting update process...');

            // Enable maintenance mode
            $this->enableMaintenanceMode();

            // Pull latest changes
            $this->pullLatestChanges();

            // Install/update dependencies
            $this->updateDependencies();

            // Run migrations
            $this->runMigrations();

            // Clear caches
            $this->clearCaches();

            // Optimize application
            $this->optimizeApplication();

            // Disable maintenance mode
            $this->disableMaintenanceMode();

            $this->addStep('Update completed successfully!');

            return [
                'success' => true,
                'steps' => $this->updateSteps,
                'new_version' => $this->getCurrentVersion(),
            ];
        } catch (\Exception $e) {
            $this->addStep('Update failed: ' . $e->getMessage());
            $this->disableMaintenanceMode();

            Log::error('Update failed', [
                'error' => $e->getMessage(),
                'steps' => $this->updateSteps,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'steps' => $this->updateSteps,
            ];
        }
    }

    /**
     * Enable maintenance mode
     */
    protected function enableMaintenanceMode(): void
    {
        $this->addStep('Enabling maintenance mode...');
        Artisan::call('down', ['--render' => 'errors::503']);
        $this->maintenanceMode = true;
    }

    /**
     * Disable maintenance mode
     */
    protected function disableMaintenanceMode(): void
    {
        if ($this->maintenanceMode) {
            $this->addStep('Disabling maintenance mode...');
            Artisan::call('up');
            $this->maintenanceMode = false;
        }
    }

    /**
     * Pull latest changes from repository
     */
    protected function pullLatestChanges(): void
    {
        $this->addStep('Pulling latest changes from repository...');

        $result = Process::run('git pull origin main');

        if (!$result->successful()) {
            throw new \Exception('Failed to pull latest changes: ' . $result->errorOutput());
        }

        $this->addStep('Repository updated successfully');
    }

    /**
     * Update dependencies
     */
    protected function updateDependencies(): void
    {
        $this->addStep('Updating dependencies...');

        $result = Process::run('composer install --no-dev --optimize-autoloader');

        if (!$result->successful()) {
            throw new \Exception('Failed to update dependencies: ' . $result->errorOutput());
        }

        $this->addStep('Dependencies updated successfully');
    }

    /**
     * Run database migrations
     */
    protected function runMigrations(): void
    {
        $this->addStep('Running database migrations...');

        Artisan::call('migrate', ['--force' => true]);

        $this->addStep('Database migrations completed');
    }

    /**
     * Clear application caches
     */
    protected function clearCaches(): void
    {
        $this->addStep('Clearing application caches...');

        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        $this->addStep('Caches cleared successfully');
    }

    /**
     * Optimize application
     */
    protected function optimizeApplication(): void
    {
        $this->addStep('Optimizing application...');

        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        $this->addStep('Application optimized successfully');
    }

    /**
     * Add step to update log
     */
    protected function addStep(string $step): void
    {
        $this->updateSteps[] = [
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'step' => $step,
        ];
    }

    /**
     * Get update history from cache
     */
    public function getUpdateHistory(): array
    {
        return Cache::get('updater.history', []);
    }

    /**
     * Save update history to cache
     */
    protected function saveUpdateHistory(array $updateResult): void
    {
        $history = $this->getUpdateHistory();
        $history[] = [
            'timestamp' => now()->toISOString(),
            'from_version' => $this->currentVersion,
            'to_version' => $updateResult['new_version'] ?? $this->currentVersion,
            'success' => $updateResult['success'],
            'steps' => $updateResult['steps'] ?? [],
        ];

        // Keep only last 10 updates
        $history = array_slice($history, -10);

        Cache::put('updater.history', $history, now()->addDays(30));
    }

    /**
     * Get system information
     */
    public function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_time' => now()->toISOString(),
            'memory_usage' => memory_get_usage(true),
            'disk_free_space' => disk_free_space(base_path()),
            'git_branch' => $this->getCurrentBranch(),
        ];
    }

    /**
     * Get current git branch
     */
    protected function getCurrentBranch(): string
    {
        try {
            $result = Process::run('git branch --show-current');
            return $result->successful() ? trim($result->output()) : 'unknown';
        } catch (\Exception $e) {
            return 'unknown';
        }
    }
}
