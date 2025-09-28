<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Social Media Integration Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for social media integrations
    | including Twitter, Facebook, Discord, and Telegram.
    |
    */

    'twitter' => [
        'enabled' => env('TWITTER_ENABLED', false),
        'bearer_token' => env('TWITTER_BEARER_TOKEN'),
        'api_key' => env('TWITTER_API_KEY'),
        'api_secret' => env('TWITTER_API_SECRET'),
        'access_token' => env('TWITTER_ACCESS_TOKEN'),
        'access_token_secret' => env('TWITTER_ACCESS_TOKEN_SECRET'),
        'user_id' => env('TWITTER_USER_ID'),
    ],

    'facebook' => [
        'enabled' => env('FACEBOOK_ENABLED', false),
        'app_id' => env('FACEBOOK_APP_ID'),
        'app_secret' => env('FACEBOOK_APP_SECRET'),
        'access_token' => env('FACEBOOK_ACCESS_TOKEN'),
        'page_id' => env('FACEBOOK_PAGE_ID'),
        'verify_token' => env('FACEBOOK_VERIFY_TOKEN'),
    ],

    'discord' => [
        'enabled' => env('DISCORD_ENABLED', false),
        'webhook_url' => env('DISCORD_WEBHOOK_URL'),
        'bot_token' => env('DISCORD_BOT_TOKEN'),
        'channel_id' => env('DISCORD_CHANNEL_ID'),
        'guild_id' => env('DISCORD_GUILD_ID'),
    ],

    'telegram' => [
        'enabled' => env('TELEGRAM_ENABLED', false),
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'channel_id' => env('TELEGRAM_CHANNEL_ID'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    'instagram' => [
        'enabled' => env('INSTAGRAM_ENABLED', false),
        'access_token' => env('INSTAGRAM_ACCESS_TOKEN'),
        'business_account_id' => env('INSTAGRAM_BUSINESS_ACCOUNT_ID'),
        'app_id' => env('INSTAGRAM_APP_ID'),
        'app_secret' => env('INSTAGRAM_APP_SECRET'),
    ],

    'linkedin' => [
        'enabled' => env('LINKEDIN_ENABLED', false),
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'access_token' => env('LINKEDIN_ACCESS_TOKEN'),
        'company_id' => env('LINKEDIN_COMPANY_ID'),
    ],

    'youtube' => [
        'enabled' => env('YOUTUBE_ENABLED', false),
        'api_key' => env('YOUTUBE_API_KEY'),
        'channel_id' => env('YOUTUBE_CHANNEL_ID'),
        'client_id' => env('YOUTUBE_CLIENT_ID'),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
        'refresh_token' => env('YOUTUBE_REFRESH_TOKEN'),
    ],

    'tiktok' => [
        'enabled' => env('TIKTOK_ENABLED', false),
        'access_token' => env('TIKTOK_ACCESS_TOKEN'),
        'open_id' => env('TIKTOK_OPEN_ID'),
        'client_key' => env('TIKTOK_CLIENT_KEY'),
        'client_secret' => env('TIKTOK_CLIENT_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Default settings for social media sharing
    |
    */

    'defaults' => [
        'auto_share_achievements' => env('AUTO_SHARE_ACHIEVEMENTS', false),
        'auto_share_battles' => env('AUTO_SHARE_BATTLES', false),
        'auto_share_milestones' => env('AUTO_SHARE_MILESTONES', false),
        'require_player_consent' => env('REQUIRE_PLAYER_CONSENT', true),
        'share_cooldown_minutes' => env('SHARE_COOLDOWN_MINUTES', 60),
        'max_shares_per_day' => env('MAX_SHARES_PER_DAY', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Share Templates
    |--------------------------------------------------------------------------
    |
    | Default templates for different types of content
    |
    */

    'templates' => [
        'achievement' => [
            'text' => "🎉 I just unlocked the achievement '{achievement_name}' in the game! {achievement_description}",
            'hashtags' => ['#GameAchievement', '#Gaming', '#OnlineGame'],
            'platforms' => ['twitter', 'facebook', 'discord'],
        ],
        'battle_victory' => [
            'text' => '⚔️ Victory! I won a battle against {enemy_name} and captured {resources_captured} resources!',
            'hashtags' => ['#BattleVictory', '#Gaming', '#OnlineGame', '#Strategy'],
            'platforms' => ['twitter', 'facebook', 'discord'],
        ],
        'village_milestone' => [
            'text' => "🏘️ My village '{village_name}' has reached {milestone}! Population: {population}",
            'hashtags' => ['#VillageGrowth', '#Gaming', '#OnlineGame', '#Strategy'],
            'platforms' => ['twitter', 'facebook', 'discord'],
        ],
        'alliance_war' => [
            'text' => "🤝 My alliance '{alliance_name}' has declared war on {enemy_alliance}! Join the battle!",
            'hashtags' => ['#AllianceWar', '#Gaming', '#OnlineGame', '#Strategy'],
            'platforms' => ['twitter', 'facebook', 'discord', 'telegram'],
        ],
        'wonder_construction' => [
            'text' => '🏛️ Our alliance is constructing the {wonder_name} wonder! Join us in this epic project!',
            'hashtags' => ['#WonderConstruction', '#Alliance', '#Gaming', '#Strategy'],
            'platforms' => ['twitter', 'facebook', 'discord', 'telegram'],
        ],
        'quest_completion' => [
            'text' => "📜 Quest completed! I finished '{quest_name}' and received {reward}!",
            'hashtags' => ['#QuestComplete', '#Gaming', '#OnlineGame', '#Rewards'],
            'platforms' => ['twitter', 'facebook', 'discord'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting settings for social media API calls
    |
    */

    'rate_limiting' => [
        'twitter' => [
            'requests_per_minute' => 300,
            'requests_per_hour' => 15000,
            'requests_per_day' => 300000,
        ],
        'facebook' => [
            'requests_per_minute' => 200,
            'requests_per_hour' => 4800,
            'requests_per_day' => 100000,
        ],
        'discord' => [
            'requests_per_minute' => 50,
            'requests_per_hour' => 1000,
            'requests_per_day' => 10000,
        ],
        'telegram' => [
            'requests_per_minute' => 30,
            'requests_per_hour' => 720,
            'requests_per_day' => 20000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Error handling settings for social media integrations
    |
    */

    'error_handling' => [
        'retry_attempts' => 3,
        'retry_delay_seconds' => 5,
        'log_errors' => true,
        'notify_admins_on_failure' => false,
        'admin_email' => env('SOCIAL_MEDIA_ADMIN_EMAIL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    |
    | Analytics settings for tracking social media performance
    |
    */

    'analytics' => [
        'enabled' => env('SOCIAL_MEDIA_ANALYTICS_ENABLED', true),
        'track_engagement' => true,
        'track_reach' => true,
        'track_clicks' => true,
        'cache_analytics_duration' => 3600, // 1 hour in seconds
    ],
];
