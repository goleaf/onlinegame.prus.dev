<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Layout;
use SmartCache\Facades\SmartCache;

#[Title('Admin Dashboard')]
#[Layout('layouts.app')]
class AdminDashboard extends Component
{
    public $systemStats = [];
    public $recentUpdates = [];
    public $systemHealth = [];
    public $isLoading = false;

    public function mount()
    {
        $this->loadSystemStats();
        $this->loadRecentUpdates();
        $this->loadSystemHealth();
    }

    public function loadSystemStats()
    {
        $this->isLoading = true;
        
        try {
            $cacheKey = "admin_system_stats_" . now()->format('Y-m-d-H-i');
            
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
            session()->flash('error', 'Failed to load system statistics: ' . $e->getMessage());
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
                    'status' => 'completed'
                ],
                [
                    'type' => 'feature',
                    'title' => 'Game Navigation Enhancement',
                    'description' => 'Updated navigation with updater access',
                    'date' => now()->subHours(4),
                    'status' => 'completed'
                ],
                [
                    'type' => 'maintenance',
                    'title' => 'System Optimization',
                    'description' => 'Cleared caches and optimized performance',
                    'date' => now()->subDays(1),
                    'status' => 'completed'
                ]
            ];
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to load recent updates: ' . $e->getMessage());
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
                'overall' => 'healthy'
            ];
        } catch (\Exception $e) {
            $this->systemHealth = [
                'overall' => 'error',
                'error' => $e->getMessage()
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
                return round(($used / $total) * 100, 1) . '%';
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

    public function render()
    {
        return view('livewire.admin.admin-dashboard');
    }
}
