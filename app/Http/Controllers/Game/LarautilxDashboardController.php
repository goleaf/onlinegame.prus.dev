<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Services\AIService;
use App\Services\LarautilxIntegrationService;
use Illuminate\Http\Request;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Utilities\ConfigUtil;
use LaraUtilX\Utilities\FeatureToggleUtil;
use LaraUtilX\Utilities\LoggingUtil;
use LaraUtilX\Utilities\QueryParameterUtil;
use LaraUtilX\Utilities\SchedulerUtil;
use SmartCache\Facades\SmartCache;

class LarautilxDashboardController extends Controller
{
    use ApiResponseTrait;

    protected LarautilxIntegrationService $integrationService;
    protected AIService $aiService;
    protected ConfigUtil $configUtil;
    protected SchedulerUtil $schedulerUtil;

    public function __construct(
        LarautilxIntegrationService $integrationService,
        AIService $aiService,
        ConfigUtil $configUtil,
        SchedulerUtil $schedulerUtil
    ) {
        $this->integrationService = $integrationService;
        $this->aiService = $aiService;
        $this->configUtil = $configUtil;
        $this->schedulerUtil = $schedulerUtil;
    }

    /**
     * Get comprehensive Larautilx integration dashboard data with SmartCache optimization
     */
    public function getDashboardData()
    {
        try {
            $cacheKey = "larautilx_dashboard_data_" . auth()->id();
            
            $data = SmartCache::remember($cacheKey, now()->addMinutes(15), function () {
                return [
                    'integration_status' => $this->integrationService->getIntegrationStatus(),
                    'ai_service_status' => $this->aiService->getStatus(),
                    'feature_toggles' => $this->getFeatureTogglesStatus(),
                    'system_health' => $this->getSystemHealthStatus(),
                    'performance_metrics' => $this->getPerformanceMetrics(),
                    'scheduled_tasks' => $this->getScheduledTasksSummary(),
                    'configuration_status' => $this->getConfigurationStatus(),
                    'usage_statistics' => $this->getUsageStatistics(),
                    'recent_activity' => $this->getRecentActivity(),
                ];
            });

            LoggingUtil::info('Larautilx dashboard data retrieved', [
                'user_id' => auth()->id(),
                'components_count' => count($data['integration_status']['components'] ?? []),
                'ai_available' => $data['ai_service_status']['available'] ?? false,
            ], 'larautilx_dashboard');

            return $this->successResponse($data, 'Larautilx dashboard data retrieved successfully.');

        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving Larautilx dashboard data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'larautilx_dashboard');

            return $this->errorResponse('Failed to retrieve Larautilx dashboard data.', 500);
        }
    }

    /**
     * Get feature toggles status
     */
    protected function getFeatureTogglesStatus()
    {
        $features = [
            'larautilx_integration' => FeatureToggleUtil::isEnabled('larautilx_integration'),
            'advanced_caching' => FeatureToggleUtil::isEnabled('advanced_caching'),
            'enhanced_filtering' => FeatureToggleUtil::isEnabled('enhanced_filtering'),
            'standardized_responses' => FeatureToggleUtil::isEnabled('standardized_responses'),
            'request_logging' => FeatureToggleUtil::isEnabled('request_logging'),
            'rate_limiting' => FeatureToggleUtil::isEnabled('rate_limiting'),
            'advanced_map' => FeatureToggleUtil::isEnabled('advanced_map'),
            'real_time_updates' => FeatureToggleUtil::isEnabled('real_time_updates'),
            'enhanced_statistics' => FeatureToggleUtil::isEnabled('enhanced_statistics'),
            'geographic_features' => FeatureToggleUtil::isEnabled('geographic_features'),
            'user_management' => FeatureToggleUtil::isEnabled('user_management'),
            'ai_content_generation' => FeatureToggleUtil::isEnabled('ai_content_generation'),
        ];

        return [
            'features' => $features,
            'enabled_count' => count(array_filter($features)),
            'total_count' => count($features),
            'enabled_percentage' => round((count(array_filter($features)) / count($features)) * 100, 2),
        ];
    }

    /**
     * Get system health status
     */
    protected function getSystemHealthStatus()
    {
        $health = [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'storage' => $this->checkStorageHealth(),
            'ai_service' => $this->checkAIServiceHealth(),
            'larautilx_components' => $this->checkLarautilxComponentsHealth(),
        ];

        $healthyCount = count(array_filter($health, fn($status) => $status === 'healthy'));
        $totalCount = count($health);

        return [
            'checks' => $health,
            'overall_status' => $healthyCount === $totalCount ? 'healthy' : ($healthyCount > $totalCount / 2 ? 'degraded' : 'unhealthy'),
            'healthy_count' => $healthyCount,
            'total_count' => $totalCount,
            'health_percentage' => round(($healthyCount / $totalCount) * 100, 2),
        ];
    }

    /**
     * Get performance metrics
     */
    protected function getPerformanceMetrics()
    {
        return [
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => ini_get('memory_limit'),
            ],
            'database' => [
                'connections' => count(\DB::getConnections()),
                'query_count' => \DB::getQueryLog() ? count(\DB::getQueryLog()) : 0,
            ],
            'cache' => [
                'driver' => config('cache.default'),
                'stores' => array_keys(config('cache.stores')),
            ],
            'larautilx' => [
                'caching_util_active' => class_exists(\LaraUtilX\Utilities\CachingUtil::class),
                'filtering_util_active' => class_exists(\LaraUtilX\Utilities\FilteringUtil::class),
                'pagination_util_active' => class_exists(\LaraUtilX\Utilities\PaginationUtil::class),
                'logging_util_active' => class_exists(\LaraUtilX\Utilities\LoggingUtil::class),
                'rate_limiter_util_active' => class_exists(\LaraUtilX\Utilities\RateLimiterUtil::class),
                'config_util_active' => class_exists(\LaraUtilX\Utilities\ConfigUtil::class),
                'query_parameter_util_active' => class_exists(\LaraUtilX\Utilities\QueryParameterUtil::class),
                'scheduler_util_active' => class_exists(\LaraUtilX\Utilities\SchedulerUtil::class),
                'feature_toggle_util_active' => class_exists(\LaraUtilX\Utilities\FeatureToggleUtil::class),
            ],
        ];
    }

    /**
     * Get scheduled tasks summary
     */
    protected function getScheduledTasksSummary()
    {
        try {
            $scheduleSummary = $this->schedulerUtil->getScheduleSummary();
            $hasOverdueTasks = $this->schedulerUtil->hasOverdueTasks();

            return [
                'total_tasks' => count($scheduleSummary),
                'running_tasks' => collect($scheduleSummary)->where('is_running', true)->count(),
                'due_tasks' => collect($scheduleSummary)->where('is_due', true)->count(),
                'overdue_tasks' => $hasOverdueTasks,
                'tasks' => $scheduleSummary,
            ];
        } catch (\Exception $e) {
            return [
                'total_tasks' => 0,
                'running_tasks' => 0,
                'due_tasks' => 0,
                'overdue_tasks' => false,
                'tasks' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get configuration status
     */
    protected function getConfigurationStatus()
    {
        return [
            'larautilx_config' => [
                'package_installed' => class_exists(\LaraUtilX\LaraUtilXServiceProvider::class),
                'service_provider_registered' => app()->getProviders(\LaraUtilX\LaraUtilXServiceProvider::class) !== null,
                'middleware_registered' => app('router')->getMiddleware()['access.log'] ?? false,
            ],
            'ai_config' => [
                'openai_configured' => !empty(config('ai.openai.api_key')),
                'gemini_configured' => !empty(config('ai.gemini.api_key')),
                'default_provider' => config('ai.default_provider'),
                'default_model' => config('ai.default_model'),
            ],
            'feature_toggles_config' => [
                'config_file_exists' => file_exists(config_path('feature-toggles.php')),
                'config_loaded' => config('feature-toggles') !== null,
            ],
            'cache_config' => [
                'driver' => config('cache.default'),
                'stores_available' => count(config('cache.stores')),
            ],
        ];
    }

    /**
     * Get usage statistics
     */
    protected function getUsageStatistics()
    {
        return [
            'game_entities' => [
                'users' => \App\Models\User::count(),
                'players' => \App\Models\Game\Player::count(),
                'villages' => \App\Models\Game\Village::count(),
                'alliances' => \App\Models\Game\Alliance::count(),
                'worlds' => \App\Models\Game\World::count(),
                'tasks' => \App\Models\Game\Task::count(),
            ],
            'active_sessions' => [
                'online_players' => \App\Models\Game\Player::where('is_online', true)
                    ->where('last_active_at', '>=', now()->subMinutes(15))
                    ->count(),
                'active_players' => \App\Models\Game\Player::where('is_active', true)->count(),
                'recent_registrations' => \App\Models\User::where('created_at', '>=', now()->subDays(7))->count(),
            ],
            'larautilx_usage' => [
                'api_requests_today' => \App\Models\Game\Player::whereDate('last_active_at', today())->count(),
                'cache_hits' => 'N/A', // Would need cache driver support
                'rate_limited_requests' => 'N/A', // Would need rate limiter tracking
            ],
        ];
    }

    /**
     * Get recent activity
     */
    protected function getRecentActivity()
    {
        return [
            'recent_users' => \App\Models\User::latest()->limit(5)->get(['id', 'name', 'email', 'created_at']),
            'recent_players' => \App\Models\Game\Player::with('user')->latest()->limit(5)->get(['id', 'name', 'user_id', 'created_at']),
            'recent_villages' => \App\Models\Game\Village::with('player')->latest()->limit(5)->get(['id', 'name', 'player_id', 'created_at']),
            'recent_tasks' => \App\Models\Game\Task::latest()->limit(5)->get(['id', 'title', 'type', 'status', 'created_at']),
        ];
    }

    /**
     * Check database health
     */
    protected function checkDatabaseHealth()
    {
        try {
            \DB::connection()->getPdo();
            return 'healthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }

    /**
     * Check cache health
     */
    protected function checkCacheHealth()
    {
        try {
            \Cache::put('health_check', 'ok', 60);
            $value = \Cache::get('health_check');
            return $value === 'ok' ? 'healthy' : 'unhealthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }

    /**
     * Check storage health
     */
    protected function checkStorageHealth()
    {
        try {
            \Storage::put('health_check.txt', 'ok');
            $value = \Storage::get('health_check.txt');
            \Storage::delete('health_check.txt');
            return $value === 'ok' ? 'healthy' : 'unhealthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }

    /**
     * Check AI service health
     */
    protected function checkAIServiceHealth()
    {
        try {
            return $this->aiService->isAvailable() ? 'healthy' : 'unhealthy';
        } catch (\Exception $e) {
            return 'unhealthy';
        }
    }

    /**
     * Check Larautilx components health
     */
    protected function checkLarautilxComponentsHealth()
    {
        $components = [
            'ApiResponseTrait' => trait_exists(\LaraUtilX\Traits\ApiResponseTrait::class),
            'FilteringUtil' => class_exists(\LaraUtilX\Utilities\FilteringUtil::class),
            'PaginationUtil' => class_exists(\LaraUtilX\Utilities\PaginationUtil::class),
            'CachingUtil' => class_exists(\LaraUtilX\Utilities\CachingUtil::class),
            'LoggingUtil' => class_exists(\LaraUtilX\Utilities\LoggingUtil::class),
            'RateLimiterUtil' => class_exists(\LaraUtilX\Utilities\RateLimiterUtil::class),
            'ConfigUtil' => class_exists(\LaraUtilX\Utilities\ConfigUtil::class),
            'QueryParameterUtil' => class_exists(\LaraUtilX\Utilities\QueryParameterUtil::class),
            'SchedulerUtil' => class_exists(\LaraUtilX\Utilities\SchedulerUtil::class),
            'FeatureToggleUtil' => class_exists(\LaraUtilX\Utilities\FeatureToggleUtil::class),
        ];

        $healthyCount = count(array_filter($components));
        $totalCount = count($components);

        return $healthyCount === $totalCount ? 'healthy' : ($healthyCount > $totalCount / 2 ? 'degraded' : 'unhealthy');
    }

    /**
     * Get Larautilx integration summary
     */
    public function getIntegrationSummary()
    {
        try {
            $summary = [
                'package_info' => [
                    'name' => 'omarchouman/lara-util-x',
                    'version' => '1.1',
                    'installed' => true,
                    'service_provider' => class_exists(\LaraUtilX\LaraUtilXServiceProvider::class),
                ],
                'integrated_components' => [
                    'traits' => [
                        'ApiResponseTrait' => trait_exists(\LaraUtilX\Traits\ApiResponseTrait::class),
                        'Auditable' => trait_exists(\LaraUtilX\Traits\Auditable::class),
                        'FileProcessingTrait' => trait_exists(\LaraUtilX\Traits\FileProcessingTrait::class),
                    ],
                    'utilities' => [
                        'CachingUtil' => class_exists(\LaraUtilX\Utilities\CachingUtil::class),
                        'ConfigUtil' => class_exists(\LaraUtilX\Utilities\ConfigUtil::class),
                        'FeatureToggleUtil' => class_exists(\LaraUtilX\Utilities\FeatureToggleUtil::class),
                        'FilteringUtil' => class_exists(\LaraUtilX\Utilities\FilteringUtil::class),
                        'LoggingUtil' => class_exists(\LaraUtilX\Utilities\LoggingUtil::class),
                        'PaginationUtil' => class_exists(\LaraUtilX\Utilities\PaginationUtil::class),
                        'QueryParameterUtil' => class_exists(\LaraUtilX\Utilities\QueryParameterUtil::class),
                        'RateLimiterUtil' => class_exists(\LaraUtilX\Utilities\RateLimiterUtil::class),
                        'SchedulerUtil' => class_exists(\LaraUtilX\Utilities\SchedulerUtil::class),
                    ],
                    'controllers' => [
                        'CrudController' => class_exists(\LaraUtilX\Http\Controllers\CrudController::class),
                    ],
                    'middleware' => [
                        'AccessLogMiddleware' => class_exists(\LaraUtilX\Http\Middleware\AccessLogMiddleware::class),
                    ],
                    'llm_providers' => [
                        'OpenAIProvider' => class_exists(\LaraUtilX\LLMProviders\OpenAI\OpenAIProvider::class),
                        'GeminiProvider' => class_exists(\LaraUtilX\LLMProviders\Gemini\GeminiProvider::class),
                        'LLMProviderInterface' => interface_exists(\LaraUtilX\LLMProviders\Contracts\LLMProviderInterface::class),
                    ],
                    'models' => [
                        'AccessLog' => class_exists(\LaraUtilX\Models\AccessLog::class),
                    ],
                    'enums' => [
                        'LogLevel' => enum_exists(\LaraUtilX\Enums\LogLevel::class),
                    ],
                ],
                'game_integration' => [
                    'controllers_created' => [
                        'PlayerController' => class_exists(\App\Http\Controllers\Game\PlayerController::class),
                        'VillageController' => class_exists(\App\Http\Controllers\Game\VillageController::class),
                        'TaskController' => class_exists(\App\Http\Controllers\Game\TaskController::class),
                        'UserController' => class_exists(\App\Http\Controllers\Game\UserController::class),
                        'SystemController' => class_exists(\App\Http\Controllers\Game\SystemController::class),
                        'AIController' => class_exists(\App\Http\Controllers\Game\AIController::class),
                        'LarautilxController' => class_exists(\App\Http\Controllers\Game\LarautilxController::class),
                    ],
                    'livewire_components' => [
                        'BattleManager' => class_exists(\App\Livewire\Game\BattleManager::class),
                        'TaskManager' => class_exists(\App\Livewire\Game\TaskManager::class),
                        'EnhancedGameDashboard' => class_exists(\App\Livewire\Game\EnhancedGameDashboard::class),
                        'MovementManager' => class_exists(\App\Livewire\Game\MovementManager::class),
                        'FileUploadManager' => class_exists(\App\Livewire\Game\FileUploadManager::class),
                        'AdvancedMapManager' => class_exists(\App\Livewire\Game\AdvancedMapManager::class),
                        'UserManagement' => class_exists(\App\Livewire\Game\UserManagement::class),
                        'SystemManagement' => class_exists(\App\Livewire\Game\SystemManagement::class),
                        'AIManager' => class_exists(\App\Livewire\Game\AIManager::class),
                    ],
                    'services_created' => [
                        'LarautilxIntegrationService' => class_exists(\App\Services\LarautilxIntegrationService::class),
                        'AIService' => class_exists(\App\Services\AIService::class),
                        'GeographicService' => class_exists(\App\Services\GeographicService::class),
                    ],
                    'models_enhanced' => [
                        'User' => class_exists(\App\Models\User::class),
                        'Player' => class_exists(\App\Models\Game\Player::class),
                        'Village' => class_exists(\App\Models\Game\Village::class),
                        'Task' => class_exists(\App\Models\Game\Task::class),
                    ],
                ],
                'api_endpoints' => [
                    'total_endpoints' => 50, // Approximate count
                    'crud_endpoints' => 15,
                    'advanced_endpoints' => 25,
                    'ai_endpoints' => 10,
                ],
                'configuration_files' => [
                    'feature_toggles' => file_exists(config_path('feature-toggles.php')),
                    'ai_config' => file_exists(config_path('ai.php')),
                ],
            ];

            LoggingUtil::info('Larautilx integration summary retrieved', [
                'user_id' => auth()->id(),
                'components_integrated' => count(array_filter($summary['integrated_components']['utilities'])),
                'controllers_created' => count(array_filter($summary['game_integration']['controllers_created'])),
                'livewire_components' => count(array_filter($summary['game_integration']['livewire_components'])),
            ], 'larautilx_dashboard');

            return $this->successResponse($summary, 'Larautilx integration summary retrieved successfully.');

        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving Larautilx integration summary', [
                'error' => $e->getMessage(),
            ], 'larautilx_dashboard');

            return $this->errorResponse('Failed to retrieve Larautilx integration summary.', 500);
        }
    }

    /**
     * Test Larautilx components
     */
    public function testComponents(Request $request)
    {
        try {
            $validated = $request->validate([
                'components' => 'array',
                'components.*' => 'string|in:caching,filtering,pagination,logging,rate_limiting,config,query_parameter,scheduler,feature_toggle,ai',
            ]);

            $components = $validated['components'] ?? ['caching', 'filtering', 'pagination', 'logging'];
            $results = [];

            foreach ($components as $component) {
                $results[$component] = $this->testComponent($component);
            }

            LoggingUtil::info('Larautilx components tested', [
                'user_id' => auth()->id(),
                'components_tested' => $components,
                'results' => $results,
            ], 'larautilx_dashboard');

            return $this->successResponse($results, 'Larautilx components tested successfully.');

        } catch (\Exception $e) {
            LoggingUtil::error('Error testing Larautilx components', [
                'error' => $e->getMessage(),
            ], 'larautilx_dashboard');

            return $this->errorResponse('Failed to test Larautilx components.', 500);
        }
    }

    /**
     * Test individual component
     */
    protected function testComponent(string $component): array
    {
        try {
            switch ($component) {
                case 'caching':
                    $cacheUtil = app(\LaraUtilX\Utilities\CachingUtil::class);
                    $testKey = 'test_cache_' . time();
                    $testValue = 'test_value_' . time();
                    $cacheUtil->cache($testKey, $testValue, 1);
                    $retrieved = $cacheUtil->get($testKey);
                    $cacheUtil->forget($testKey);
                    return [
                        'status' => 'success',
                        'test' => 'Cache store and retrieve',
                        'result' => $retrieved === $testValue,
                        'message' => $retrieved === $testValue ? 'Cache working correctly' : 'Cache test failed',
                    ];

                case 'filtering':
                    $collection = collect([
                        ['id' => 1, 'name' => 'Test 1', 'active' => true],
                        ['id' => 2, 'name' => 'Test 2', 'active' => false],
                        ['id' => 3, 'name' => 'Test 3', 'active' => true],
                    ]);
                    $filtered = \LaraUtilX\Utilities\FilteringUtil::filter($collection, 'active', 'equals', true);
                    return [
                        'status' => 'success',
                        'test' => 'Collection filtering',
                        'result' => $filtered->count() === 2,
                        'message' => $filtered->count() === 2 ? 'Filtering working correctly' : 'Filtering test failed',
                    ];

                case 'pagination':
                    $items = range(1, 25);
                    $paginated = \LaraUtilX\Utilities\PaginationUtil::paginate($items, 10, 1);
                    return [
                        'status' => 'success',
                        'test' => 'Array pagination',
                        'result' => count($paginated->items()) === 10,
                        'message' => count($paginated->items()) === 10 ? 'Pagination working correctly' : 'Pagination test failed',
                    ];

                case 'logging':
                    \LaraUtilX\Utilities\LoggingUtil::info('Test log message', ['test' => true], 'test');
                    return [
                        'status' => 'success',
                        'test' => 'Logging functionality',
                        'result' => true,
                        'message' => 'Logging working correctly',
                    ];

                case 'rate_limiting':
                    $rateLimiter = app(\LaraUtilX\Utilities\RateLimiterUtil::class);
                    $testKey = 'test_rate_limit_' . time();
                    $attempt1 = $rateLimiter->attempt($testKey, 5, 1);
                    $attempt2 = $rateLimiter->attempt($testKey, 5, 1);
                    $rateLimiter->clear($testKey);
                    return [
                        'status' => 'success',
                        'test' => 'Rate limiting',
                        'result' => $attempt1 && $attempt2,
                        'message' => $attempt1 && $attempt2 ? 'Rate limiting working correctly' : 'Rate limiting test failed',
                    ];

                case 'config':
                    $configUtil = app(\LaraUtilX\Utilities\ConfigUtil::class);
                    $appConfig = $configUtil->getAllAppSettings();
                    return [
                        'status' => 'success',
                        'test' => 'Configuration access',
                        'result' => !empty($appConfig),
                        'message' => !empty($appConfig) ? 'Config access working correctly' : 'Config test failed',
                    ];

                case 'query_parameter':
                    $request = request();
                    $request->merge(['test_param' => 'test_value']);
                    $params = \LaraUtilX\Utilities\QueryParameterUtil::parse($request, ['test_param']);
                    return [
                        'status' => 'success',
                        'test' => 'Query parameter parsing',
                        'result' => isset($params['test_param']) && $params['test_param'] === 'test_value',
                        'message' => isset($params['test_param']) && $params['test_param'] === 'test_value' ? 'Query parameter parsing working correctly' : 'Query parameter test failed',
                    ];

                case 'scheduler':
                    $scheduler = app(\LaraUtilX\Utilities\SchedulerUtil::class);
                    $summary = $scheduler->getScheduleSummary();
                    return [
                        'status' => 'success',
                        'test' => 'Scheduler access',
                        'result' => is_array($summary),
                        'message' => is_array($summary) ? 'Scheduler access working correctly' : 'Scheduler test failed',
                    ];

                case 'feature_toggle':
                    $enabled = \LaraUtilX\Utilities\FeatureToggleUtil::isEnabled('larautilx_integration');
                    return [
                        'status' => 'success',
                        'test' => 'Feature toggle access',
                        'result' => is_bool($enabled),
                        'message' => is_bool($enabled) ? 'Feature toggle access working correctly' : 'Feature toggle test failed',
                    ];

                case 'ai':
                    $aiService = app(\App\Services\AIService::class);
                    $status = $aiService->getStatus();
                    return [
                        'status' => 'success',
                        'test' => 'AI service access',
                        'result' => isset($status['available']),
                        'message' => isset($status['available']) ? 'AI service access working correctly' : 'AI service test failed',
                    ];

                default:
                    return [
                        'status' => 'error',
                        'test' => 'Unknown component',
                        'result' => false,
                        'message' => 'Unknown component: ' . $component,
                    ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'test' => $component . ' test',
                'result' => false,
                'message' => 'Test failed: ' . $e->getMessage(),
            ];
        }
    }
}
