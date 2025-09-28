# Laradumps Production Configuration Guide

## Overview

This guide explains how to safely configure Laradumps for production environments while maintaining debugging capabilities in development.

## Production Configuration

### 1. Production-Safe Configuration

Use the `laradumps.production.yaml` file for production environments:

```yaml
# Production configuration - All debugging disabled
observers:
    auto_invoke_app: false
    dump: false
    queries: false
    logs: false
    # ... all other observers disabled

logs:
    info: false
    warning: false
    error: false
    # ... all log levels disabled
```

### 2. Environment-Based Configuration

Create environment-specific configurations:

```php
// In your AppServiceProvider or a dedicated service
public function boot()
{
    if (app()->environment('production')) {
        // Use production configuration
        config(['laradumps.observers.auto_invoke_app' => false]);
        config(['laradumps.observers.dump' => false]);
        config(['laradumps.observers.queries' => false]);
        config(['laradumps.observers.logs' => false]);
    } else {
        // Use development configuration
        config(['laradumps.observers.auto_invoke_app' => true]);
        config(['laradumps.observers.dump' => true]);
        config(['laradumps.observers.queries' => true]);
        config(['laradumps.observers.logs' => true]);
    }
}
```

### 3. Conditional Debugging

Use environment checks in your code:

```php
// Only debug in development
if (app()->environment('local', 'development')) {
    ds('Debug data', $data)->label('Development Debug');
}

// Production-safe debugging
if (config('app.debug')) {
    ds('Debug info', $data)->label('Debug Mode');
}

// Use Laravel's debug flag
if (config('app.debug') && app()->environment('local')) {
    ds('Local debug', $data)->label('Local Development');
}
```

## Deployment Strategies

### 1. Environment Variables

Set environment variables for different environments:

```bash
# Development
LARADUMPS_ENABLED=true
LARADUMPS_AUTO_INVOKE=true

# Production
LARADUMPS_ENABLED=false
LARADUMPS_AUTO_INVOKE=false
```

### 2. Configuration Files

Use different configuration files:

```bash
# Development
cp laradumps.yaml laradumps.local.yaml

# Production
cp laradumps.production.yaml laradumps.yaml
```

### 3. Docker Configuration

For Docker environments:

```dockerfile
# Development
ENV LARADUMPS_ENABLED=true

# Production
ENV LARADUMPS_ENABLED=false
```

## Performance Considerations

### 1. Memory Usage

Laradumps can impact memory usage in production:

```php
// Monitor memory usage
if (memory_get_usage(true) > 128 * 1024 * 1024) { // 128MB
    // Disable Laradumps if memory usage is high
    config(['laradumps.observers.dump' => false]);
}
```

### 2. CPU Usage

Disable expensive operations in production:

```yaml
# Production configuration
observers:
    queries: false          # Disable query monitoring
    slow_queries: false     # Disable slow query detection
    explain: false          # Disable query explanation
```

### 3. Network Usage

Laradumps sends data over the network:

```yaml
# Production configuration
app:
    dispatcher: curl        # Use efficient dispatcher
    port: 9191             # Standard port
```

## Security Considerations

### 1. Sensitive Data

Never debug sensitive information:

```php
// ❌ Never do this
ds('User data', [
    'password' => $user->password,
    'api_key' => $user->api_key,
    'credit_card' => $user->credit_card
]);

// ✅ Safe approach
ds('User data', [
    'user_id' => $user->id,
    'email' => '***@example.com', // Masked
    'created_at' => $user->created_at
]);
```

### 2. Production Data

Avoid debugging production data:

```php
// ❌ Don't debug in production
if (app()->environment('production')) {
    return; // Skip debugging
}

// ✅ Safe debugging
if (app()->environment('local', 'development')) {
    ds('Debug data', $data);
}
```

### 3. Network Security

Ensure Laradumps doesn't expose data:

```yaml
# Production configuration
app:
    primary_host: 127.0.0.1    # Localhost only
    secondary_host: host.docker.internal
    port: 9191                 # Standard port
```

## Monitoring and Logging

### 1. Application Monitoring

Use Laradumps for application monitoring:

```php
// Monitor application health
ds('Application health', [
    'memory_usage' => memory_get_usage(true),
    'peak_memory' => memory_get_peak_usage(true),
    'execution_time' => microtime(true) - LARAVEL_START,
    'database_connections' => DB::getConnections(),
])->label('Application Health');
```

### 2. Error Monitoring

Monitor errors without exposing sensitive data:

```php
try {
    // Your code
} catch (\Exception $e) {
    // Log error without sensitive data
    ds('Error occurred', [
        'error_type' => get_class($e),
        'error_message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'timestamp' => now(),
    ])->label('Error Monitor');
    
    // Don't expose stack trace in production
    if (app()->environment('local')) {
        ds('Stack trace', $e->getTraceAsString())->label('Error Trace');
    }
}
```

### 3. Performance Monitoring

Monitor performance metrics:

```php
// Monitor database performance
ds('Database performance', [
    'query_count' => count(DB::getQueryLog()),
    'total_time' => array_sum(array_column(DB::getQueryLog(), 'time')),
    'slow_queries' => array_filter(DB::getQueryLog(), function($query) {
        return $query['time'] > 100; // 100ms threshold
    })
])->label('Database Performance');
```

## Best Practices

### 1. Development vs Production

```php
// Use environment-specific debugging
class DebugService
{
    public function debug($data, $label = 'Debug')
    {
        if (app()->environment('local', 'development')) {
            ds($data)->label($label);
        }
    }
    
    public function productionSafe($data, $label = 'Production Debug')
    {
        // Only debug non-sensitive data
        $safeData = $this->sanitizeData($data);
        ds($safeData)->label($label);
    }
    
    private function sanitizeData($data)
    {
        // Remove sensitive fields
        if (is_array($data)) {
            unset($data['password'], $data['api_key'], $data['credit_card']);
        }
        return $data;
    }
}
```

### 2. Conditional Loading

```php
// Only load Laradumps in development
if (app()->environment('local', 'development')) {
    // Laradumps debugging code
    ds('Development debug', $data);
}
```

### 3. Configuration Management

```php
// Use configuration for debugging
if (config('laradumps.enabled', false)) {
    ds('Configurable debug', $data)->label('Config Debug');
}
```

## Troubleshooting

### 1. Production Issues

If Laradumps causes issues in production:

```bash
# Disable Laradumps completely
export LARADUMPS_ENABLED=false

# Or use production configuration
cp laradumps.production.yaml laradumps.yaml
```

### 2. Performance Issues

If Laradumps impacts performance:

```yaml
# Minimal configuration
observers:
    auto_invoke_app: false
    dump: false
    queries: false
    logs: false
```

### 3. Memory Issues

If Laradumps causes memory issues:

```php
// Monitor memory usage
if (memory_get_usage(true) > 100 * 1024 * 1024) { // 100MB
    // Disable Laradumps
    config(['laradumps.observers.dump' => false]);
}
```

## Deployment Checklist

### Before Deployment

- [ ] Verify production configuration is used
- [ ] Check that sensitive data is not being debugged
- [ ] Ensure Laradumps is disabled in production
- [ ] Test performance impact
- [ ] Verify security settings

### After Deployment

- [ ] Monitor application performance
- [ ] Check memory usage
- [ ] Verify no debug output in production
- [ ] Test error handling
- [ ] Monitor security

## Configuration Examples

### Development Configuration

```yaml
# laradumps.yaml (development)
observers:
    auto_invoke_app: true
    dump: true
    queries: true
    logs: true
```

### Production Configuration

```yaml
# laradumps.production.yaml
observers:
    auto_invoke_app: false
    dump: false
    queries: false
    logs: false
```

### Staging Configuration

```yaml
# laradumps.staging.yaml
observers:
    auto_invoke_app: false
    dump: true          # Allow debugging in staging
    queries: false      # Disable query monitoring
    logs: true          # Allow log monitoring
```

---

**Note**: Always test your production configuration in a staging environment before deploying to production. Ensure that Laradumps doesn't impact performance or expose sensitive data.