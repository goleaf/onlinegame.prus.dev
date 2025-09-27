<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MySQL Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for MySQL performance monitoring and optimization
    | Based on Oh Dear's SQL performance improvements guide
    |
    */

    'slow_query_log' => [
        'enabled' => env('MYSQL_SLOW_QUERY_LOG_ENABLED', false),
        'file' => env('MYSQL_SLOW_QUERY_LOG_FILE', '/var/log/mysql-slow-query.log'),
        'long_query_time' => env('MYSQL_LONG_QUERY_TIME', 1.0), // seconds
        'log_queries_not_using_indexes' => env('MYSQL_LOG_QUERIES_NOT_USING_INDEXES', true),
        'min_examined_row_limit' => env('MYSQL_MIN_EXAMINED_ROW_LIMIT', 0),
    ],

    'general_query_log' => [
        'enabled' => env('MYSQL_GENERAL_QUERY_LOG_ENABLED', false),
        'file' => env('MYSQL_GENERAL_QUERY_LOG_FILE', '/var/log/mysql-general-query.log'),
    ],

    'performance_schema' => [
        'enabled' => env('MYSQL_PERFORMANCE_SCHEMA_ENABLED', true),
    ],

    'query_optimization' => [
        'enable_query_cache' => env('MYSQL_QUERY_CACHE_ENABLED', true),
        'query_cache_size' => env('MYSQL_QUERY_CACHE_SIZE', '64M'),
        'query_cache_type' => env('MYSQL_QUERY_CACHE_TYPE', 'ON'),
        'query_cache_limit' => env('MYSQL_QUERY_CACHE_LIMIT', '2M'),
    ],

    'connection_optimization' => [
        'max_connections' => env('MYSQL_MAX_CONNECTIONS', 151),
        'connect_timeout' => env('MYSQL_CONNECT_TIMEOUT', 10),
        'wait_timeout' => env('MYSQL_WAIT_TIMEOUT', 28800),
        'interactive_timeout' => env('MYSQL_INTERACTIVE_TIMEOUT', 28800),
    ],

    'buffer_optimization' => [
        'innodb_buffer_pool_size' => env('MYSQL_INNODB_BUFFER_POOL_SIZE', '128M'),
        'innodb_log_file_size' => env('MYSQL_INNODB_LOG_FILE_SIZE', '64M'),
        'innodb_log_buffer_size' => env('MYSQL_INNODB_LOG_BUFFER_SIZE', '16M'),
        'key_buffer_size' => env('MYSQL_KEY_BUFFER_SIZE', '32M'),
    ],

    'monitoring' => [
        'enable_processlist_monitoring' => env('MYSQL_PROCESSLIST_MONITORING', false),
        'enable_slow_query_analysis' => env('MYSQL_SLOW_QUERY_ANALYSIS', false),
        'pt_query_digest_path' => env('PT_QUERY_DIGEST_PATH', '/usr/bin/pt-query-digest'),
    ],
];

