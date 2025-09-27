<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Toggles
    |--------------------------------------------------------------------------
    |
    | This file contains feature toggles for the application. Features can be
    | enabled or disabled globally, per environment, or per user.
    |
    | Format:
    | 'feature_name' => true|false,
    | 'feature_name' => [
    |     'user.1' => true,
    |     'user.2' => false,
    |     'environment.local' => true,
    |     'environment.production' => false,
    | ],
    |
    */

    // Larautilx Integration Features
    'larautilx_integration' => true,
    'advanced_caching' => true,
    'enhanced_filtering' => true,
    'standardized_responses' => true,
    'request_logging' => true,

    // Game Features
    'advanced_map' => true,
    'real_time_updates' => true,
    'enhanced_statistics' => true,
    'geographic_features' => true,
    'user_management' => true,

    // Performance Features
    'query_optimization' => true,
    'caching_optimization' => true,
    'rate_limiting' => true,

    // Development Features
    'debug_mode' => env('APP_DEBUG', false),
    'detailed_logging' => env('APP_DEBUG', false),
    'performance_monitoring' => env('APP_DEBUG', false),

    // User-specific overrides (examples)
    'beta_features' => [
        'user.1' => true,  // Enable for user ID 1
        'user.2' => false, // Disable for user ID 2
    ],

    // Environment-specific overrides
    'development_features' => [
        'environment.local' => true,
        'environment.staging' => true,
        'environment.production' => false,
    ],
];