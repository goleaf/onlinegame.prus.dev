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
        $this->info('🏥 Starting health check...');

        $healthStatus = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'external_services' => $this->checkExternalServices(),
            'performance' => $this->checkPerformance(),
        ];

        $this->displayHealthResults($healthStatus);

        $overallHealth = $this->calculateOverallHealth($healthStatus);
        $this->info("Overall Health Score: {$overallHealth}/100");

        return $overallHealth >= 80 ? 0 : 1;
    }

    /**
     * Check database connectivity and performance
     */
    private function checkDatabase(): array
    {
        $this->info('🔍 Checking database...');

        try {
            $startTime = microtime(true);
            $result = DB::select('SELECT 1 as test');
            $responseTime = (microtime(true) - $startTime) * 1000;

            $tables = DB::select('SHOW TABLES');
            $tableCount = count($tables);

            return [
                'status' => 'healthy',
                'response_time' => round($responseTime, 2),
                'table_count' => $tableCount,
                'message' => "Database connected successfully. {$tableCount} tables found."
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check cache system
     */
    private function checkCache(): array
    {
        $this->info('💾 Checking cache system...');

        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value_' . rand(1000, 9999);

            Cache::put($testKey, $testValue, 60);
            $retrievedValue = Cache::get($testKey);
            Cache::forget($testKey);

            if ($retrievedValue === $testValue) {
                return [
                    'status' => 'healthy',
                    'message' => 'Cache system working correctly'
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Cache retrieval failed'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Cache system error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check storage system
     */
    private function checkStorage(): array
    {
        $this->info('💿 Checking storage system...');

        try {
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'Health check test content';

            Storage::disk('public')->put($testFile, $testContent);
            $retrievedContent = Storage::disk('public')->get($testFile);
            Storage::disk('public')->delete($testFile);

            if ($retrievedContent === $testContent) {
                $freeSpace = disk_free_space(storage_path());
                $totalSpace = disk_total_space(storage_path());
                $usagePercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 2);

                return [
                    'status' => 'healthy',
                    'usage_percent' => $usagePercent,
                    'free_space_gb' => round($freeSpace / (1024 * 1024 * 1024), 2),
                    'message' => "Storage system working correctly. {$usagePercent}% used."
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Storage retrieval failed'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Storage system error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check external services
     */
    private function checkExternalServices(): array
    {
        $this->info('🌐 Checking external services...');

        $services = [
            'Google Fonts' => 'https://fonts.bunny.net',
            'Bootstrap CDN' => 'https://cdn.jsdelivr.net',
            'Font Awesome CDN' => 'https://cdnjs.cloudflare.com',
        ];

        $results = [];
        foreach ($services as $name => $url) {
            try {
                $startTime = microtime(true);
                $response = Http::timeout(5)->head($url);
                $responseTime = (microtime(true) - $startTime) * 1000;

                $results[] = [
                    'service' => $name,
                    'status' => $response->successful() ? 'healthy' : 'unhealthy',
                    'response_time' => round($responseTime, 2),
                    'status_code' => $response->status(),
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'service' => $name,
                    'status' => 'unhealthy',
                    'message' => $e->getMessage(),
                ];
            }
        }

        $healthyCount = collect($results)->where('status', 'healthy')->count();
        $totalCount = count($results);

        return [
            'status' => $healthyCount === $totalCount ? 'healthy' : 'degraded',
            'services' => $results,
            'message' => "{$healthyCount}/{$totalCount} external services healthy"
        ];
    }

    /**
     * Check performance metrics
     */
    private function checkPerformance(): array
    {
        $this->info('⚡ Checking performance metrics...');

        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        $memoryUsagePercent = ($memoryUsage / $this->parseMemoryLimit($memoryLimit)) * 100;

        return [
            'status' => $memoryUsagePercent < 80 ? 'healthy' : 'warning',
            'memory_usage_mb' => round($memoryUsage / (1024 * 1024), 2),
            'memory_peak_mb' => round($memoryPeak / (1024 * 1024), 2),
            'memory_usage_percent' => round($memoryUsagePercent, 2),
            'message' => "Memory usage: {$memoryUsagePercent}% of limit"
        ];
    }

    /**
     * Display health check results
     */
    private function displayHealthResults(array $healthStatus): void
    {
        $this->newLine();
        $this->info('📊 Health Check Results:');
        $this->newLine();

        foreach ($healthStatus as $component => $status) {
            $icon = $status['status'] === 'healthy' ? '✅' : 
                   ($status['status'] === 'degraded' ? '⚠️' : '❌');
            
            $this->line("{$icon} " . ucfirst($component) . ": {$status['message']}");
            
            if ($this->option('detailed') && isset($status['services'])) {
                foreach ($status['services'] as $service) {
                    $serviceIcon = $service['status'] === 'healthy' ? '  ✅' : '  ❌';
                    $this->line("{$serviceIcon} {$service['service']}");
                }
            }
        }
    }

    /**
     * Calculate overall health score
     */
    private function calculateOverallHealth(array $healthStatus): int
    {
        $scores = [];
        
        foreach ($healthStatus as $component => $status) {
            switch ($status['status']) {
                case 'healthy':
                    $scores[] = 100;
                    break;
                case 'degraded':
                    $scores[] = 70;
                    break;
                case 'warning':
                    $scores[] = 60;
                    break;
                default:
                    $scores[] = 0;
            }
        }

        return round(array_sum($scores) / count($scores));
    }

    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }
}