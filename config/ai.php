<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AI services including OpenAI and Gemini providers
    | from the Larautilx package.
    |
    */

    'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),
    'default_model' => env('AI_DEFAULT_MODEL', 'gpt-3.5-turbo'),
    'cache_duration' => env('AI_CACHE_DURATION', 3600), // 1 hour

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'max_retries' => env('OPENAI_MAX_RETRIES', 3),
        'retry_delay' => env('OPENAI_RETRY_DELAY', 2),
        'models' => [
            'gpt-3.5-turbo',
            'gpt-4',
            'gpt-4-turbo',
        ],
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'max_retries' => env('GEMINI_MAX_RETRIES', 3),
        'retry_delay' => env('GEMINI_RETRY_DELAY', 2),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'models' => [
            'gemini-pro',
            'gemini-pro-vision',
        ],
    ],

    'game_features' => [
        'village_name_generation' => env('AI_VILLAGE_NAMES', true),
        'alliance_name_generation' => env('AI_ALLIANCE_NAMES', true),
        'quest_descriptions' => env('AI_QUEST_DESCRIPTIONS', true),
        'battle_reports' => env('AI_BATTLE_REPORTS', true),
        'player_messages' => env('AI_PLAYER_MESSAGES', true),
        'world_events' => env('AI_WORLD_EVENTS', true),
        'strategy_suggestions' => env('AI_STRATEGY_SUGGESTIONS', true),
    ],

    'rate_limiting' => [
        'enabled' => env('AI_RATE_LIMITING', true),
        'max_requests_per_minute' => env('AI_MAX_REQUESTS_PER_MINUTE', 60),
        'max_requests_per_hour' => env('AI_MAX_REQUESTS_PER_HOUR', 1000),
    ],

    'logging' => [
        'enabled' => env('AI_LOGGING', true),
        'log_requests' => env('AI_LOG_REQUESTS', true),
        'log_responses' => env('AI_LOG_RESPONSES', false),
        'log_errors' => env('AI_LOG_ERRORS', true),
    ],
];

