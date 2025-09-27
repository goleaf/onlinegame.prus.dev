<?php

namespace App\Livewire\Game;

use App\Services\AIService;
use App\Services\GameIntegrationService;
use App\Services\GameNotificationService;
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
        $this->initializeLarautilxRealTime();
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

    /**
     * Initialize Larautilx real-time features
     */
    public function initializeLarautilxRealTime()
    {
        try {
            $userId = Auth::id();
            if ($userId) {
                // Initialize real-time features for the user
                GameIntegrationService::initializeUserRealTime($userId);
                
                $this->dispatch('larautilx-initialized', [
                    'message' => 'Larautilx dashboard real-time features activated',
                    'user_id' => $userId,
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('error', [
                'message' => 'Failed to initialize Larautilx real-time features: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Test components with real-time integration
     */
    public function testComponentsWithIntegration()
    {
        try {
            $this->testComponents();

            $userId = Auth::id();
            if ($userId) {
                // Send notification about component testing
                GameNotificationService::sendNotification(
                    $userId,
                    'components_tested',
                    [
                        'user_id' => $userId,
                        'components' => $this->selectedTestComponents,
                        'timestamp' => now()->toISOString(),
                    ]
                );

                $this->dispatch('components-tested', [
                    'message' => 'Components tested successfully with notifications',
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('error', [
                'message' => 'Failed to test components: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Refresh dashboard with real-time integration
     */
    public function refreshDashboardWithIntegration()
    {
        try {
            $this->refreshDashboard();

            $userId = Auth::id();
            if ($userId) {
                // Send notification about dashboard refresh
                GameNotificationService::sendNotification(
                    $userId,
                    'dashboard_refreshed',
                    [
                        'user_id' => $userId,
                        'timestamp' => now()->toISOString(),
                    ]
                );

                $this->dispatch('dashboard-refreshed', [
                    'message' => 'Dashboard refreshed successfully with notifications',
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('error', [
                'message' => 'Failed to refresh dashboard: ' . $e->getMessage(),
            ]);
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
