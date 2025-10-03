<?php

declare(strict_types=1);

namespace Volcanic;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Volcanic\Commands\VolcanicCommand;
use Volcanic\Http\Controllers\PlaygroundController;
use Volcanic\Http\Controllers\PlaygroundSchemaController;
use Volcanic\Services\ApiQueryService;
use Volcanic\Services\ApiResourceDiscoveryService;
use Volcanic\Services\ApiRouteDiscoveryService;
use Volcanic\Services\SchemaService;

class VolcanicServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('volcanic')
            ->hasConfigFile()
            ->hasViews()
            ->hasAssets()
            ->hasCommand(VolcanicCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ApiResourceDiscoveryService::class);
        $this->app->singleton(ApiQueryService::class);
        $this->app->singleton(ApiRouteDiscoveryService::class);
        $this->app->singleton(SchemaService::class);
        $this->app->singleton(Playground::class);

        $this->app->singleton(Volcanic::class, fn (Application $app): Volcanic => new Volcanic(
            $app->make(ApiResourceDiscoveryService::class)
        ));
    }

    public function packageBooted(): void
    {
        // Register playground routes
        $this->registerPlaygroundRoutes();

        if (config('volcanic.auto_discover_controller_routes', true)) {
            // Discover and register controller method-based routes
            $routeDiscovery = $this->app->make(ApiRouteDiscoveryService::class);
            $controllerPaths = config('volcanic.controller_paths', []);
            $routeDiscovery->discoverAndRegisterRoutes($controllerPaths);
        }

        if (config('volcanic.auto_discover_routes', true)) {
            // Discover and register model-based API routes
            $volcanic = $this->app->make(Volcanic::class);
            $volcanic->discoverApiRoutes();
        }
    }

    /**
     * Register the playground routes.
     */
    protected function registerPlaygroundRoutes(): void
    {
        Route::prefix('volcanic/playground')
            ->group(function (): void {
                Route::get('/', PlaygroundController::class)->name('volcanic.playground');
                Route::get('/schema', PlaygroundSchemaController::class)->name('volcanic.playground.schema');
            });
    }
}
