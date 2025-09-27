<?php

namespace App\Console\Commands;

use App\Services\EnhancedCacheService;
use App\Services\EnhancedSessionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use SmartCache\Facades\SmartCache;

/**
 * Command to demonstrate and test Laravel 12.29.0+ features
 */
class Laravel129FeaturesCommand extends Command
{
    protected $signature = 'laravel:129-features {--test : Run feature tests}';
    protected $description = 'Demonstrate Laravel 12.29.0+ enhanced features';

    public function handle(): int
    {
        $this->info('🚀 Laravel 12.29.0+ Features Demonstration');
        $this->newLine();

        if ($this->option('test')) {
            return $this->runFeatureTests();
        }

        $this->showFeatureOverview();
        return 0;
    }

    protected function showFeatureOverview(): void
    {
        $this->info('📋 Available Features:');
        $this->line('1. Enhanced Debug Page with auto dark/light mode detection');
        $this->line('2. Performance-boosting session drivers (Redis with compression)');
        $this->line('3. Enhanced caching mechanisms (Redis with igbinary + lzf)');
        $this->line('4. Streamlined dependency injection');
        $this->newLine();

        $this->info('🔧 Configuration Status:');
        $this->checkConfiguration();
        $this->newLine();

        $this->info('📊 Performance Metrics:');
        $this->showPerformanceMetrics();
    }

    protected function runFeatureTests(): int
    {
        $this->info('🧪 Running Laravel 12.29.0+ Feature Tests...');
        $this->newLine();

        $tests = [
            'Enhanced Cache Service' => fn() => $this->testEnhancedCache(),
            'Enhanced Session Service' => fn() => $this->testEnhancedSession(),
            'Redis Connection' => fn() => $this->testRedisConnection(),
            'Compression Support' => fn() => $this->testCompressionSupport(),
        ];

        $passed = 0;
        $total = count($tests);

        foreach ($tests as $name => $test) {
            $this->line("Testing {$name}...");

            try {
                $result = $test();
                if ($result) {
                    $this->info("✅ {$name}: PASSED");
                    $passed++;
                } else {
                    $this->error("❌ {$name}: FAILED");
                }
            } catch (\Exception $e) {
                $this->error("❌ {$name}: ERROR - {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Test Results: {$passed}/{$total} passed");

        return $passed === $total ? 0 : 1;
    }

    protected function testEnhancedCache(): bool
    {
        try {
            $cacheService = app(EnhancedCacheService::class);

            // Test basic caching
            $key = 'test-cache-' . time();
            $data = ['test' => 'data', 'timestamp' => time()];

            $cached = $cacheService->remember($key, 60, fn() => $data);

            return $cached === $data;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function testEnhancedSession(): bool
    {
        try {
            $sessionService = app(EnhancedSessionService::class);

            // Test session operations
            $key = 'test-session-' . time();
            $data = ['test' => 'session-data'];

            $sessionService->put($key, $data);
            $retrieved = $sessionService->get($key);

            return $retrieved === $data;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function testRedisConnection(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function testCompressionSupport(): bool
    {
        $hasIgbinary = function_exists('igbinary_serialize');
        $hasLzf = function_exists('lzf_compress');

        // igbinary is available, lzf is optional
        return $hasIgbinary;
    }

    protected function checkConfiguration(): void
    {
        $configs = [
            'Session Driver' => config('session.driver'),
            'Cache Store' => config('cache.default'),
            'Session Lifetime' => config('session.lifetime') . ' minutes',
            'Debug Mode' => config('app.debug') ? 'Enabled' : 'Disabled',
        ];

        foreach ($configs as $key => $value) {
            $status = $this->getConfigStatus($key, $value);
            $this->line("  {$key}: {$value} {$status}");
        }
    }

    protected function getConfigStatus(string $key, string $value): string
    {
        return match ($key) {
            'Session Driver' => $value === 'redis' ? '✅' : '⚠️',
            'Cache Store' => $value === 'redis' ? '✅' : '⚠️',
            'Debug Mode' => $value === 'Enabled' ? '✅' : '⚠️',
            default => '✅',
        };
    }

    protected function showPerformanceMetrics(): void
    {
        try {
            $cacheService = app(EnhancedCacheService::class);
            $sessionService = app(EnhancedSessionService::class);

            $cacheStats = $cacheService->getStats();
            $sessionStats = $sessionService->getStats();

            $this->line('  Cache Statistics:');
            foreach ($cacheStats as $key => $value) {
                $this->line("    {$key}: {$value}");
            }

            $this->line('  Session Statistics:');
            foreach ($sessionStats as $key => $value) {
                $this->line("    {$key}: {$value}");
            }
        } catch (\Exception $e) {
            $this->error('  Unable to retrieve performance metrics: ' . $e->getMessage());
        }
    }
}
