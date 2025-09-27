<?php

namespace App\Livewire\Game;

use App\Services\AIService;
use App\Services\LarautilxIntegrationService;
use Illuminate\Support\Facades\Auth;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Utilities\LoggingUtil;
use Livewire\Component;

class LarautilxDashboard extends Component
{
    use ApiResponseTrait;

    public $dashboardData = [];
    public $integrationSummary = [];
    public $isLoading = false;
    public $activeTab = 'overview';
    public $testResults = [];
    public $selectedTestComponents = ['caching', 'filtering', 'pagination', 'logging'];
    public $isTesting = false;

    protected $listeners = [
        'dashboardDataLoaded' => 'handleDashboardDataLoaded',
        'integrationSummaryLoaded' => 'handleIntegrationSummaryLoaded',
        'componentsTested' => 'handleComponentsTested',
    ];

    public function mount()
    {
        $this->loadDashboardData();
        $this->loadIntegrationSummary();
    }

    public function loadDashboardData()
    {
        $this->isLoading = true;

        try {
            $response = $this->makeApiRequest('GET', '/game/api/larautilx/dashboard');

            if ($response && isset($response['data'])) {
                $this->dashboardData = $response['data'];
                $this->dispatch('dashboardDataLoaded', ['data' => $this->dashboardData]);
            }

        } catch (\Exception $e) {
            LoggingUtil::error('Error loading Larautilx dashboard data', [
                'error' => $e->getMessage(),
            ], 'larautilx_dashboard');

            $this->addNotification('Error loading dashboard data: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function loadIntegrationSummary()
    {
        $this->isLoading = true;

        try {
            $response = $this->makeApiRequest('GET', '/game/api/larautilx/integration-summary');

            if ($response && isset($response['data'])) {
                $this->integrationSummary = $response['data'];
                $this->dispatch('integrationSummaryLoaded', ['data' => $this->integrationSummary]);
            }

        } catch (\Exception $e) {
            LoggingUtil::error('Error loading Larautilx integration summary', [
                'error' => $e->getMessage(),
            ], 'larautilx_dashboard');

            $this->addNotification('Error loading integration summary: ' . $e->getMessage(), 'error');
        } finally {
            $this->isLoading = false;
        }
    }

    public function testComponents()
    {
        $this->isTesting = true;

        try {
            $response = $this->makeApiRequest('POST', '/game/api/larautilx/test-components', [
                'components' => $this->selectedTestComponents,
            ]);

            if ($response && isset($response['data'])) {
                $this->testResults = $response['data'];
                $this->dispatch('componentsTested', ['results' => $this->testResults]);
                $this->addNotification('Components tested successfully', 'success');
            }

        } catch (\Exception $e) {
            LoggingUtil::error('Error testing Larautilx components', [
                'error' => $e->getMessage(),
            ], 'larautilx_dashboard');

            $this->addNotification('Error testing components: ' . $e->getMessage(), 'error');
        } finally {
            $this->isTesting = false;
        }
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function refreshDashboard()
    {
        $this->loadDashboardData();
        $this->loadIntegrationSummary();
        $this->addNotification('Dashboard refreshed successfully', 'success');
    }

    public function handleDashboardDataLoaded($data)
    {
        $this->addNotification('Dashboard data loaded successfully', 'success');
    }

    public function handleIntegrationSummaryLoaded($data)
    {
        $this->addNotification('Integration summary loaded successfully', 'success');
    }

    public function handleComponentsTested($data)
    {
        $this->addNotification('Components tested successfully', 'success');
    }

    public function addNotification($message, $type = 'info')
    {
        $this->dispatch('notification', [
            'message' => $message,
            'type' => $type
        ]);
    }

    private function makeApiRequest($method, $url, $data = [])
    {
        try {
            $response = \Http::withHeaders([
                'Authorization' => 'Bearer ' . auth()->user()->createToken('larautilx-dashboard')->plainTextToken,
                'Accept' => 'application/json',
            ])->$method(url($url), $data);

            if ($response->successful()) {
                return $response->json();
            }

            throw new \Exception('API request failed: ' . $response->body());

        } catch (\Exception $e) {
            LoggingUtil::error('API request failed', [
                'method' => $method,
                'url' => $url,
                'error' => $e->getMessage(),
            ], 'larautilx_dashboard');

            throw $e;
        }
    }

    public function render()
    {
        return view('livewire.game.larautilx-dashboard', [
            'availableTestComponents' => [
                'caching' => 'CachingUtil',
                'filtering' => 'FilteringUtil',
                'pagination' => 'PaginationUtil',
                'logging' => 'LoggingUtil',
                'rate_limiting' => 'RateLimiterUtil',
                'config' => 'ConfigUtil',
                'query_parameter' => 'QueryParameterUtil',
                'scheduler' => 'SchedulerUtil',
                'feature_toggle' => 'FeatureToggleUtil',
                'ai' => 'AIService',
            ],
        ]);
    }
}
