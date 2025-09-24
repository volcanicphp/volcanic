<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Auto-Discover Routes
    |--------------------------------------------------------------------------
    |
    | When enabled, Volcanic will automatically discover and register routes
    | for models that have the API attribute. Set to false if you prefer
    | to manually register routes.
    |
    */
    'auto_discover_routes' => true,

    /*
    |--------------------------------------------------------------------------
    | Default API Prefix
    |--------------------------------------------------------------------------
    |
    | The default prefix to use for API routes when not specified in the
    | API attribute.
    |
    */
    'default_api_prefix' => 'api',

    /*
    |--------------------------------------------------------------------------
    | Default Pagination
    |--------------------------------------------------------------------------
    |
    | Default pagination settings for API endpoints.
    |
    */
    'default_per_page' => 15,

    /*
    |--------------------------------------------------------------------------
    | Global Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware that should be applied to all automatically discovered
    | API routes.
    |
    */
    'global_middleware' => [
        // 'auth:sanctum',
        // 'throttle:api',
    ],
];
