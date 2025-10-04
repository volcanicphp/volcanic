<?php

declare(strict_types=1);

use Volcanic\Enums\PaginationType;

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
    | Maximum Per Page Limit
    |--------------------------------------------------------------------------
    |
    | The maximum number of items that can be requested per page via the
    | ?per_page query parameter. This prevents excessive memory usage and
    | protects against performance issues from overly large page sizes.
    |
    */
    'max_per_page' => 100,

    /*
    |--------------------------------------------------------------------------
    | Default Pagination Type
    |--------------------------------------------------------------------------
    |
    | The default pagination type to use for API endpoints when not specified.
    | Uses the PaginationType enum for type safety.
    |
    */
    'default_pagination_type' => PaginationType::LENGTH_AWARE,

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

    /*
    |--------------------------------------------------------------------------
    | API Playground
    |--------------------------------------------------------------------------
    |
    | Enable or disable the API playground. By default, it's enabled in local
    | and development environments. You can customize access control using
    | Playground::canAccess() in your AppServiceProvider.
    |
    | You can customize the playground and schema URIs to any endpoint you prefer.
    |
    */
    'playground' => [
        'enabled' => env('VOLCANIC_PLAYGROUND_ENABLED', true),
        'uri' => env('VOLCANIC_PLAYGROUND_URI', 'volcanic/playground'),
    ],

];
