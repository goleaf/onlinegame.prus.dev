<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class HealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:check {--detailed : Show detailed health information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform comprehensive health check of the application';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🏥 Starting application health check...');

        $healthStatus = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'external_services' => $this->checkExternalServices(),
            'performance' => $this->checkPerformance(),
            'security' => $this->checkSecurity(),
        ];

        $this->displayHealthReport($healthStatus);

        $overallStatus = $this->getOverallStatus($healthStatus);
        
        if ($overallStatus === 'healthy') {
            $this->info('✅ Application is healthy!');
            return 0;
        } else {
            $this->error('❌ Application has health issues that need attention.');
            return 1;
        }
    }

    /**
     * Check database connectivity and performance
     */
    private function checkDatabase(): array
    {
        $this->line('🔍 Checking database...');

        try {
            $startTime = microtime(true);
            DB::connection()->getPdo();
            $connectionTime = (microtime(true) - $startTime) * 1000;

            $tables = DB::select('SHOW TABLES');
            $tableCount = count($tables);

            $status = $connectionTime < 1000 ? 'healthy' : 'warning';
            
            return [
                'status' => $status,
                'connection_time' => round($connectionTime, 2) . 'ms',
                'table_count' => $tableCount,
                'message' => $status === 'healthy' ? 'Database connection healthy' : 'Database connection slow',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache system
     */
    private function checkCache(): array
    {
        $this->line('🔍 Checking cache system...');

        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';

            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            if ($retrieved === $testValue) {
                return [
                    'status' => 'healthy',
                    'message' => 'Cache system working correctly',
                ];
            } else {
                return [
                    'status' => 'warning',
                    'message' => 'Cache system has issues with data integrity',
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Cache system failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage system
     */
    private function checkStorage(): array
    {
        $this->line('🔍 Checking storage system...');

        try {
            $disk = Storage::disk('public');
            
            if (!$disk->exists('.')) {
                return [
                    'status' => 'error',
                    'message' => 'Storage disk not accessible',
                ];
            }

            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'health check content';

            $disk->put($testFile, $testContent);
            $retrieved = $disk->get($testFile);
            $disk->delete($testFile);

            if ($retrieved === $testContent) {
                return [
                    'status' => 'healthy',
                    'message' => 'Storage system working correctly',
                ];
            } else {
                return [
                    'status' => 'warning',
                    'message' => 'Storage system has issues with data integrity',
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Storage system failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check external services
     */
    private function checkExternalServices(): array
    {
        $this->line('🔍 Checking external services...');

        $services = [
            'cdn_jsdelivr' => 'https://cdn.jsdelivr.net',
            'cdn_cloudflare' => 'https://cdnjs.cloudflare.com',
            'fonts_bunny' => 'https://fonts.bunny.net',
            'stripe' => 'https://js.stripe.com',
        ];

        $results = [];
        $healthyCount = 0;

        foreach ($services as $name => $url) {
            try {
                $startTime = microtime(true);
                $response = Http::timeout(5)->head($url);
                $responseTime = (microtime(true) - $startTime) * 1000;

                $status = $response->successful() && $responseTime < 2000 ? 'healthy' : 'warning';
                if ($status === 'healthy') $healthyCount++;

                $results[$name] = [
                    'status' => $status,
                    'response_time' => round($responseTime, 2) . 'ms',
                    'http_status' => $response->status(),
                ];
            } catch (\Exception $e) {
                $results[$name] = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        $overallStatus = $healthyCount >= count($services) * 0.75 ? 'healthy' : 'warning';
        
        return [
            'status' => $overallStatus,
            'services' => $results,
            'message' => "{$healthyCount}/" . count($services) . " external services healthy",
        ];
    }

    /**
     * Check performance metrics
     */
    private function checkPerformance(): array
    {
        $this->line('🔍 Checking performance...');

        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = ini_get('memory_limit');

        $memoryUsageMB = round($memoryUsage / 1024 / 1024, 2);
        $memoryPeakMB = round($memoryPeak / 1024 / 1024, 2);

        $status = $memoryUsageMB < 128 ? 'healthy' : ($memoryUsageMB < 256 ? 'warning' : 'error');

        return [
            'status' => $status,
            'memory_usage' => $memoryUsageMB . 'MB',
            'memory_peak' => $memoryPeakMB . 'MB',
            'memory_limit' => $memoryLimit,
            'message' => $status === 'healthy' ? 'Memory usage is optimal' : 'High memory usage detected',
        ];
    }

    /**
     * Check security settings
     */
    private function checkSecurity(): array
    {
        $this->line('🔍 Checking security...');

        $checks = [
            'app_debug' => config('app.debug') === false,
            'app_env' => config('app.env') === 'production',
            'session_secure' => config('session.secure') === true,
            'session_httponly' => config('session.http_only') === true,
        ];

        $passedChecks = array_sum($checks);
        $totalChecks = count($checks);

        $status = $passedChecks === $totalChecks ? 'healthy' : 'warning';

        return [
            'status' => $status,
            'checks' => $checks,
            'message' => "{$passedChecks}/{$totalChecks} security checks passed",
        ];
    }

    /**
     * Display health report
     */
    private function displayHealthReport(array $healthStatus): void
    {
        $this->newLine();
        $this->info('📊 Health Check Report:');
        $this->newLine();

        foreach ($healthStatus as $component => $status) {
            $icon = $this->getStatusIcon($status['status']);
            $this->line("{$icon} {$component}: {$status['message']}");

            if ($this->option('detailed') && isset($status['services'])) {
                foreach ($status['services'] as $service => $serviceStatus) {
                    $serviceIcon = $this->getStatusIcon($serviceStatus['status']);
                    $this->line("  {$serviceIcon} {$service}: " . ($serviceStatus['response_time'] ?? $serviceStatus['message'] ?? 'OK'));
                }
            }
        }
    }

    /**
     * Get status icon
     */
    private function getStatusIcon(string $status): string
    {
        return match($status) {
            'healthy' => '✅',
            'warning' => '⚠️',
            'error' => '❌',
            default => '❓',
        };
    }

    /**
     * Get overall status
     */
    private function getOverallStatus(array $healthStatus): string
    {
        $statuses = array_column($healthStatus, 'status');
        
        if (in_array('error', $statuses)) {
            return 'error';
        }
        
        if (in_array('warning', $statuses)) {
            return 'warning';
        }
        
        return 'healthy';
    }
}
