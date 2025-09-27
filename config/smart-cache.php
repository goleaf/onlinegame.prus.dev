<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Smart Cache Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration settings for the SmartCache package.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Thresholds
    |--------------------------------------------------------------------------
    |
    | Configure size-based thresholds that trigger optimization strategies.
    |
    */
    'thresholds' => [
        'compression' => 1024 * 50, // 50KB
        'chunking' => 1024 * 100,   // 100KB
    ],

    /*
    |--------------------------------------------------------------------------
    | Strategies
    |--------------------------------------------------------------------------
    |
    | Configure which optimization strategies are enabled and their options.
    |
    */
    'strategies' => [
        'compression' => [
            'enabled' => true,
            'level' => 6, // 0-9 (higher = better compression but slower)
        ],
        'chunking' => [
            'enabled' => true,
            'chunk_size' => 1000, // Items per chunk for arrays/collections
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback
    |--------------------------------------------------------------------------
    |
    | Configure fallback behavior if optimizations fail or are incompatible.
    |
    */
    'fallback' => [
        'enabled' => true,
        'log_errors' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Drivers
    |--------------------------------------------------------------------------
    |
    | Configure which cache drivers should use which optimization strategies.
    | Set to null to use the global strategies configuration.
    |
    */
    'drivers' => [
        'redis' => [
            'compression' => [
                'enabled' => true,
                'level' => 6,
            ],
            'chunking' => [
                'enabled' => true,
                'chunk_size' => 1000,
            ],
        ],
        'file' => [
            'compression' => [
                'enabled' => true,
                'level' => 4,
            ],
            'chunking' => [
                'enabled' => false,
            ],
        ],
        'database' => [
            'compression' => [
                'enabled' => false,
            ],
            'chunking' => [
                'enabled' => true,
                'chunk_size' => 500,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Game-Specific Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration specific to the game application's caching needs.
    |
    */
    'game' => [
        'ttl' => [
            'real_time' => 60,      // 1 minute for real-time data
            'frequent' => 300,      // 5 minutes for frequently accessed data
            'static' => 1800,       // 30 minutes for static data
            'long_term' => 3600,    // 1 hour for long-term data
        ],
        'keys' => [
            'prefix' => 'game_',
            'separator' => '_',
        ],
        'optimization' => [
            'enable_compression' => true,
            'enable_chunking' => true,
            'memory_threshold' => 1024 * 100, // 100KB
        ],
    ],
    'drivers' => [
        'redis' => null, // Use global settings
        'file' => [
            'compression' => true,
            'chunking' => true,
        ],
        'memcached' => [
            'compression' => false, // Memcached has its own compression
            'chunking' => true,
        ],
    ],
]; 