<?php

declare(strict_types=1);

namespace Volcanic;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Volcanic\Commands\VolcanicCommand;

class VolcanicServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('volcanic')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_volcanic_table')
            ->hasCommand(VolcanicCommand::class);
    }
}
