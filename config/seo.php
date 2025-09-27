<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SEO Configuration
    |--------------------------------------------------------------------------
    |
    | This option controls the default SEO configuration for your application.
    | You can customize these settings to match your site's requirements.
    |
    */

    'default_title' => 'Travian Online Game - Laravel Edition',
    'default_description' => 'Play the legendary browser-based strategy MMO Travian built with Laravel 12 and Livewire 3. Build villages, manage resources, and conquer the ancient world.',
    'default_image' => '/img/travian/game-logo.png',
    'default_keywords' => 'travian, strategy game, mmo, browser game, laravel, livewire, ancient world, village building, resource management',

    /*
    |--------------------------------------------------------------------------
    | Site Configuration
    |--------------------------------------------------------------------------
    |
    | Basic site information for SEO metadata.
    |
    */

    'site_name' => 'Travian Game',
    'site_url' => env('APP_URL', 'https://onlinegame.prus.dev'),
    'site_logo' => '/img/travian/game-logo.png',

    /*
    |--------------------------------------------------------------------------
    | Social Media Configuration
    |--------------------------------------------------------------------------
    |
    | Social media metadata configuration for better sharing.
    |
    */

    'twitter' => [
        'enabled' => true,
        'site' => '@TravianGame',
        'creator' => '@TravianGame',
        'card' => 'summary_large_image',
    ],

    'facebook' => [
        'enabled' => true,
        'app_id' => env('FACEBOOK_APP_ID'),
        'admins' => env('FACEBOOK_ADMINS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Open Graph Configuration
    |--------------------------------------------------------------------------
    |
    | Open Graph metadata configuration for better social media sharing.
    |
    */

    'open_graph' => [
        'enabled' => true,
        'type' => 'website',
        'locale' => 'en_US',
        'site_name' => 'Travian Game',
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON-LD Structured Data
    |--------------------------------------------------------------------------
    |
    | Configuration for JSON-LD structured data.
    |
    */

    'json_ld' => [
        'enabled' => true,
        'organization' => [
            '@type' => 'Organization',
            'name' => 'Travian Game',
            'url' => env('APP_URL', 'https://onlinegame.prus.dev'),
            'logo' => '/img/travian/game-logo.png',
            'description' => 'Browser-based strategy MMO set in ancient times',
        ],
        'website' => [
            '@type' => 'WebSite',
            'name' => 'Travian Game',
            'url' => env('APP_URL', 'https://onlinegame.prus.dev'),
            'description' => 'Play the legendary browser-based strategy MMO Travian',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => env('APP_URL', 'https://onlinegame.prus.dev') . '/search?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Robots Configuration
    |--------------------------------------------------------------------------
    |
    | Robots meta tag configuration.
    |
    */

    'robots' => [
        'index' => true,
        'follow' => true,
        'archive' => true,
        'snippet' => true,
        'imageindex' => true,
        'nocache' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Canonical URL Configuration
    |--------------------------------------------------------------------------
    |
    | Canonical URL configuration to prevent duplicate content issues.
    |
    */

    'canonical' => [
        'enabled' => true,
        'force_https' => true,
        'remove_trailing_slash' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Configuration
    |--------------------------------------------------------------------------
    |
    | Default image configuration for SEO metadata.
    |
    */

    'images' => [
        'default' => '/img/travian/game-logo.png',
        'width' => 1200,
        'height' => 630,
        'alt' => 'Travian Online Game',
    ],
];
