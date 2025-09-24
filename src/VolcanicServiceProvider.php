<?php

declare(strict_types=1);

namespace Volcanic;

use Illuminate\Foundation\Application;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Volcanic\Commands\VolcanicCommand;
use Volcanic\Services\ApiDiscoveryService;
use Volcanic\Services\ApiQueryService;

class VolcanicServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('volcanic')
            ->hasConfigFile()
            ->hasCommand(VolcanicCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ApiDiscoveryService::class);
        $this->app->singleton(ApiQueryService::class);

        $this->app->singleton(Volcanic::class, fn (Application $app): Volcanic => new Volcanic(
            $app->make(ApiDiscoveryService::class)
        ));
    }

    public function packageBooted(): void
    {
        if (config('volcanic.auto_discover_routes', true)) {
            $volcanic = $this->app->make(Volcanic::class);
            $volcanic->discoverApiRoutes();
        }
    }
}
