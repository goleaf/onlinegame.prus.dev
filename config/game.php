<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Game Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all the game-specific configuration options.
    |
    */

    'name' => env('GAME_NAME', 'Online Strategy Game'),
    'version' => env('GAME_VERSION', '1.0.0'),
    
    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'query_threshold' => env('GAME_QUERY_THRESHOLD', 1.0), // seconds
        'response_threshold' => env('GAME_RESPONSE_THRESHOLD', 2.0), // seconds
        'memory_limit' => env('GAME_MEMORY_LIMIT', 256), // MB
        'cache_duration' => env('GAME_CACHE_DURATION', 3600), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Game Rules
    |--------------------------------------------------------------------------
    */
    'rules' => [
        'max_villages_per_player' => env('GAME_MAX_VILLAGES', 10),
        'max_alliance_members' => env('GAME_MAX_ALLIANCE_MEMBERS', 50),
        'battle_cooldown' => env('GAME_BATTLE_COOLDOWN', 300), // seconds
        'movement_speed_multiplier' => env('GAME_MOVEMENT_SPEED', 1.0),
        'resource_production_rate' => env('GAME_RESOURCE_RATE', 1.0),
        'building_time_multiplier' => env('GAME_BUILDING_TIME', 1.0),
        'research_time_multiplier' => env('GAME_RESEARCH_TIME', 1.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Settings
    |--------------------------------------------------------------------------
    */
    'resources' => [
        'starting_wood' => env('GAME_STARTING_WOOD', 1000),
        'starting_clay' => env('GAME_STARTING_CLAY', 1000),
        'starting_iron' => env('GAME_STARTING_IRON', 1000),
        'starting_crop' => env('GAME_STARTING_CROP', 1000),
        'storage_capacity_base' => env('GAME_STORAGE_CAPACITY', 10000),
        'production_intervals' => [
            'wood' => env('GAME_WOOD_INTERVAL', 3600), // seconds
            'clay' => env('GAME_CLAY_INTERVAL', 3600),
            'iron' => env('GAME_IRON_INTERVAL', 3600),
            'crop' => env('GAME_CROP_INTERVAL', 3600),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Battle Settings
    |--------------------------------------------------------------------------
    */
    'battle' => [
        'max_attack_distance' => env('GAME_MAX_ATTACK_DISTANCE', 50), // km
        'morale_bonus' => env('GAME_MORALE_BONUS', 0.1), // 10% bonus for defending
        'loyalty_decrease' => env('GAME_LOYALTY_DECREASE', 10), // loyalty points lost per attack
        'loyalty_minimum' => env('GAME_LOYALTY_MINIMUM', 20),
        'loyalty_maximum' => env('GAME_LOYALTY_MAXIMUM', 100),
        'loyalty_recovery_rate' => env('GAME_LOYALTY_RECOVERY', 1), // points per hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Unit Settings
    |--------------------------------------------------------------------------
    */
    'units' => [
        'infantry' => [
            'attack' => 10,
            'defense' => 15,
            'speed' => 5, // km/h
            'cost' => ['wood' => 100, 'clay' => 50, 'iron' => 25, 'crop' => 50],
            'training_time' => 300, // seconds
        ],
        'archer' => [
            'attack' => 15,
            'defense' => 10,
            'speed' => 4,
            'cost' => ['wood' => 50, 'clay' => 100, 'iron' => 75, 'crop' => 25],
            'training_time' => 450,
        ],
        'cavalry' => [
            'attack' => 20,
            'defense' => 5,
            'speed' => 15,
            'cost' => ['wood' => 25, 'clay' => 75, 'iron' => 100, 'crop' => 100],
            'training_time' => 600,
        ],
        'siege' => [
            'attack' => 50,
            'defense' => 20,
            'speed' => 2,
            'cost' => ['wood' => 200, 'clay' => 150, 'iron' => 300, 'crop' => 50],
            'training_time' => 900,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Building Settings
    |--------------------------------------------------------------------------
    */
    'buildings' => [
        'max_level' => env('GAME_BUILDING_MAX_LEVEL', 20),
        'upgrade_cost_multiplier' => env('GAME_BUILDING_COST_MULTIPLIER', 1.5),
        'upgrade_time_multiplier' => env('GAME_BUILDING_TIME_MULTIPLIER', 1.3),
        'destruction_refund' => env('GAME_BUILDING_REFUND', 0.5), // 50% refund
    ],

    /*
    |--------------------------------------------------------------------------
    | Map Settings
    |--------------------------------------------------------------------------
    */
    'map' => [
        'size' => [
            'width' => env('GAME_MAP_WIDTH', 1000), // km
            'height' => env('GAME_MAP_HEIGHT', 1000), // km
        ],
        'village_density' => env('GAME_VILLAGE_DENSITY', 0.1), // villages per km²
        'oasis_bonus' => env('GAME_OASIS_BONUS', 0.5), // 50% resource bonus
        'nature_bonus' => env('GAME_NATURE_BONUS', 0.25), // 25% resource bonus
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Settings
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'emails' => explode(',', env('GAME_ADMIN_EMAILS', 'admin@example.com')),
        'backup_retention' => env('GAME_BACKUP_RETENTION', 30), // days
        'cleanup_intervals' => [
            'battles' => env('GAME_CLEANUP_BATTLES', 30), // days
            'movements' => env('GAME_CLEANUP_MOVEMENTS', 7), // days
            'logs' => env('GAME_CLEANUP_LOGS', 14), // days
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'alliances' => env('GAME_FEATURE_ALLIANCES', true),
        'trading' => env('GAME_FEATURE_TRADING', true),
        'heroes' => env('GAME_FEATURE_HEROES', true),
        'quests' => env('GAME_FEATURE_QUESTS', true),
        'achievements' => env('GAME_FEATURE_ACHIEVEMENTS', true),
        'chat' => env('GAME_FEATURE_CHAT', true),
        'reports' => env('GAME_FEATURE_REPORTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        'max_login_attempts' => env('GAME_MAX_LOGIN_ATTEMPTS', 5),
        'login_cooldown' => env('GAME_LOGIN_COOLDOWN', 300), // seconds
        'session_timeout' => env('GAME_SESSION_TIMEOUT', 3600), // seconds
        'rate_limiting' => [
            'battle_requests' => env('GAME_RATE_LIMIT_BATTLES', 10), // per minute
            'building_requests' => env('GAME_RATE_LIMIT_BUILDINGS', 5), // per minute
            'movement_requests' => env('GAME_RATE_LIMIT_MOVEMENTS', 20), // per minute
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'player_data' => env('GAME_CACHE_PLAYER_DATA', 300), // seconds
        'village_data' => env('GAME_CACHE_VILLAGE_DATA', 60), // seconds
        'map_data' => env('GAME_CACHE_MAP_DATA', 1800), // seconds
        'alliance_data' => env('GAME_CACHE_ALLIANCE_DATA', 600), // seconds
        'statistics' => env('GAME_CACHE_STATISTICS', 3600), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('GAME_LOGGING_ENABLED', true),
        'channels' => [
            'game_actions' => env('GAME_LOG_ACTIONS', true),
            'performance' => env('GAME_LOG_PERFORMANCE', true),
            'errors' => env('GAME_LOG_ERRORS', true),
            'security' => env('GAME_LOG_SECURITY', true),
        ],
        'retention' => env('GAME_LOG_RETENTION', 30), // days
    ],
];

