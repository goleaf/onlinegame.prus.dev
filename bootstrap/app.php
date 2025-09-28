<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'game.auth' => \App\Http\Middleware\GameAuthMiddleware::class,
            'game.rate_limit' => \App\Http\Middleware\GameRateLimitMiddleware::class,
            'access.log' => \LaraUtilX\Http\Middleware\AccessLogMiddleware::class,
            'query.performance' => \App\Http\Middleware\QueryPerformanceMiddleware::class,
            'enhanced.debug' => \App\Http\Middleware\EnhancedDebugMiddleware::class,
            'game.security' => \App\Http\Middleware\GameSecurityMiddleware::class,
            'websocket.auth' => \App\Http\Middleware\WebSocketAuthMiddleware::class,
        ]);

        // Add middleware to web middleware group
        $middleware->web(append: [
            \LaraUtilX\Http\Middleware\AccessLogMiddleware::class,
            \App\Http\Middleware\QueryPerformanceMiddleware::class,
            \App\Http\Middleware\SeoMiddleware::class,
        ]);

        // Add middleware to API middleware group
        $middleware->api(append: [
            \LaraUtilX\Http\Middleware\AccessLogMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
