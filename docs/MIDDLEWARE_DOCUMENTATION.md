# Middleware Documentation

This document provides comprehensive information about all middleware available in the online game application.

## Overview

The application uses a combination of Laravel's built-in middleware, LaraUtilX middleware, and custom game-specific middleware to provide security, performance monitoring, and enhanced functionality.

## Middleware Configuration

All middleware is configured in `bootstrap/app.php`:

```php
$middleware->alias([
    'game.auth' => \App\Http\Middleware\GameAuthMiddleware::class,
    'game.rate_limit' => \App\Http\Middleware\GameRateLimitMiddleware::class,
    'access.log' => \LaraUtilX\Http\Middleware\AccessLogMiddleware::class,
    'query.performance' => \App\Http\Middleware\QueryPerformanceMiddleware::class,
    'enhanced.debug' => \App\Http\Middleware\EnhancedDebugMiddleware::class,
    'game.security' => \App\Http\Middleware\GameSecurityMiddleware::class,
    'websocket.auth' => \App\Http\Middleware\WebSocketAuthMiddleware::class,
]);
```

## Available Middleware

### 1. LaraUtilX Middleware

#### AccessLogMiddleware
- **Class**: `LaraUtilX\Http\Middleware\AccessLogMiddleware`
- **Alias**: `access.log`
- **Purpose**: Logs all HTTP requests with detailed information
- **Usage**: Automatically applied to web and API routes
- **Features**:
  - Request/response logging
  - Performance metrics
  - User agent tracking
  - IP address logging

### 2. Game-Specific Middleware

#### GameAuthMiddleware
- **Class**: `App\Http\Middleware\GameAuthMiddleware`
- **Alias**: `game.auth`
- **Purpose**: Handles authentication for game-specific routes
- **Features**:
  - Player account validation
  - Game session management
  - Authentication state checking
  - Redirect handling for unauthenticated users

#### GameRateLimitMiddleware
- **Class**: `App\Http\Middleware\GameRateLimitMiddleware`
- **Alias**: `game.rate_limit`
- **Purpose**: Rate limiting for game actions
- **Features**:
  - Action-specific rate limits
  - Player-based rate limiting
  - Configurable limits per action type
  - Rate limit headers

#### GameSecurityMiddleware
- **Class**: `App\Http\Middleware\GameSecurityMiddleware`
- **Alias**: `game.security`
- **Purpose**: Security measures for game operations
- **Features**:
  - CSRF protection
  - XSS prevention
  - Input validation
  - Security headers

### 3. Performance Middleware

#### QueryPerformanceMiddleware
- **Class**: `App\Http\Middleware\QueryPerformanceMiddleware`
- **Alias**: `query.performance`
- **Purpose**: Monitors database query performance
- **Features**:
  - Query execution time tracking
  - Slow query detection
  - Query count monitoring
  - Performance reporting

#### SeoMiddleware
- **Class**: `App\Http\Middleware\SeoMiddleware`
- **Purpose**: SEO optimization for web routes
- **Features**:
  - Meta tag injection
  - Canonical URL handling
  - Structured data injection
  - Performance optimization

### 4. Development Middleware

#### EnhancedDebugMiddleware
- **Class**: `App\Http\Middleware\EnhancedDebugMiddleware`
- **Alias**: `enhanced.debug`
- **Purpose**: Enhanced debugging capabilities
- **Features**:
  - Detailed error reporting
  - Request/response debugging
  - Performance profiling
  - Debug information injection

### 5. WebSocket Middleware

#### WebSocketAuthMiddleware
- **Class**: `App\Http\Middleware\WebSocketAuthMiddleware`
- **Alias**: `websocket.auth`
- **Purpose**: Authentication for WebSocket connections
- **Features**:
  - WebSocket authentication
  - Connection validation
  - User session verification
  - Real-time communication security

## Middleware Groups

### Web Middleware Group
Applied to all web routes:

```php
$middleware->web(append: [
    \LaraUtilX\Http\Middleware\AccessLogMiddleware::class,
    \App\Http\Middleware\QueryPerformanceMiddleware::class,
    \App\Http\Middleware\SeoMiddleware::class,
]);
```

### API Middleware Group
Applied to all API routes:

```php
$middleware->api(append: [
    \LaraUtilX\Http\Middleware\AccessLogMiddleware::class,
]);
```

## Usage Examples

### Applying Middleware to Routes

```php
// Single middleware
Route::middleware('game.auth')->group(function () {
    Route::get('/game/dashboard', [GameController::class, 'dashboard']);
});

// Multiple middleware
Route::middleware(['game.auth', 'game.rate_limit'])->group(function () {
    Route::post('/game/action', [GameController::class, 'performAction']);
});

// WebSocket middleware
Route::middleware('websocket.auth')->group(function () {
    Route::get('/websocket/connect', [WebSocketController::class, 'connect']);
});
```

### Applying Middleware to Controllers

```php
class GameController extends Controller
{
    public function __construct()
    {
        $this->middleware('game.auth');
        $this->middleware('game.rate_limit')->only(['performAction']);
        $this->middleware('game.security')->except(['index']);
    }
}
```

### Conditional Middleware

```php
// Apply middleware conditionally
if (config('app.debug')) {
    Route::middleware('enhanced.debug')->group(function () {
        // Debug routes
    });
}
```

## Middleware Order

The order of middleware execution is important:

1. **Global Middleware** (applied to all requests)
2. **Group Middleware** (web/api)
3. **Route Middleware** (specific to routes)
4. **Controller Middleware** (applied in controller constructor)

## Configuration

### Environment Variables

```env
# Enable/disable specific middleware
GAME_DEBUG_MIDDLEWARE=true
QUERY_PERFORMANCE_MONITORING=true
ENHANCED_LOGGING=true
```

### Middleware Configuration Files

```php
// config/middleware.php (if needed)
return [
    'game' => [
        'rate_limit' => [
            'enabled' => env('GAME_RATE_LIMIT_ENABLED', true),
            'max_attempts' => env('GAME_RATE_LIMIT_MAX_ATTEMPTS', 60),
            'decay_minutes' => env('GAME_RATE_LIMIT_DECAY_MINUTES', 1),
        ],
    ],
];
```

## Testing Middleware

### Unit Tests

```php
public function test_game_auth_middleware_redirects_guests()
{
    $response = $this->get('/game/dashboard');
    $response->assertRedirect('/login');
}

public function test_rate_limit_middleware_blocks_excessive_requests()
{
    for ($i = 0; $i < 100; $i++) {
        $response = $this->post('/game/action');
    }
    
    $response->assertStatus(429);
}
```

### Integration Tests

```php
public function test_middleware_stack_execution()
{
    $response = $this->actingAs($this->user)
        ->get('/game/dashboard');
    
    // Verify middleware effects
    $this->assertDatabaseHas('access_logs', [
        'url' => '/game/dashboard'
    ]);
}
```

## Performance Considerations

### Middleware Performance Impact

1. **AccessLogMiddleware**: Minimal impact, async logging
2. **QueryPerformanceMiddleware**: Low impact, only in debug mode
3. **GameAuthMiddleware**: Moderate impact, database queries
4. **GameRateLimitMiddleware**: Low impact, cache-based
5. **SeoMiddleware**: Minimal impact, template processing

### Optimization Tips

1. **Conditional Loading**: Only load middleware when needed
2. **Caching**: Use caching for expensive operations
3. **Async Processing**: Use queues for heavy logging
4. **Middleware Ordering**: Place lightweight middleware first

## Troubleshooting

### Common Issues

1. **Middleware Not Applied**: Check route definitions and controller constructors
2. **Performance Issues**: Review middleware order and implementation
3. **Authentication Problems**: Verify middleware configuration
4. **Rate Limiting Issues**: Check rate limit configuration

### Debugging

```php
// Enable middleware debugging
Log::info('Middleware executed', [
    'middleware' => 'GameAuthMiddleware',
    'user' => auth()->id(),
    'route' => request()->route()->getName(),
]);
```

## Security Considerations

### Security Best Practices

1. **Always use CSRF protection** for state-changing operations
2. **Implement rate limiting** for public endpoints
3. **Validate user permissions** in authentication middleware
4. **Log security events** for monitoring
5. **Use HTTPS** for sensitive operations

### Security Headers

The application includes security headers via middleware:

```php
// Security headers applied by GameSecurityMiddleware
$response->headers->set('X-Content-Type-Options', 'nosniff');
$response->headers->set('X-Frame-Options', 'DENY');
$response->headers->set('X-XSS-Protection', '1; mode=block');
```

## Conclusion

The middleware system provides comprehensive security, performance monitoring, and functionality enhancement for the online game application. Each middleware serves a specific purpose and contributes to the overall application architecture.

For questions or issues with middleware, refer to the individual middleware class documentation or contact the development team.
