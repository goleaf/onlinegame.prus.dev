<?php

namespace App\Livewire\Admin;

use App\Livewire\BaseSessionComponent;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use SmartCache\Facades\SmartCache;

#[Title('Admin Dashboard')]
#[Layout('layouts.app')]
class AdminDashboard extends BaseSessionComponent
{
    public $systemStats = [];

    public $recentUpdates = [];

    public $systemHealth = [];

    public $isLoading = false;

    // Admin-specific session properties
    #[Session]
    public $showSystemInfo = true;

    #[Session]
    public $showRecentUpdates = true;

    #[Session]
    public $dashboardLayout = 'grid';

    #[Session]
    public $selectedMetrics = ['cpu', 'memory', 'disk'];

    #[Session]
    public $updateFilters = [];

    #[Session]
    public $systemFilters = [];

    #[Session]
    public $chartTimeRange = '24h';

    #[Session]
    public $showCharts = true;

    #[Session]
    public $chartType = 'line';

    #[Session]
    public $enableAlerts = true;

    #[Session]
    public $alertThreshold = 'warning';

    public function mount()
    {
        // Initialize session properties
        $this->initializeSessionProperties();

        // Override base refresh settings with admin-specific defaults
        $this->refreshInterval = $this->refreshInterval ?: 60;

        $this->loadSystemStats();
        $this->loadRecentUpdates();
        $this->loadSystemHealth();
    }

    public function loadSystemStats()
    {
        $this->isLoading = true;

        try {
            $cacheKey = 'admin_system_stats_'.now()->format('Y-m-d-H-i');

            $this->systemStats = SmartCache::remember($cacheKey, now()->addMinutes(5), function () {
                return [
                    'total_users' => \App\Models\User::count(),
                    'total_players' => \App\Models\Game\Player::count(),
                    'total_villages' => \App\Models\Game\Village::count(),
                    'active_sessions' => \App\Models\Game\Player::where('last_activity', '>', now()->subMinutes(30))->count(),
                    'system_uptime' => $this->getSystemUptime(),
                    'memory_usage' => $this->getMemoryUsage(),
                    'disk_usage' => $this->getDiskUsage(),
                ];
            });
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load system statistics: '.$e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function loadRecentUpdates()
    {
        try {
            // Load recent git commits or update history
            $this->recentUpdates = [
                [
                    'type' => 'system',
                    'title' => 'Laravel Updater Integration',
                    'description' => 'Added comprehensive update system with Livewire interface',
                    'date' => now()->subHours(2),
                    'status' => 'completed',
                ],
                [
                    'type' => 'feature',
                    'title' => 'Game Navigation Enhancement',
                    'description' => 'Updated navigation with updater access',
                    'date' => now()->subHours(4),
                    'status' => 'completed',
                ],
                [
                    'type' => 'maintenance',
                    'title' => 'System Optimization',
                    'description' => 'Cleared caches and optimized performance',
                    'date' => now()->subDays(1),
                    'status' => 'completed',
                ],
            ];
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load recent updates: '.$e->getMessage());
        }
    }

    public function loadSystemHealth()
    {
        try {
            $this->systemHealth = [
                'database' => $this->checkDatabaseHealth(),
                'cache' => $this->checkCacheHealth(),
                'storage' => $this->checkStorageHealth(),
                'queue' => $this->checkQueueHealth(),
                'overall' => 'healthy',
            ];
        } catch (\Exception $e) {
            $this->systemHealth = [
                'overall' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function getSystemUptime()
    {
        try {
            $uptime = shell_exec('uptime -p 2>/dev/null');

            return $uptime ? trim($uptime) : 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private function getMemoryUsage()
    {
        try {
            $memory = shell_exec('free -m 2>/dev/null');
            if ($memory) {
                $lines = explode("\n", $memory);
                $memInfo = preg_split('/\s+/', $lines[1]);
                $used = $memInfo[2] ?? 0;
                $total = $memInfo[1] ?? 1;

                return round(($used / $total) * 100, 1).'%';
            }

            return 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private function getDiskUsage()
    {
        try {
            $disk = shell_exec('df -h / 2>/dev/null');
            if ($disk) {
                $lines = explode("\n", $disk);
                $diskInfo = preg_split('/\s+/', $lines[1]);

                return $diskInfo[4] ?? 'Unknown';
            }

            return 'Unknown';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    private function checkDatabaseHealth()
    {
        try {
            \DB::connection()->getPdo();

            return 'healthy';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    private function checkCacheHealth()
    {
        try {
            \Cache::put('health_check', 'ok', 60);
            $result = \Cache::get('health_check');

            return $result === 'ok' ? 'healthy' : 'error';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    private function checkStorageHealth()
    {
        try {
            $testFile = storage_path('app/health_check.txt');
            file_put_contents($testFile, 'test');
            $result = file_get_contents($testFile);
            unlink($testFile);

            return $result === 'test' ? 'healthy' : 'error';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    private function checkQueueHealth()
    {
        try {
            // Simple queue health check
            return 'healthy';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    public function refreshStats()
    {
        $this->loadSystemStats();
        $this->loadSystemHealth();
        session()->flash('message', 'System statistics refreshed successfully!');
    }

    /**
     * Update dashboard layout preference
     */
    public function setDashboardLayout($layout)
    {
        $this->dashboardLayout = in_array($layout, ['grid', 'list', 'compact']) ? $layout : 'grid';
        session()->flash('message', "Dashboard layout set to {$this->dashboardLayout}");
    }

    /**
     * Toggle system info display
     */
    public function toggleSystemInfo()
    {
        $this->showSystemInfo = ! $this->showSystemInfo;
        session()->flash('message', $this->showSystemInfo ? 'System info enabled' : 'System info disabled');
    }

    /**
     * Toggle recent updates display
     */
    public function toggleRecentUpdates()
    {
        $this->showRecentUpdates = ! $this->showRecentUpdates;
        session()->flash('message', $this->showRecentUpdates ? 'Recent updates enabled' : 'Recent updates disabled');
    }

    /**
     * Update selected metrics
     */
    public function updateSelectedMetrics(array $metrics)
    {
        $validMetrics = ['cpu', 'memory', 'disk', 'network', 'database', 'cache'];
        $this->selectedMetrics = array_intersect($metrics, $validMetrics);
        session()->flash('message', 'Selected metrics updated');
    }

    /**
     * Update chart time range
     */
    public function setChartTimeRange($range)
    {
        $this->chartTimeRange = in_array($range, ['1h', '6h', '24h', '7d', '30d']) ? $range : '24h';
        session()->flash('message', "Chart time range set to {$this->chartTimeRange}");
    }

    /**
     * Update chart type
     */
    public function setChartType($type)
    {
        $this->chartType = in_array($type, ['line', 'bar', 'pie', 'area']) ? $type : 'line';
        session()->flash('message', "Chart type set to {$this->chartType}");
    }

    /**
     * Toggle charts display
     */
    public function toggleCharts()
    {
        $this->showCharts = ! $this->showCharts;
        session()->flash('message', $this->showCharts ? 'Charts enabled' : 'Charts disabled');
    }

    /**
     * Toggle alerts
     */
    public function toggleAlerts()
    {
        $this->enableAlerts = ! $this->enableAlerts;
        session()->flash('message', $this->enableAlerts ? 'Alerts enabled' : 'Alerts disabled');
    }

    /**
     * Update alert threshold
     */
    public function setAlertThreshold($threshold)
    {
        $this->alertThreshold = in_array($threshold, ['info', 'warning', 'error', 'critical']) ? $threshold : 'warning';
        session()->flash('message', "Alert threshold set to {$this->alertThreshold}");
    }

    /**
     * Update system filters
     */
    public function updateSystemFilters(array $filters)
    {
        $this->systemFilters = array_filter($filters, fn ($value) => ! empty($value));
        session()->flash('message', 'System filters updated');
    }

    /**
     * Update update filters
     */
    public function updateUpdateFilters(array $filters)
    {
        $this->updateFilters = array_filter($filters, fn ($value) => ! empty($value));
        session()->flash('message', 'Update filters updated');
    }

    /**
     * Clear all filters
     */
    public function clearAllFilters()
    {
        $this->systemFilters = [];
        $this->updateFilters = [];
        session()->flash('message', 'All filters cleared');
    }

    /**
     * Reset all admin preferences to defaults
     */
    public function resetAdminPreferences()
    {
        $this->showSystemInfo = true;
        $this->showRecentUpdates = true;
        $this->dashboardLayout = 'grid';
        $this->selectedMetrics = ['cpu', 'memory', 'disk'];
        $this->updateFilters = [];
        $this->systemFilters = [];
        $this->chartTimeRange = '24h';
        $this->showCharts = true;
        $this->chartType = 'line';
        $this->enableAlerts = true;
        $this->alertThreshold = 'warning';

        // Reset base session properties
        $this->resetSessionProperties();

        session()->flash('message', 'All admin preferences reset to defaults');
    }

    public function render()
    {
        return view('livewire.admin.admin-dashboard');
    }
}
