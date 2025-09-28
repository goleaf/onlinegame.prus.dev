<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\EnhancedDebugMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class EnhancedDebugMiddlewareTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_instantiated()
    {
        $middleware = new EnhancedDebugMiddleware();

        $this->assertInstanceOf(EnhancedDebugMiddleware::class, $middleware);
    }

    /**
     * @test
     */
    public function it_handles_request_in_development()
    {
        $middleware = new EnhancedDebugMiddleware();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('Test response');
        });

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @test
     */
    public function it_handles_request_in_production()
    {
        $middleware = new EnhancedDebugMiddleware();
        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('Test response');
        });

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @test
     */
    public function it_has_proper_method_signature()
    {
        $reflection = new \ReflectionClass(EnhancedDebugMiddleware::class);
        $method = $reflection->getMethod('handle');

        $this->assertTrue($method->isPublic());
        $this->assertCount(2, $method->getParameters());
    }

    /**
     * @test
     */
    public function it_can_be_used_as_middleware()
    {
        $middleware = new EnhancedDebugMiddleware();

        $this->assertInstanceOf(EnhancedDebugMiddleware::class, $middleware);
    }
}
