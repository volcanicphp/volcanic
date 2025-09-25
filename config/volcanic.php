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
    | Auto-Discover Controller Routes
    |--------------------------------------------------------------------------
    |
    | When enabled, Volcanic will automatically discover and register routes
    | for controller methods that have the ApiRoute attribute. Set to false if
    | you prefer to manually register routes.
    |
    */
    'auto_discover_controller_routes' => true,

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
    | Model Paths
    |--------------------------------------------------------------------------
    |
    | Paths to scan for models with the API attribute.
    |
    */
    'model_paths' => [
        app_path('Models'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Controller Paths
    |--------------------------------------------------------------------------
    |
    | Paths to scan for controllers with ApiRoute attributes on methods.
    | If empty, will default to app_path('Http/Controllers').
    |
    */
    'controller_paths' => [
        app_path('Http/Controllers'),
    ],

];
