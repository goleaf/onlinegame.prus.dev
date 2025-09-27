<?php

namespace App\Http\Controllers\Game;

use App\Http\Controllers\Controller;
use App\Services\LarautilxIntegrationService;
use Illuminate\Http\Request;
use LaraUtilX\Traits\ApiResponseTrait;
use LaraUtilX\Utilities\ConfigUtil;
use LaraUtilX\Utilities\LoggingUtil;
use LaraUtilX\Utilities\QueryParameterUtil;
use LaraUtilX\Utilities\SchedulerUtil;

class SystemController extends Controller
{
    use ApiResponseTrait;

    protected ConfigUtil $configUtil;
    protected SchedulerUtil $schedulerUtil;
    protected LarautilxIntegrationService $integrationService;

    public function __construct(
        ConfigUtil $configUtil,
        SchedulerUtil $schedulerUtil,
        LarautilxIntegrationService $integrationService
    ) {
        $this->configUtil = $configUtil;
        $this->schedulerUtil = $schedulerUtil;
        $this->integrationService = $integrationService;
    }

    /**
     * Get system configuration
     */
    public function config(Request $request)
    {
        try {
            $allowedParams = ['section', 'key', 'include_app'];
            $queryParams = QueryParameterUtil::parse($request, $allowedParams);

            $config = [];

            if ($request->has('include_app') && $request->get('include_app') === 'true') {
                $config['app'] = $this->configUtil->getAllAppSettings();
            }

            if ($request->has('section')) {
                $section = $request->get('section');
                $config[$section] = $this->configUtil->getAllSettings($section);
            }

            if ($request->has('key')) {
                $key = $request->get('key');
                $config['specific_key'] = $this->configUtil->getSetting($key);
            }

            // Add game-specific configuration
            $config['game'] = [
                'worlds' => \App\Models\Game\World::active()->count(),
                'players' => \App\Models\Game\Player::active()->count(),
                'villages' => \App\Models\Game\Village::count(),
                'alliances' => \App\Models\Game\Alliance::active()->count(),
                'features' => [
                    'advanced_map' => config('feature-toggles.advanced_map', false),
                    'real_time_updates' => config('feature-toggles.real_time_updates', false),
                    'geographic_features' => config('feature-toggles.geographic_features', false),
                    'larautilx_integration' => config('feature-toggles.larautilx_integration', false),
                ],
            ];

            LoggingUtil::info('System configuration retrieved', [
                'user_id' => auth()->id(),
                'requested_sections' => $queryParams,
            ], 'system_management');

            return $this->successResponse($config, 'System configuration retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving system configuration', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 'system_management');

            return $this->errorResponse('Failed to retrieve system configuration.', 500);
        }
    }

    /**
     * Update system configuration
     */
    public function updateConfig(Request $request)
    {
        try {
            $validated = $request->validate([
                'key' => 'required|string',
                'value' => 'required',
                'section' => 'nullable|string',
            ]);

            $this->configUtil->setSetting($validated['key'], $validated['value']);

            LoggingUtil::info('System configuration updated', [
                'user_id' => auth()->id(),
                'key' => $validated['key'],
                'section' => $validated['section'] ?? 'general',
            ], 'system_management');

            return $this->successResponse([
                'key' => $validated['key'],
                'value' => $validated['value'],
                'updated_at' => now()->toDateTimeString(),
            ], 'System configuration updated successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error updating system configuration', [
                'error' => $e->getMessage(),
                'key' => $request->get('key'),
            ], 'system_management');

            return $this->errorResponse('Failed to update system configuration.', 500);
        }
    }

    /**
     * Get scheduled tasks information
     */
    public function scheduledTasks()
    {
        try {
            $scheduleSummary = $this->schedulerUtil->getScheduleSummary();
            $hasOverdueTasks = $this->schedulerUtil->hasOverdueTasks();

            $response = [
                'tasks' => $scheduleSummary,
                'summary' => [
                    'total_tasks' => count($scheduleSummary),
                    'overdue_tasks' => $hasOverdueTasks,
                    'running_tasks' => collect($scheduleSummary)->where('is_running', true)->count(),
                    'due_tasks' => collect($scheduleSummary)->where('is_due', true)->count(),
                ],
            ];

            LoggingUtil::info('Scheduled tasks information retrieved', [
                'user_id' => auth()->id(),
                'total_tasks' => count($scheduleSummary),
                'overdue_tasks' => $hasOverdueTasks,
            ], 'system_management');

            return $this->successResponse($response, 'Scheduled tasks information retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving scheduled tasks', [
                'error' => $e->getMessage(),
            ], 'system_management');

            return $this->errorResponse('Failed to retrieve scheduled tasks information.', 500);
        }
    }

    /**
     * Get system health status
     */
    public function health()
    {
        try {
            $health = [
                'status' => 'healthy',
                'checks' => [],
                'timestamp' => now()->toDateTimeString(),
            ];

            // Database connectivity check
            try {
                \DB::connection()->getPdo();
                $health['checks']['database'] = [
                    'status' => 'healthy',
                    'message' => 'Database connection successful',
                ];
            } catch (\Exception $e) {
                $health['checks']['database'] = [
                    'status' => 'unhealthy',
                    'message' => 'Database connection failed: ' . $e->getMessage(),
                ];
                $health['status'] = 'degraded';
            }

            // Cache system check
            try {
                \Cache::put('health_check', 'ok', 60);
                $cacheValue = \Cache::get('health_check');
                $health['checks']['cache'] = [
                    'status' => $cacheValue === 'ok' ? 'healthy' : 'unhealthy',
                    'message' => $cacheValue === 'ok' ? 'Cache system working' : 'Cache system not responding',
                ];
                if ($cacheValue !== 'ok') {
                    $health['status'] = 'degraded';
                }
            } catch (\Exception $e) {
                $health['checks']['cache'] = [
                    'status' => 'unhealthy',
                    'message' => 'Cache system error: ' . $e->getMessage(),
                ];
                $health['status'] = 'degraded';
            }

            // Storage check
            try {
                \Storage::put('health_check.txt', 'ok');
                $storageValue = \Storage::get('health_check.txt');
                \Storage::delete('health_check.txt');
                $health['checks']['storage'] = [
                    'status' => $storageValue === 'ok' ? 'healthy' : 'unhealthy',
                    'message' => $storageValue === 'ok' ? 'Storage system working' : 'Storage system not responding',
                ];
                if ($storageValue !== 'ok') {
                    $health['status'] = 'degraded';
                }
            } catch (\Exception $e) {
                $health['checks']['storage'] = [
                    'status' => 'unhealthy',
                    'message' => 'Storage system error: ' . $e->getMessage(),
                ];
                $health['status'] = 'degraded';
            }

            // Game-specific health checks
            try {
                $gameStats = [
                    'active_worlds' => \App\Models\Game\World::active()->count(),
                    'active_players' => \App\Models\Game\Player::active()->count(),
                    'total_villages' => \App\Models\Game\Village::count(),
                    'online_players' => \App\Models\Game\Player::where('is_online', true)
                        ->where('last_active_at', '>=', now()->subMinutes(15))
                        ->count(),
                ];

                $health['checks']['game_system'] = [
                    'status' => 'healthy',
                    'message' => 'Game system operational',
                    'stats' => $gameStats,
                ];
            } catch (\Exception $e) {
                $health['checks']['game_system'] = [
                    'status' => 'unhealthy',
                    'message' => 'Game system error: ' . $e->getMessage(),
                ];
                $health['status'] = 'degraded';
            }

            // Larautilx integration health check
            try {
                $larautilxStatus = $this->integrationService->getIntegrationStatus();
                $health['checks']['larautilx'] = [
                    'status' => 'healthy',
                    'message' => 'Larautilx integration working',
                    'details' => $larautilxStatus,
                ];
            } catch (\Exception $e) {
                $health['checks']['larautilx'] = [
                    'status' => 'unhealthy',
                    'message' => 'Larautilx integration error: ' . $e->getMessage(),
                ];
                $health['status'] = 'degraded';
            }

            LoggingUtil::info('System health check completed', [
                'user_id' => auth()->id(),
                'overall_status' => $health['status'],
                'checks_count' => count($health['checks']),
            ], 'system_management');

            return $this->successResponse($health, 'System health status retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error performing system health check', [
                'error' => $e->getMessage(),
            ], 'system_management');

            return $this->errorResponse('Failed to perform system health check.', 500);
        }
    }

    /**
     * Get system performance metrics
     */
    public function getSystemMetrics()
    {
        try {
            $metrics = [
                'timestamp' => now()->toDateTimeString(),
                'performance' => [
                    'memory_usage' => memory_get_usage(true),
                    'memory_peak' => memory_get_peak_usage(true),
                    'memory_limit' => ini_get('memory_limit'),
                ],
                'database' => [
                    'connections' => \DB::getConnections(),
                    'query_count' => \DB::getQueryLog() ? count(\DB::getQueryLog()) : 0,
                ],
                'cache' => [
                    'driver' => config('cache.default'),
                    'stores' => array_keys(config('cache.stores')),
                ],
                'game_metrics' => [
                    'active_sessions' => \App\Models\Game\Player::where('is_online', true)
                        ->where('last_active_at', '>=', now()->subMinutes(15))
                        ->count(),
                    'total_requests_today' => \App\Models\Game\Player::whereDate('last_active_at', today())->count(),
                    'new_registrations_today' => \App\Models\User::whereDate('created_at', today())->count(),
                ],
            ];

            // Add Larautilx-specific metrics
            try {
                $metrics['larautilx'] = [
                    'caching_util_active' => class_exists(\LaraUtilX\Utilities\CachingUtil::class),
                    'filtering_util_active' => class_exists(\LaraUtilX\Utilities\FilteringUtil::class),
                    'pagination_util_active' => class_exists(\LaraUtilX\Utilities\PaginationUtil::class),
                    'logging_util_active' => class_exists(\LaraUtilX\Utilities\LoggingUtil::class),
                    'rate_limiter_util_active' => class_exists(\LaraUtilX\Utilities\RateLimiterUtil::class),
                ];
            } catch (\Exception $e) {
                $metrics['larautilx'] = [
                    'error' => 'Failed to get Larautilx metrics: ' . $e->getMessage(),
                ];
            }

            LoggingUtil::info('System metrics retrieved', [
                'user_id' => auth()->id(),
                'memory_usage' => $metrics['performance']['memory_usage'],
                'active_sessions' => $metrics['game_metrics']['active_sessions'],
            ], 'system_management');

            return $this->successResponse($metrics, 'System metrics retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving system metrics', [
                'error' => $e->getMessage(),
            ], 'system_management');

            return $this->errorResponse('Failed to retrieve system metrics.', 500);
        }
    }

    /**
     * Clear system caches
     */
    public function clearSystemCaches(Request $request)
    {
        try {
            $validated = $request->validate([
                'cache_types' => 'array',
                'cache_types.*' => 'in:config,route,view,application,larautilx',
            ]);

            $cacheTypes = $validated['cache_types'] ?? ['config', 'route', 'view', 'application'];
            $cleared = [];

            foreach ($cacheTypes as $type) {
                switch ($type) {
                    case 'config':
                        \Artisan::call('config:clear');
                        $cleared[] = 'Configuration cache';
                        break;
                    case 'route':
                        \Artisan::call('route:clear');
                        $cleared[] = 'Route cache';
                        break;
                    case 'view':
                        \Artisan::call('view:clear');
                        $cleared[] = 'View cache';
                        break;
                    case 'application':
                        \Artisan::call('cache:clear');
                        $cleared[] = 'Application cache';
                        break;
                    case 'larautilx':
                        // Clear Larautilx-specific caches
                        $this->integrationService->clearAllCaches();
                        $cleared[] = 'Larautilx caches';
                        break;
                }
            }

            LoggingUtil::info('System caches cleared', [
                'user_id' => auth()->id(),
                'cache_types' => $cacheTypes,
                'cleared_caches' => $cleared,
            ], 'system_management');

            return $this->successResponse([
                'cleared_caches' => $cleared,
                'timestamp' => now()->toDateTimeString(),
            ], 'System caches cleared successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error clearing system caches', [
                'error' => $e->getMessage(),
                'cache_types' => $request->get('cache_types', []),
            ], 'system_management');

            return $this->errorResponse('Failed to clear system caches.', 500);
        }
    }

    /**
     * Get system logs
     */
    public function getSystemLogs(Request $request)
    {
        try {
            $allowedParams = ['level', 'limit', 'since'];
            $queryParams = QueryParameterUtil::parse($request, $allowedParams);

            $level = $request->get('level', 'info');
            $limit = $request->get('limit', 100);
            $since = $request->get('since', now()->subHours(24)->toDateTimeString());

            // This is a simplified log retrieval - in production you'd want more sophisticated log parsing
            $logFile = storage_path('logs/laravel.log');
            $logs = [];

            if (file_exists($logFile)) {
                $logContent = file_get_contents($logFile);
                $logLines = explode("\n", $logContent);

                // Filter and limit logs
                $filteredLogs = array_filter($logLines, function ($line) use ($level, $since) {
                    if (empty($line))
                        return false;

                    // Simple level filtering
                    if ($level !== 'all' && !str_contains(strtolower($line), strtolower($level))) {
                        return false;
                    }

                    // Simple time filtering (basic implementation)
                    if (str_contains($line, $since)) {
                        return true;
                    }

                    return true;  // Include all logs for now
                });

                $logs = array_slice($filteredLogs, -$limit);
            }

            LoggingUtil::info('System logs retrieved', [
                'user_id' => auth()->id(),
                'level' => $level,
                'limit' => $limit,
                'logs_count' => count($logs),
            ], 'system_management');

            return $this->successResponse([
                'logs' => $logs,
                'metadata' => [
                    'level' => $level,
                    'limit' => $limit,
                    'since' => $since,
                    'total_retrieved' => count($logs),
                ],
            ], 'System logs retrieved successfully.');
        } catch (\Exception $e) {
            LoggingUtil::error('Error retrieving system logs', [
                'error' => $e->getMessage(),
                'level' => $request->get('level'),
            ], 'system_management');

            return $this->errorResponse('Failed to retrieve system logs.', 500);
        }
    }
}
