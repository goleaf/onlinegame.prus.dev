# SQL Performance Optimization Guide

This guide implements SQL performance improvements based on [Oh Dear's optimization techniques](https://ohdear.app/news-and-updates/sql-performance-improvements-finding-the-right-queries-to-fix-part-1).

## Overview

The implementation includes:

- Laravel Debug Bar for local development
- MySQL slow query log configuration
- Query performance middleware
- Lazy loading prevention
- Query analysis tools

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# MySQL Performance Monitoring Configuration
# Based on Oh Dear's SQL performance improvements guide

# Slow Query Log
MYSQL_SLOW_QUERY_LOG_ENABLED=false
MYSQL_SLOW_QUERY_LOG_FILE=/var/log/mysql-slow-query.log
MYSQL_LONG_QUERY_TIME=1.0
MYSQL_LOG_QUERIES_NOT_USING_INDEXES=true
MYSQL_MIN_EXAMINED_ROW_LIMIT=0

# General Query Log
MYSQL_GENERAL_QUERY_LOG_ENABLED=false
MYSQL_GENERAL_QUERY_LOG_FILE=/var/log/mysql-general-query.log

# Performance Schema
MYSQL_PERFORMANCE_SCHEMA_ENABLED=true

# Query Optimization
MYSQL_QUERY_CACHE_ENABLED=true
MYSQL_QUERY_CACHE_SIZE=64M
MYSQL_QUERY_CACHE_TYPE=ON
MYSQL_QUERY_CACHE_LIMIT=2M

# Connection Optimization
MYSQL_MAX_CONNECTIONS=151
MYSQL_CONNECT_TIMEOUT=10
MYSQL_WAIT_TIMEOUT=28800
MYSQL_INTERACTIVE_TIMEOUT=28800

# Buffer Optimization
MYSQL_INNODB_BUFFER_POOL_SIZE=128M
MYSQL_INNODB_LOG_FILE_SIZE=64M
MYSQL_INNODB_LOG_BUFFER_SIZE=16M
MYSQL_KEY_BUFFER_SIZE=32M

# Monitoring
MYSQL_PROCESSLIST_MONITORING=false
MYSQL_SLOW_QUERY_ANALYSIS=false
PT_QUERY_DIGEST_PATH=/usr/bin/pt-query-digest

# Laravel Debug Bar (for local development)
DEBUGBAR_ENABLED=null
DEBUGBAR_OPTIONS_DB_EXPLAIN_ENABLED=false
DEBUGBAR_OPTIONS_DB_SLOW_THRESHOLD=false
DEBUGBAR_OPTIONS_DB_HINTS=false
```

## Setup Commands

### 1. Setup MySQL Performance Monitoring

```bash
php artisan mysql:setup-performance
```

This command will:

- Configure slow query log
- Enable performance schema
- Set up query optimization
- Configure connection and buffer settings

### 2. Analyze Slow Queries

```bash
# Analyze slow queries with default settings
php artisan mysql:analyze-slow-queries

# Analyze with custom file and limit
php artisan mysql:analyze-slow-queries --file=/var/log/mysql-slow-query.log --limit=50

# Export results as CSV
php artisan mysql:analyze-slow-queries --format=csv > slow-queries.csv
```

## Features

### 1. Laravel Debug Bar Integration

The debug bar is already configured and will show:

- Total query execution time
- Number of queries executed
- Duplicate queries (N+1 detection)
- Query details with backtrace

### 2. Lazy Loading Prevention

Configured in `AppServiceProvider` to prevent:

- N+1 queries
- Silent attribute access
- Missing relationship access

### 3. Query Performance Middleware

Automatically logs:

- Slow requests (>1s by default)
- High query count requests (>50 queries)
- Performance metrics for debugging

### 4. MySQL Configuration

The system includes optimized MySQL settings for:

- Query caching
- Connection pooling
- Buffer optimization
- Performance schema monitoring

## Usage Examples

### Local Development

1. Enable debug bar in your `.env`:

```env
DEBUGBAR_ENABLED=true
APP_DEBUG=true
```

2. Monitor queries in the debug bar at the bottom of your pages

3. Check for N+1 queries - they will throw exceptions in development

### Production Monitoring

1. Enable slow query log:

```env
MYSQL_SLOW_QUERY_LOG_ENABLED=true
MYSQL_LONG_QUERY_TIME=0.5
```

2. Monitor the log file:

```bash
tail -f /var/log/mysql-slow-query.log
```

3. Analyze periodically:

```bash
php artisan mysql:analyze-slow-queries --limit=100
```

### Using Percona Toolkit (Optional)

If you have Percona Toolkit installed:

```bash
# Install percona-toolkit
apt install percona-toolkit  # Debian/Ubuntu
brew install percona-toolkit # macOS

# Analyze slow query log
pt-query-digest /var/log/mysql-slow-query.log

# Analyze general query log
sed 's/Execute\t/Query\t/' /var/log/mysql-general-query.log > /tmp/general-query-edited.log
pt-query-digest --type=genlog /tmp/general-query-edited.log
```

## Best Practices

### 1. Query Optimization

- Use `with()` for eager loading
- Use `withCount()` for relationship counts
- Avoid `SELECT *` - specify columns
- Add indexes for frequently queried columns
- Use `when()` for conditional queries

### 2. N+1 Query Prevention

```php
// Bad - causes N+1 queries
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->comments->count();
}

// Good - eager load counts
$posts = Post::withCount('comments')->get();
foreach ($posts as $post) {
    echo $post->comments_count;
}
```

### 3. Index Optimization

- Add composite indexes for multi-column queries
- Use partial indexes for filtered queries
- Monitor index usage with `EXPLAIN`

### 4. Caching Strategy

- Use Redis for frequently accessed data
- Implement query result caching
- Cache expensive calculations

## Monitoring and Alerts

### Application Logs

The middleware automatically logs:

- Slow requests with full context
- High query count requests
- Performance metrics

### Database Monitoring

Monitor these MySQL metrics:

- `Slow_queries` - number of slow queries
- `Questions` - total queries executed
- `Connections` - connection count
- `Threads_connected` - active connections

### Alert Thresholds

Recommended thresholds:

- Query time > 1 second
- Query count > 50 per request
- Connection count > 80% of max_connections
- Slow queries > 1% of total queries

## Troubleshooting

### Common Issues

1. **Debug bar not showing**: Check `DEBUGBAR_ENABLED=true` in `.env`
2. **Slow query log not working**: Ensure MySQL has write permissions to log file
3. **Performance middleware errors**: Check database connection and permissions
4. **High memory usage**: Reduce query cache size or disable in development

### Debug Commands

```bash
# Check MySQL configuration
mysql -e "SHOW VARIABLES LIKE '%slow%';"
mysql -e "SHOW VARIABLES LIKE '%query_cache%';"

# Check current queries
mysql -e "SHOW PROCESSLIST;"

# Check slow query log status
mysql -e "SHOW VARIABLES LIKE 'slow_query_log%';"
```

## Integration with Existing Code

The performance monitoring integrates seamlessly with the existing `QueryOptimizationService` and Livewire components. The middleware will automatically track performance metrics for all requests.

## Performance Impact

The monitoring adds minimal overhead:

- ~1-2ms per request for middleware
- Debug bar only active in development
- Slow query log has negligible impact
- Performance schema overhead is minimal

## Next Steps

1. **Enable monitoring** in your environment
2. **Run the setup command** to configure MySQL
3. **Monitor slow queries** regularly
4. **Optimize identified queries** using the analysis tools
5. **Set up alerts** for performance degradation

## References

- [Oh Dear SQL Performance Guide](https://ohdear.app/news-and-updates/sql-performance-improvements-finding-the-right-queries-to-fix-part-1)
- [Laravel Debug Bar Documentation](https://github.com/barryvdh/laravel-debugbar)
- [MySQL Performance Schema](https://dev.mysql.com/doc/refman/8.0/en/performance-schema.html)
- [Percona Toolkit Documentation](https://www.percona.com/doc/percona-toolkit/LATEST/index.html)
