<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\GameRateLimitMiddleware;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class GameRateLimitMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected GameRateLimitMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new GameRateLimitMiddleware();
    }

    /**
     * @test
     */
    public function it_allows_request_within_rate_limit()
    {
        $request = Request::create('/game/dashboard');
        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    /**
     * @test
     */
    public function it_blocks_request_exceeding_rate_limit()
    {
        $request = Request::create('/game/dashboard');
        $key = 'game-actions:'.$request->ip();

        // Hit the rate limit
        for ($i = 0; $i < 60; $i++) {
            RateLimiter::hit($key, 60);
        }

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Too many game actions', $response->getContent());
    }

    /**
     * @test
     */
    public function it_returns_retry_after_in_response()
    {
        $request = Request::create('/game/dashboard');
        $key = 'game-actions:'.$request->ip();

        // Hit the rate limit
        for ($i = 0; $i < 60; $i++) {
            RateLimiter::hit($key, 60);
        }

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(429, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('retry_after', $responseData);
        $this->assertIsInt($responseData['retry_after']);
    }

    /**
     * @test
     */
    public function it_uses_ip_address_for_rate_limiting()
    {
        $request1 = Request::create('/game/dashboard');
        $request1->server->set('REMOTE_ADDR', '192.168.1.1');

        $request2 = Request::create('/game/dashboard');
        $request2->server->set('REMOTE_ADDR', '192.168.1.2');

        // Hit rate limit for first IP
        $key1 = 'game-actions:192.168.1.1';
        for ($i = 0; $i < 60; $i++) {
            RateLimiter::hit($key1, 60);
        }

        // First IP should be blocked
        $response1 = $this->middleware->handle($request1, function ($req) {
            return response('OK');
        });
        $this->assertEquals(429, $response1->getStatusCode());

        // Second IP should be allowed
        $response2 = $this->middleware->handle($request2, function ($req) {
            return response('OK');
        });
        $this->assertEquals(200, $response2->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_different_request_methods()
    {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];

        foreach ($methods as $method) {
            $request = Request::create('/game/dashboard', $method);
            $response = $this->middleware->handle($request, function ($req) {
                return response('OK');
            });

            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    /**
     * @test
     */
    public function it_handles_different_urls()
    {
        $urls = [
            '/game/dashboard',
            '/game/village/1',
            '/game/battle/1',
            '/game/alliance/1',
        ];

        foreach ($urls as $url) {
            $request = Request::create($url);
            $response = $this->middleware->handle($request, function ($req) {
                return response('OK');
            });

            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    /**
     * @test
     */
    public function it_handles_request_with_query_parameters()
    {
        $request = Request::create('/game/dashboard?page=1&filter=active');
        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_post_data()
    {
        $request = Request::create('/game/dashboard', 'POST', [
            'name' => 'Test Village',
            'description' => 'A test village',
        ]);
        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_headers()
    {
        $request = Request::create('/game/dashboard');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->headers->set('Accept', 'application/json');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_cookies()
    {
        $request = Request::create('/game/dashboard');
        $request->cookies->set('game_session', 'test-session-id');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_session()
    {
        $request = Request::create('/game/dashboard');
        $request->session()->put('game_data', 'test-data');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_files()
    {
        $request = Request::create('/game/dashboard', 'POST', [], [], [
            'avatar' => [
                'name' => 'avatar.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/php123',
                'error' => 0,
                'size' => 1024,
            ],
        ]);

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_json_data()
    {
        $request = Request::create('/game/dashboard', 'POST', [], [], [], [], json_encode([
            'name' => 'Test Village',
            'description' => 'A test village',
        ]));
        $request->headers->set('Content-Type', 'application/json');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_xml_data()
    {
        $request = Request::create('/game/dashboard', 'POST', [], [], [], [], '<?xml version="1.0"?><root><name>Test Village</name></root>');
        $request->headers->set('Content-Type', 'application/xml');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_form_data()
    {
        $request = Request::create('/game/dashboard', 'POST', [
            'name' => 'Test Village',
            'description' => 'A test village',
        ]);
        $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_multipart_data()
    {
        $request = Request::create('/game/dashboard', 'POST', [
            'name' => 'Test Village',
            'description' => 'A test village',
        ], [], [
            'avatar' => [
                'name' => 'avatar.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/php123',
                'error' => 0,
                'size' => 1024,
            ],
        ]);
        $request->headers->set('Content-Type', 'multipart/form-data');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_different_ip_addresses()
    {
        $ipAddresses = [
            '192.168.1.1',
            '192.168.1.2',
            '10.0.0.1',
            '172.16.0.1',
            '127.0.0.1',
        ];

        foreach ($ipAddresses as $ip) {
            $request = Request::create('/game/dashboard');
            $request->server->set('REMOTE_ADDR', $ip);

            $response = $this->middleware->handle($request, function ($req) {
                return response('OK');
            });

            $this->assertEquals(200, $response->getStatusCode());
        }
    }

    /**
     * @test
     */
    public function it_handles_request_with_ipv6_address()
    {
        $request = Request::create('/game/dashboard');
        $request->server->set('REMOTE_ADDR', '2001:0db8:85a3:0000:0000:8a2e:0370:7334');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_forwarded_ip()
    {
        $request = Request::create('/game/dashboard');
        $request->headers->set('X-Forwarded-For', '192.168.1.100');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_real_ip()
    {
        $request = Request::create('/game/dashboard');
        $request->headers->set('X-Real-IP', '192.168.1.200');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_client_ip()
    {
        $request = Request::create('/game/dashboard');
        $request->headers->set('X-Client-IP', '192.168.1.300');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_remote_addr()
    {
        $request = Request::create('/game/dashboard');
        $request->server->set('REMOTE_ADDR', '192.168.1.400');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_http_client_ip()
    {
        $request = Request::create('/game/dashboard');
        $request->server->set('HTTP_CLIENT_IP', '192.168.1.500');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_http_x_forwarded_for()
    {
        $request = Request::create('/game/dashboard');
        $request->server->set('HTTP_X_FORWARDED_FOR', '192.168.1.600');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_http_x_forwarded_for_multiple_ips()
    {
        $request = Request::create('/game/dashboard');
        $request->server->set('HTTP_X_FORWARDED_FOR', '192.168.1.700, 10.0.0.1, 172.16.0.1');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_http_x_forwarded_for_with_port()
    {
        $request = Request::create('/game/dashboard');
        $request->server->set('HTTP_X_FORWARDED_FOR', '192.168.1.800:8080');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_http_x_forwarded_for_with_protocol()
    {
        $request = Request::create('/game/dashboard');
        $request->server->set('HTTP_X_FORWARDED_FOR', '192.168.1.900:8080');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function it_handles_request_with_http_x_forwarded_for_with_protocol_and_port()
    {
        $request = Request::create('/game/dashboard');
        $request->server->set('HTTP_X_FORWARDED_FOR', '192.168.1.1000:8080');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }
}
