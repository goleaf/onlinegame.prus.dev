<?php

namespace App\Livewire\Game;

use App\Utilities\LoggingUtil;
use LaraUtilX\Traits\ApiResponseTrait;
use Livewire\Component;

class SystemManagement extends Component
{
    use ApiResponseTrait;

    public $systemConfig = [];

    public $scheduledTasks = [];

    public $systemHealth = [];

    public $systemMetrics = [];

    public $systemLogs = [];

    public $isLoading = false;

    public $activeTab = 'health';

    public $logLevel = 'info';

    public $logLimit = 100;

    public $cacheTypes = ['config', 'route', 'view', 'application'];

    public $selectedCacheTypes = [];

    protected $listeners = [
        'systemConfigUpdated' => 'handleConfigUpdate',
        'cachesCleared' => 'handleCachesCleared',
        'logsRefreshed' => 'handleLogsRefreshed',
    ];

    public function mount()
    {
        $this->loadSystemHealth();
        $this->loadSystemMetrics();
    }

    public function loadSystemConfig()
    {
        $this->isLoading = true;

        try {
            $response = $this->makeApiRequest('GET', '/game/api/system/config', [
                'include_app' => 'true',
                'section' => 'game',
            ]);

            if ($response && isset($response['data'])) {
                $this->systemConfig = $response['data'];
            }
        } catch (\Exception $e) {
            LoggingUtil::error('Error loading system configuration', [
                'error' => $e->getMessage(),
            ], 'system_management');

            $this->addNotification('Error loading system configuration: '.$e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function loadScheduledTasks()
    {
        $this->isLoading = true;

        try {
            $response = $this->makeApiRequest('GET', '/game/api/system/scheduled-tasks');

            if ($response && isset($response['data'])) {
                $this->scheduledTasks = $response['data'];
            }
        } catch (\Exception $e) {
            LoggingUtil::error('Error loading scheduled tasks', [
                'error' => $e->getMessage(),
            ], 'system_management');

            $this->addNotification('Error loading scheduled tasks: '.$e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function loadSystemHealth()
    {
        $this->isLoading = true;

        try {
            $response = $this->makeApiRequest('GET', '/game/api/system/health');

            if ($response && isset($response['data'])) {
                $this->systemHealth = $response['data'];
            }
        } catch (\Exception $e) {
            LoggingUtil::error('Error loading system health', [
                'error' => $e->getMessage(),
            ], 'system_management');

            $this->addNotification('Error loading system health: '.$e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function loadSystemMetrics()
    {
        $this->isLoading = true;

        try {
            $response = $this->makeApiRequest('GET', '/game/api/system/metrics');

            if ($response && isset($response['data'])) {
                $this->systemMetrics = $response['data'];
            }
        } catch (\Exception $e) {
            LoggingUtil::error('Error loading system metrics', [
                'error' => $e->getMessage(),
            ], 'system_management');

            $this->addNotification('Error loading system metrics: '.$e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function loadSystemLogs()
    {
        $this->isLoading = true;

        try {
            $response = $this->makeApiRequest('GET', '/game/api/system/logs', [
                'level' => $this->logLevel,
                'limit' => $this->logLimit,
                'since' => now()->subHours(24)->toDateTimeString(),
            ]);

            if ($response && isset($response['data'])) {
                $this->systemLogs = $response['data'];
            }
        } catch (\Exception $e) {
            LoggingUtil::error('Error loading system logs', [
                'error' => $e->getMessage(),
            ], 'system_management');

            $this->addNotification('Error loading system logs: '.$e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function updateSystemConfig($key, $value)
    {
        try {
            $response = $this->makeApiRequest('PUT', '/game/api/system/config', [
                'key' => $key,
                'value' => $value,
            ]);

            if ($response && isset($response['data'])) {
                $this->addNotification('System configuration updated successfully', 'success');
                $this->loadSystemConfig();
                $this->dispatch('systemConfigUpdated', ['key' => $key, 'value' => $value]);
            }
        } catch (\Exception $e) {
            LoggingUtil::error('Error updating system configuration', [
                'error' => $e->getMessage(),
                'key' => $key,
                'value' => $value,
            ], 'system_management');

            $this->addNotification('Error updating system configuration: '.$e->getMessage(), 'error');
        }
    }

    public function clearSystemCaches()
    {
        try {
            $response = $this->makeApiRequest('POST', '/game/api/system/clear-caches', [
                'cache_types' => $this->selectedCacheTypes,
            ]);

            if ($response && isset($response['data'])) {
                $this->addNotification('System caches cleared successfully', 'success');
                $this->dispatch('cachesCleared', ['cleared_caches' => $response['data']['cleared_caches']]);
            }
        } catch (\Exception $e) {
            LoggingUtil::error('Error clearing system caches', [
                'error' => $e->getMessage(),
                'cache_types' => $this->selectedCacheTypes,
            ], 'system_management');

            $this->addNotification('Error clearing system caches: '.$e->getMessage(), 'error');
        }
    }

    public function refreshLogs()
    {
        $this->loadSystemLogs();
        $this->dispatch('logsRefreshed');
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;

        switch ($tab) {
            case 'config':
                $this->loadSystemConfig();

                break;
            case 'tasks':
                $this->loadScheduledTasks();

                break;
            case 'health':
                $this->loadSystemHealth();

                break;
            case 'metrics':
                $this->loadSystemMetrics();

                break;
            case 'logs':
                $this->loadSystemLogs();

                break;
        }
    }

    public function updatedLogLevel()
    {
        if ($this->activeTab === 'logs') {
            $this->loadSystemLogs();
        }
    }

    public function updatedLogLimit()
    {
        if ($this->activeTab === 'logs') {
            $this->loadSystemLogs();
        }
    }

    public function handleConfigUpdate($data)
    {
        $this->addNotification("Configuration updated: {$data['key']}", 'success');
    }

    public function handleCachesCleared($data)
    {
        $this->addNotification('Caches cleared successfully', 'success');
    }

    public function handleLogsRefreshed()
    {
        $this->addNotification('Logs refreshed successfully', 'success');
    }

    public function addNotification($message, $type = 'info')
    {
        $this->dispatch('notification', [
            'message' => $message,
            'type' => $type,
        ]);
    }

    private function makeApiRequest($method, $url, $data = [])
    {
        try {
            $response = \Http::withHeaders([
                'Authorization' => 'Bearer '.auth()->user()->createToken('system-management')->plainTextToken,
                'Accept' => 'application/json',
            ])->$method(url($url), $data);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('API request failed: '.$response->body());
        } catch (\Exception $e) {
            LoggingUtil::error('API request failed', [
                'method' => $method,
                'url' => $url,
                'error' => $e->getMessage(),
            ], 'system_management');

            throw $e;
        }
    }

    public function render()
    {
        return view('livewire.game.system-management', [
            'logLevels' => ['all', 'debug', 'info', 'warning', 'error', 'critical'],
            'cacheTypes' => $this->cacheTypes,
        ]);
    }
}
