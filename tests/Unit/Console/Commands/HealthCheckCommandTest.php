<?php

namespace Tests\Unit\Console\Commands;

use App\Console\Commands\HealthCheckCommand;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HealthCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    private HealthCheckCommand $command;

    protected function setUp(): void
    {
        parent::setUp();
        $this->command = new HealthCheckCommand();
        $this->bindCacheRepository(new Repository(new ArrayStore()));
    }

    protected function tearDown(): void
    {
        app()->forgetInstance('healthcheck.cache');

        parent::tearDown();
    }

    private function bindCacheRepository(Repository $repository): void
    {
        app()->instance('healthcheck.cache', $repository);
    }

    private function cacheRepositoryThatThrowsOnPut(string $message): Repository
    {
        return new Repository(new class ($message) extends ArrayStore {
            public function __construct(private string $message)
            {
                parent::__construct();
            }

            public function put($key, $value, $seconds): void
            {
                throw new \Exception($this->message);
            }
        });
    }

    private function cacheRepositoryWithMismatchedGet(): Repository
    {
        return new Repository(new class () extends ArrayStore {
            public function get($key)
            {
                return 'different_value';
            }
        });
    }

    /**
     * @test
     */
    public function it_can_perform_health_check()
    {
        // Mock database
        DB::shouldReceive('select')
            ->with('SELECT 1 as test')
            ->andReturn([(object) ['test' => 1]]);

        DB::shouldReceive('select')
            ->with('SHOW TABLES')
            ->andReturn([
                (object) ['Tables_in_test' => 'users'],
                (object) ['Tables_in_test' => 'villages'],
            ]);

        // Mock storage
        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('put')->andReturn(true);
        Storage::shouldReceive('get')->andReturn('Health check test content');
        Storage::shouldReceive('delete')->andReturn(true);

        // Mock HTTP
        Http::shouldReceive('timeout')->andReturnSelf();
        Http::shouldReceive('head')->andReturn(
            new class () {
                public function successful()
                {
                    return true;
                }

                public function status()
                {
                    return 200;
                }
            }
        );

        $this
            ->artisan('health:check')
            ->expectsOutput('🏥 Starting health check...')
            ->expectsOutput('🔍 Checking database...')
            ->expectsOutput('💾 Checking cache system...')
            ->expectsOutput('💿 Checking storage system...')
            ->expectsOutput('🌐 Checking external services...')
            ->expectsOutput('⚡ Checking performance metrics...')
            ->expectsOutput('📊 Health Check Results:')
            ->expectsOutput('Overall Health Score: 100/100')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_perform_detailed_health_check()
    {
        // Mock database
        DB::shouldReceive('select')
            ->with('SELECT 1 as test')
            ->andReturn([(object) ['test' => 1]]);

        DB::shouldReceive('select')
            ->with('SHOW TABLES')
            ->andReturn([
                (object) ['Tables_in_test' => 'users'],
                (object) ['Tables_in_test' => 'villages'],
            ]);

        // Mock storage
        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('put')->andReturn(true);
        Storage::shouldReceive('get')->andReturn('Health check test content');
        Storage::shouldReceive('delete')->andReturn(true);

        // Mock HTTP
        Http::shouldReceive('timeout')->andReturnSelf();
        Http::shouldReceive('head')->andReturn(
            new class () {
                public function successful()
                {
                    return true;
                }

                public function status()
                {
                    return 200;
                }
            }
        );

        $this
            ->artisan('health:check', ['--detailed' => true])
            ->expectsOutput('🏥 Starting health check...')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_database_connection_failure()
    {
        DB::shouldReceive('select')
            ->andThrow(new \Exception('Connection failed'));

        $this
            ->artisan('health:check')
            ->expectsOutput('🏥 Starting health check...')
            ->expectsOutput('🔍 Checking database...')
            ->expectsOutput('❌ Database: Database connection failed: Connection failed')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_cache_failure()
    {
        // Mock database
        DB::shouldReceive('select')
            ->with('SELECT 1 as test')
            ->andReturn([(object) ['test' => 1]]);

        DB::shouldReceive('select')
            ->with('SHOW TABLES')
            ->andReturn([
                (object) ['Tables_in_test' => 'users'],
            ]);

        $this->bindCacheRepository($this->cacheRepositoryThatThrowsOnPut('Cache error'));

        $this
            ->artisan('health:check')
            ->expectsOutput('🏥 Starting health check...')
            ->expectsOutput('🔍 Checking database...')
            ->expectsOutput('💾 Checking cache system...')
            ->expectsOutput('❌ Cache: Cache system error: Cache error')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_cache_retrieval_failure()
    {
        // Mock database
        DB::shouldReceive('select')
            ->with('SELECT 1 as test')
            ->andReturn([(object) ['test' => 1]]);

        DB::shouldReceive('select')
            ->with('SHOW TABLES')
            ->andReturn([
                (object) ['Tables_in_test' => 'users'],
            ]);

        $this->bindCacheRepository($this->cacheRepositoryWithMismatchedGet());

        $this
            ->artisan('health:check')
            ->expectsOutput('🏥 Starting health check...')
            ->expectsOutput('🔍 Checking database...')
            ->expectsOutput('💾 Checking cache system...')
            ->expectsOutput('❌ Cache: Cache retrieval failed')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_storage_failure()
    {
        // Mock database
        DB::shouldReceive('select')
            ->with('SELECT 1 as test')
            ->andReturn([(object) ['test' => 1]]);

        DB::shouldReceive('select')
            ->with('SHOW TABLES')
            ->andReturn([
                (object) ['Tables_in_test' => 'users'],
            ]);

        // Mock cache

        // Mock storage failure
        Storage::shouldReceive('disk')->andThrow(new \Exception('Storage error'));

        $this
            ->artisan('health:check')
            ->expectsOutput('🏥 Starting health check...')
            ->expectsOutput('🔍 Checking database...')
            ->expectsOutput('💾 Checking cache system...')
            ->expectsOutput('💿 Checking storage system...')
            ->expectsOutput('❌ Storage: Storage system error: Storage error')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_storage_retrieval_failure()
    {
        // Mock database
        DB::shouldReceive('select')
            ->with('SELECT 1 as test')
            ->andReturn([(object) ['test' => 1]]);

        DB::shouldReceive('select')
            ->with('SHOW TABLES')
            ->andReturn([
                (object) ['Tables_in_test' => 'users'],
            ]);

        // Mock cache

        // Mock storage
        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('put')->andReturn(true);
        Storage::shouldReceive('get')->andReturn('different content');
        Storage::shouldReceive('delete')->andReturn(true);

        $this
            ->artisan('health:check')
            ->expectsOutput('🏥 Starting health check...')
            ->expectsOutput('🔍 Checking database...')
            ->expectsOutput('💾 Checking cache system...')
            ->expectsOutput('💿 Checking storage system...')
            ->expectsOutput('❌ Storage: Storage retrieval failed')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_external_service_failure()
    {
        // Mock database
        DB::shouldReceive('select')
            ->with('SELECT 1 as test')
            ->andReturn([(object) ['test' => 1]]);

        DB::shouldReceive('select')
            ->with('SHOW TABLES')
            ->andReturn([
                (object) ['Tables_in_test' => 'users'],
            ]);

        // Mock cache

        // Mock storage
        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('put')->andReturn(true);
        Storage::shouldReceive('get')->andReturn('Health check test content');
        Storage::shouldReceive('delete')->andReturn(true);

        // Mock HTTP failure
        Http::shouldReceive('timeout')->andThrow(new \Exception('Network error'));

        $this
            ->artisan('health:check')
            ->expectsOutput('🏥 Starting health check...')
            ->expectsOutput('🔍 Checking database...')
            ->expectsOutput('💾 Checking cache system...')
            ->expectsOutput('💿 Checking storage system...')
            ->expectsOutput('🌐 Checking external services...')
            ->expectsOutput('⚠️ External_services: 0/3 external services healthy')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_handles_partial_external_service_failure()
    {
        // Mock database
        DB::shouldReceive('select')
            ->with('SELECT 1 as test')
            ->andReturn([(object) ['test' => 1]]);

        DB::shouldReceive('select')
            ->with('SHOW TABLES')
            ->andReturn([
                (object) ['Tables_in_test' => 'users'],
            ]);

        // Mock cache

        // Mock storage
        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('put')->andReturn(true);
        Storage::shouldReceive('get')->andReturn('Health check test content');
        Storage::shouldReceive('delete')->andReturn(true);

        // Mock HTTP - first success, second failure
        Http::shouldReceive('timeout')
            ->once()
            ->andReturnSelf();
        Http::shouldReceive('head')
            ->once()
            ->andReturn(
                new class () {
                    public function successful()
                    {
                        return true;
                    }

                    public function status()
                    {
                        return 200;
                    }
                }
            );

        Http::shouldReceive('timeout')
            ->once()
            ->andThrow(new \Exception('Network error'));

        $this
            ->artisan('health:check')
            ->expectsOutput('🏥 Starting health check...')
            ->expectsOutput('🔍 Checking database...')
            ->expectsOutput('💾 Checking cache system...')
            ->expectsOutput('💿 Checking storage system...')
            ->expectsOutput('🌐 Checking external services...')
            ->expectsOutput('⚠️ External_services: 1/3 external services healthy')
            ->assertExitCode(1);
    }

    /**
     * @test
     */
    public function it_calculates_overall_health_score_correctly()
    {
        // Mock all services as healthy
        DB::shouldReceive('select')
            ->with('SELECT 1 as test')
            ->andReturn([(object) ['test' => 1]]);

        DB::shouldReceive('select')
            ->with('SHOW TABLES')
            ->andReturn([
                (object) ['Tables_in_test' => 'users'],
            ]);


        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('put')->andReturn(true);
        Storage::shouldReceive('get')->andReturn('Health check test content');
        Storage::shouldReceive('delete')->andReturn(true);

        Http::shouldReceive('timeout')->andReturnSelf();
        Http::shouldReceive('head')->andReturn(
            new class () {
                public function successful()
                {
                    return true;
                }

                public function status()
                {
                    return 200;
                }
            }
        );

        $this
            ->artisan('health:check')
            ->expectsOutput('Overall Health Score: 100/100')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_memory_limit_parsing()
    {
        // Mock all services as healthy
        DB::shouldReceive('select')
            ->with('SELECT 1 as test')
            ->andReturn([(object) ['test' => 1]]);

        DB::shouldReceive('select')
            ->with('SHOW TABLES')
            ->andReturn([
                (object) ['Tables_in_test' => 'users'],
            ]);


        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('put')->andReturn(true);
        Storage::shouldReceive('get')->andReturn('Health check test content');
        Storage::shouldReceive('delete')->andReturn(true);

        Http::shouldReceive('timeout')->andReturnSelf();
        Http::shouldReceive('head')->andReturn(
            new class () {
                public function successful()
                {
                    return true;
                }

                public function status()
                {
                    return 200;
                }
            }
        );

        $previousLimit = ini_get('memory_limit');

        if (@ini_set('memory_limit', '128M') === false) {
            $this->markTestSkipped('Unable to adjust memory limit for the current environment.');
        }

        $this
            ->artisan('health:check')
            ->expectsOutput('⚡ Checking performance metrics...')
            ->assertExitCode(0);

        if ($previousLimit !== false) {
            @ini_set('memory_limit', $previousLimit);
        }
    }

    /**
     * @test
     */
    public function it_handles_high_memory_usage_warning()
    {
        // Mock all services as healthy
        DB::shouldReceive('select')
            ->with('SELECT 1 as test')
            ->andReturn([(object) ['test' => 1]]);

        DB::shouldReceive('select')
            ->with('SHOW TABLES')
            ->andReturn([
                (object) ['Tables_in_test' => 'users'],
            ]);


        Storage::shouldReceive('disk')->andReturnSelf();
        Storage::shouldReceive('put')->andReturn(true);
        Storage::shouldReceive('get')->andReturn('Health check test content');
        Storage::shouldReceive('delete')->andReturn(true);

        Http::shouldReceive('timeout')->andReturnSelf();
        Http::shouldReceive('head')->andReturn(
            new class () {
                public function successful()
                {
                    return true;
                }

                public function status()
                {
                    return 200;
                }
            }
        );

        $previousLimit = ini_get('memory_limit');

        if (@ini_set('memory_limit', '1M') === false) {
            $this->markTestSkipped('Unable to adjust memory limit for the current environment.');
        }

        $this
            ->artisan('health:check')
            ->expectsOutput('⚡ Checking performance metrics...')
            ->assertExitCode(0);

        if ($previousLimit !== false) {
            @ini_set('memory_limit', $previousLimit);
        }
    }
}
