<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Updater Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the application updater system.
    |
    */

    'repository_url' => env('UPDATER_REPOSITORY_URL', 'https://github.com/your-org/your-repo.git'),
    
    'branch' => env('UPDATER_BRANCH', 'main'),
    
    'maintenance_mode' => env('UPDATER_MAINTENANCE_MODE', true),
    
    'backup_before_update' => env('UPDATER_BACKUP_BEFORE_UPDATE', true),
    
    'auto_optimize' => env('UPDATER_AUTO_OPTIMIZE', true),
    
    'timeout' => env('UPDATER_TIMEOUT', 300), // 5 minutes
    
    'allowed_ips' => [
        // Add IP addresses that are allowed to perform updates
        // '127.0.0.1',
        // '::1',
    ],
    
    'notifications' => [
        'enabled' => env('UPDATER_NOTIFICATIONS_ENABLED', false),
        'email' => env('UPDATER_NOTIFICATION_EMAIL'),
        'slack_webhook' => env('UPDATER_SLACK_WEBHOOK'),
    ],
    
    'security' => [
        'require_authentication' => env('UPDATER_REQUIRE_AUTH', true),
        'allowed_users' => [
            // Add user IDs or emails that can perform updates
            // 1,
            // 'admin@example.com',
        ],
    ],
];

