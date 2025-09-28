<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\GameSecurityMiddleware;
use App\Services\GameErrorHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class GameSecurityMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private GameSecurityMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new GameSecurityMiddleware();
    }

    /**
     * @test
     */
    public function it_allows_valid_request_without_action()
    {
        $request = Request::create('/game/action');
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
    public function it_allows_valid_request_with_action()
    {
        Config::shouldReceive('get')
            ->with('game.security.rate_limiting', [])
            ->andReturn(['attack' => 10]);

        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->andReturn(false);

        RateLimiter::shouldReceive('hit')
            ->once();

        $request = Request::create('/game/attack');
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next, 'attack');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * @test
     */
    public function it_blocks_request_exceeding_rate_limit()
    {
        Config::shouldReceive('get')
            ->with('game.security.rate_limiting', [])
            ->andReturn(['attack' => 10]);

        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->andReturn(true);

        RateLimiter::shouldReceive('availableIn')
            ->once()
            ->andReturn(30);

        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('warning')
            ->once();

        $request = Request::create('/game/attack');
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->middleware->handle($request, $next, 'attack');
    }

    /**
     * @test
     */
    public function it_blocks_sql_injection_attempts()
    {
        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('critical')
            ->once();

        $request = Request::create('/game/action', 'POST', [
            'query' => 'SELECT * FROM users WHERE id = 1 UNION SELECT * FROM passwords',
        ]);
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->middleware->handle($request, $next);
    }

    /**
     * @test
     */
    public function it_blocks_script_injection_attempts()
    {
        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('critical')
            ->once();

        $request = Request::create('/game/action', 'POST', [
            'content' => "<script>alert('xss')</script>",
        ]);
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->middleware->handle($request, $next);
    }

    /**
     * @test
     */
    public function it_logs_rapid_requests()
    {
        RateLimiter::shouldReceive('attempts')
            ->once()
            ->andReturn(15);

        RateLimiter::shouldReceive('hit')
            ->once();

        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('warning')
            ->once();

        $request = Request::create('/game/action');
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_logs_suspicious_user_agent()
    {
        RateLimiter::shouldReceive('attempts')
            ->once()
            ->andReturn(5);

        RateLimiter::shouldReceive('hit')
            ->once();

        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('warning')
            ->once();

        $request = Request::create('/game/action');
        $request->headers->set('User-Agent', 'bot');
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_logs_empty_user_agent()
    {
        RateLimiter::shouldReceive('attempts')
            ->once()
            ->andReturn(5);

        RateLimiter::shouldReceive('hit')
            ->once();

        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('warning')
            ->once();

        $request = Request::create('/game/action');
        $request->headers->set('User-Agent', '');
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_blocks_suspicious_ip()
    {
        RateLimiter::shouldReceive('attempts')
            ->once()
            ->andReturn(5);

        RateLimiter::shouldReceive('hit')
            ->once();

        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('critical')
            ->once();

        $request = Request::create('/game/action');
        $request->server->set('REMOTE_ADDR', '10.0.0.1');
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->middleware->handle($request, $next);
    }

    /**
     * @test
     */
    public function it_adds_security_headers()
    {
        $request = Request::create('/game/action');
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertEquals('1; mode=block', $response->headers->get('X-XSS-Protection'));
        $this->assertEquals('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertEquals("default-src 'self'", $response->headers->get('Content-Security-Policy'));
        $this->assertEquals('geolocation=(), microphone=(), camera=()', $response->headers->get('Permissions-Policy'));
    }

    /**
     * @test
     */
    public function it_logs_security_events()
    {
        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once();

        $request = Request::create('/game/action');
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_exceptions()
    {
        GameErrorHandler::shouldReceive('handleGameError')
            ->once();

        $request = Request::create('/game/action');
        $next = function ($req): void {
            throw new \Exception('Test exception');
        };

        $this->expectException(\Exception::class);
        $this->middleware->handle($request, $next);
    }

    /**
     * @test
     */
    public function it_handles_slow_requests()
    {
        Log::shouldReceive('channel')
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once();

        Log::shouldReceive('warning')
            ->once();

        $request = Request::create('/game/action');
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
    public function it_handles_different_actions()
    {
        $actions = ['attack', 'defend', 'move', 'build', 'research'];

        foreach ($actions as $action) {
            Config::shouldReceive('get')
                ->with('game.security.rate_limiting', [])
                ->andReturn([$action => 10]);

            RateLimiter::shouldReceive('tooManyAttempts')
                ->once()
                ->andReturn(false);

            RateLimiter::shouldReceive('hit')
                ->once();

            $request = Request::create('/game/'.$action);
            $next = function ($req) {
                return new Response('OK', 200);
            };

            $response = $this->middleware->handle($request, $next, $action);

            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    /**
     * @test
     */
    public function it_handles_default_rate_limit()
    {
        Config::shouldReceive('get')
            ->with('game.security.rate_limiting', [])
            ->andReturn([]);

        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->andReturn(false);

        RateLimiter::shouldReceive('hit')
            ->once();

        $request = Request::create('/game/action');
        $next = function ($req) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next, 'unknown_action');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_different_request_methods()
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

        foreach ($methods as $method) {
            $request = Request::create('/game/action', $method);
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
        $urls = [
            '/game/action',
            '/game/village/1',
            '/game/battle/1',
            '/game/alliance/1',
        ];

        foreach ($urls as $url) {
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
    public function it_handles_request_with_headers()
    {
        $request = Request::create('/game/action');
        $request->headers->set('X-Forwarded-For', '203.0.113.1');
        $request->headers->set('X-Real-IP', '203.0.113.2');
        $request->headers->set('Referer', 'https://example.com');
        $request->headers->set('Origin', 'https://example.com');

        $next = function ($req) {
            return new Response('OK', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_generates_security_report()
    {
        $report = GameSecurityMiddleware::generateSecurityReport();

        $this->assertIsArray($report);
        $this->assertArrayHasKey('timestamp', $report);
        $this->assertArrayHasKey('rate_limits', $report);
        $this->assertArrayHasKey('suspicious_activity', $report);
        $this->assertArrayHasKey('security_events', $report);
    }

    /**
     * @test
     */
    public function it_handles_security_report_exception()
    {
        Log::shouldReceive('error')
            ->once();

        $report = GameSecurityMiddleware::generateSecurityReport();

        $this->assertIsArray($report);
    }
}
