<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\QueryPerformanceMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class QueryPerformanceMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private QueryPerformanceMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new QueryPerformanceMiddleware();
    }

    /**
     * @test
     */
    public function it_monitors_query_performance_in_non_production()
    {
        // Mock non-production environment
        app()->shouldReceive('isProduction')->andReturn(false);

        DB::shouldReceive('getQueryLog')->andReturn([]);
        DB::shouldReceive('enableQueryLog')->once();

        $request = Request::create('/test');
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * @test
     */
    public function it_skips_monitoring_in_production()
    {
        // Mock production environment
        app()->shouldReceive('isProduction')->andReturn(true);

        $request = Request::create('/test');
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * @test
     */
    public function it_adds_performance_headers()
    {
        app()->shouldReceive('isProduction')->andReturn(false);

        DB::shouldReceive('getQueryLog')
            ->twice()
            ->andReturn([]);

        DB::shouldReceive('enableQueryLog')->once();

        $request = Request::create('/test');
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertNotNull($response->headers->get('X-Query-Count'));
        $this->assertNotNull($response->headers->get('X-Execution-Time'));
    }

    /**
     * @test
     */
    public function it_logs_slow_requests()
    {
        app()->shouldReceive('isProduction')->andReturn(false);

        DB::shouldReceive('getQueryLog')
            ->twice()
            ->andReturn([]);

        DB::shouldReceive('enableQueryLog')->once();

        Config::shouldReceive('get')
            ->with('mysql-performance.slow_query_log.long_query_time', 1.0)
            ->andReturn(1.0);

        Log::shouldReceive('warning')->once();

        $request = Request::create('/test');
        $next = function ($req) {
            usleep(1100000);  // 1.1 seconds

            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_logs_high_query_count_requests()
    {
        app()->shouldReceive('isProduction')->andReturn(false);

        DB::shouldReceive('getQueryLog')
            ->once()
            ->andReturn([]);

        DB::shouldReceive('getQueryLog')
            ->once()
            ->andReturn(array_fill(0, 60, ['query' => 'SELECT * FROM users']));

        DB::shouldReceive('enableQueryLog')->once();

        Log::shouldReceive('warning')->once();

        $request = Request::create('/test');
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_logs_debug_metrics_in_debug_mode()
    {
        app()->shouldReceive('isProduction')->andReturn(false);

        DB::shouldReceive('getQueryLog')
            ->twice()
            ->andReturn([]);

        DB::shouldReceive('enableQueryLog')->once();

        Config::shouldReceive('get')
            ->with('mysql-performance.slow_query_log.long_query_time', 1.0)
            ->andReturn(1.0);

        Config::shouldReceive('get')
            ->with('app.debug')
            ->andReturn(true);

        Log::shouldReceive('debug')->once();

        $request = Request::create('/test');
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_different_request_methods()
    {
        app()->shouldReceive('isProduction')->andReturn(false);

        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        foreach ($methods as $method) {
            DB::shouldReceive('getQueryLog')
                ->twice()
                ->andReturn([]);

            DB::shouldReceive('enableQueryLog')->once();

            $request = Request::create('/test', $method);
            $next = function ($req) {
                return new Response('OK', 200);
            };

            $response = $this->middleware->handle($request, $next);

            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    /**
     * @test
     */
    public function it_handles_different_urls()
    {
        app()->shouldReceive('isProduction')->andReturn(false);

        $urls = [
            '/test',
            '/api/users',
            '/game/village/1',
            '/admin/dashboard',
        ];

        foreach ($urls as $url) {
            DB::shouldReceive('getQueryLog')
                ->twice()
                ->andReturn([]);

            DB::shouldReceive('enableQueryLog')->once();

            $request = Request::create($url);
            $next = function ($req) {
                return new Response('OK', 200);
            };

            $response = $this->middleware->handle($request, $next);

            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    /**
     * @test
     */
    public function it_handles_request_with_query_parameters()
    {
        app()->shouldReceive('isProduction')->andReturn(false);

        DB::shouldReceive('getQueryLog')
            ->twice()
            ->andReturn([]);

        DB::shouldReceive('enableQueryLog')->once();

        $request = Request::create('/test?param1=value1&param2=value2');
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_post_data()
    {
        app()->shouldReceive('isProduction')->andReturn(false);

        DB::shouldReceive('getQueryLog')
            ->twice()
            ->andReturn([]);

        DB::shouldReceive('enableQueryLog')->once();

        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_database_errors()
    {
        app()->shouldReceive('isProduction')->andReturn(false);

        DB::shouldReceive('getQueryLog')
            ->once()
            ->andReturn([]);

        DB::shouldReceive('getQueryLog')
            ->once()
            ->andThrow(new \Exception('Database error'));

        DB::shouldReceive('enableQueryLog')->once();

        $request = Request::create('/test');
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $this->expectException(\Exception::class);
        $this->middleware->handle($request, $next);
    }

    /**
     * @test
     */
    public function it_handles_enable_query_log_errors()
    {
        app()->shouldReceive('isProduction')->andReturn(false);

        DB::shouldReceive('getQueryLog')
            ->twice()
            ->andReturn([]);

        DB::shouldReceive('enableQueryLog')
            ->once()
            ->andThrow(new \Exception('Enable query log error'));

        $request = Request::create('/test');
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $this->expectException(\Exception::class);
        $this->middleware->handle($request, $next);
    }

    /**
     * @test
     */
    public function it_handles_logging_errors()
    {
        app()->shouldReceive('isProduction')->andReturn(false);

        DB::shouldReceive('getQueryLog')
            ->twice()
            ->andReturn([]);

        DB::shouldReceive('enableQueryLog')->once();

        Config::shouldReceive('get')
            ->with('mysql-performance.slow_query_log.long_query_time', 1.0)
            ->andReturn(1.0);

        Log::shouldReceive('warning')
            ->once()
            ->andThrow(new \Exception('Logging error'));

        $request = Request::create('/test');
        $next = function ($req) {
            usleep(1100000);  // 1.1 seconds

            return new Response('OK', 200);
        };

        $this->expectException(\Exception::class);
        $this->middleware->handle($request, $next);
    }

    /**
     * @test
     */
    public function it_handles_config_errors()
    {
        app()->shouldReceive('isProduction')->andReturn(false);

        DB::shouldReceive('getQueryLog')
            ->twice()
            ->andReturn([]);

        DB::shouldReceive('enableQueryLog')->once();

        Config::shouldReceive('get')
            ->with('mysql-performance.slow_query_log.long_query_time', 1.0)
            ->andThrow(new \Exception('Config error'));

        $request = Request::create('/test');
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $this->expectException(\Exception::class);
        $this->middleware->handle($request, $next);
    }

    /**
     * @test
     */
    public function it_handles_next_closure_errors()
    {
        app()->shouldReceive('isProduction')->andReturn(false);

        DB::shouldReceive('getQueryLog')
            ->once()
            ->andReturn([]);

        DB::shouldReceive('enableQueryLog')->once();

        $request = Request::create('/test');
        $next = function ($req): void {
            throw new \Exception('Next closure error');
        };

        $this->expectException(\Exception::class);
        $this->middleware->handle($request, $next);
    }

    /**
     * @test
     */
    public function it_handles_memory_usage_calculation()
    {
        app()->shouldReceive('isProduction')->andReturn(false);

        DB::shouldReceive('getQueryLog')
            ->twice()
            ->andReturn([]);

        DB::shouldReceive('enableQueryLog')->once();

        $request = Request::create('/test');
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_different_response_status_codes()
    {
        app()->shouldReceive('isProduction')->andReturn(false);

        $statusCodes = [200, 201, 400, 401, 403, 404, 500];

        foreach ($statusCodes as $statusCode) {
            DB::shouldReceive('getQueryLog')
                ->twice()
                ->andReturn([]);

            DB::shouldReceive('enableQueryLog')->once();

            $request = Request::create('/test');
            $next = function ($req) use ($statusCode) {
                return new Response('OK', $statusCode);
            };

            $response = $this->middleware->handle($request, $next);

            $this->assertEquals($statusCode, $response->getStatusCode());
        }
    }

    /**
     * @test
     */
    public function it_handles_different_query_counts()
    {
        app()->shouldReceive('isProduction')->andReturn(false);

        $queryCounts = [0, 1, 5, 10, 25, 50, 100];

        foreach ($queryCounts as $count) {
            DB::shouldReceive('getQueryLog')
                ->once()
                ->andReturn([]);

            DB::shouldReceive('getQueryLog')
                ->once()
                ->andReturn(array_fill(0, $count, ['query' => 'SELECT * FROM users']));

            DB::shouldReceive('enableQueryLog')->once();

            $request = Request::create('/test');
            $next = function ($req) {
                return new Response('OK', 200);
            };

            $response = $this->middleware->handle($request, $next);

            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals($count, $response->headers->get('X-Query-Count'));
        }
    }
}
