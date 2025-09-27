<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    /*
     * Your API path. By default, all routes starting with this path will be added to the docs.
     * If you need to change this behavior, you can add your custom routes resolver using `Scramble::routes()`.
     */
    'api_path' => 'api',

    /*
     * Your API domain. By default, app domain is used. This is also a part of the default API routes
     * matcher, so when implementing your own, make sure you use this config if needed.
     */
    'api_domain' => null,

    /*
     * The path where your OpenAPI specification will be exported.
     */
    'export_path' => 'api.json',

    'info' => [
        /*
         * API version.
         */
        'version' => env('API_VERSION', '1.0.0'),

        /*
         * Description rendered on the home page of the API documentation (`/docs/api`).
         */
        'description' => 'Online Game API - A comprehensive REST API for managing game players, villages, and game mechanics. This API provides complete functionality for game management including:

## Core Features
- **Player Management**: Complete CRUD operations for game players
- **Village Management**: Village creation, updates, and resource management  
- **Building System**: Building upgrades and construction management
- **AI Integration**: AI-powered content generation for names, descriptions, and strategies
- **System Management**: Health monitoring, configuration, and cache management
- **Larautilx Integration**: Advanced utilities for caching, filtering, and pagination
- **Artifact System**: Powerful items with server-wide effects and bonuses
- **Authentication**: Secure API access with Laravel Sanctum tokens

## API Endpoints
- **88+ Endpoints** across 9 main categories
- **RESTful Design** following industry best practices
- **Comprehensive Documentation** with examples and error handling
- **Real-time Capabilities** for game updates and notifications

All endpoints require authentication via Bearer token.',
        
        /*
         * Contact information
         */
        'contact' => [
            'name' => 'Game API Support',
            'email' => 'api@game.example.com',
            'url' => 'https://game.example.com/support',
        ],
        
        /*
         * License information
         */
        'license' => [
            'name' => 'MIT',
            'url' => 'https://opensource.org/licenses/MIT',
        ],
        
        /*
         * Terms of service
         */
        'terms_of_service' => 'https://game.example.com/terms',
    ],

    /*
     * Customize Stoplight Elements UI
     */
    'ui' => [
        /*
         * Define the title of the documentation's website. App name is used when this config is `null`.
         */
        'title' => 'Online Game API Documentation',

        /*
         * Define the theme of the documentation. Available options are `light`, `dark`, and `system`.
         */
        'theme' => 'dark',

        /*
         * Hide the `Try It` feature. Enabled by default.
         */
        'hide_try_it' => false,

        /*
         * Hide the schemas in the Table of Contents. Enabled by default.
         */
        'hide_schemas' => false,

        /*
         * URL to an image that displays as a small square logo next to the title, above the table of contents.
         */
        'logo' => '',

        /*
         * Use to fetch the credential policy for the Try It feature. Options are: omit, include (default), and same-origin
         */
        'try_it_credentials_policy' => 'include',

        /*
         * There are three layouts for Elements:
         * - sidebar - (Elements default) Three-column design with a sidebar that can be resized.
         * - responsive - Like sidebar, except at small screen sizes it collapses the sidebar into a drawer that can be toggled open.
         * - stacked - Everything in a single column, making integrations with existing websites that have their own sidebar or other columns already.
         */
        'layout' => 'responsive',
    ],

    /*
     * The list of servers of the API. By default, when `null`, server URL will be created from
     * `scramble.api_path` and `scramble.api_domain` config variables. When providing an array, you
     * will need to specify the local server URL manually (if needed).
     *
     * Example of non-default config (final URLs are generated using Laravel `url` helper):
     *
     * ```php
     * 'servers' => [
     *     'Live' => 'api',
     *     'Prod' => 'https://scramble.dedoc.co/api',
     * ],
     * ```
     */
    'servers' => null,

    /**
     * Determines how Scramble stores the descriptions of enum cases.
     * Available options:
     * - 'description' – Case descriptions are stored as the enum schema's description using table formatting.
     * - 'extension' – Case descriptions are stored in the `x-enumDescriptions` enum schema extension.
     *
     *    @see https://redocly.com/docs-legacy/api-reference-docs/specification-extensions/x-enum-descriptions
     * - false - Case descriptions are ignored.
     */
    'enum_cases_description_strategy' => 'description',

    'middleware' => [
        'web',
        // RestrictedDocsAccess::class, // Commented out for development access
    ],

    'extensions' => [],
];
