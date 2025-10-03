<?php

declare(strict_types=1);

namespace Examples;

use Illuminate\Support\ServiceProvider;
use Volcanic\Facades\Playground;

/**
 * Example: Configuring Playground Access Control
 *
 * This example shows different ways to configure access to the API Playground.
 */
class PlaygroundConfigurationExamples extends ServiceProvider
{
    /**
     * Example 1: Enable playground in all environments (NOT RECOMMENDED for production).
     */
    public function example1EnableEverywhere(): void
    {
        Playground::canAccess(true);
    }

    /**
     * Example 2: Disable playground completely.
     */
    public function example2DisableCompletely(): void
    {
        Playground::canAccess(false);
    }

    /**
     * Example 3: Enable for authenticated admin users only.
     */
    public function example3AdminOnly(): void
    {
        Playground::canAccess(function (): bool {
            return auth()->check() && auth()->user()->isAdmin();
        });
    }

    /**
     * Example 4: Enable for specific environments and authenticated users.
     */
    public function example4EnvironmentAndAuth(): void
    {
        Playground::canAccess(function (): bool {
            // Only in local/staging AND user must be authenticated
            if (! app()->environment(['local', 'staging'])) {
                return false;
            }

            return auth()->check();
        });
    }

    /**
     * Example 5: Enable for specific IP addresses (useful for internal tools).
     */
    public function example5IpWhitelist(): void
    {
        Playground::canAccess(function (): bool {
            $allowedIps = [
                '127.0.0.1',
                '192.168.1.100',
                // Add your office IP addresses
            ];

            return in_array(request()->ip(), $allowedIps, true);
        });
    }

    /**
     * Example 6: Environment-based with fallback to config.
     */
    public function example6ConfigBased(): void
    {
        Playground::canAccess(function (): bool {
            // Check if explicitly enabled in config
            if (config('volcanic.playground.enabled') === false) {
                return false;
            }

            // Default to dev environments only
            return app()->environment(['local', 'development']);
        });
    }

    /**
     * Example 7: Role-based access control.
     */
    public function example7RoleBasedAccess(): void
    {
        Playground::canAccess(function (): bool {
            if (! auth()->check()) {
                return false;
            }

            // Check if user has 'developer' or 'admin' role
            return auth()->user()->hasAnyRole(['developer', 'admin']);
        });
    }

    /**
     * Example 8: Permission-based access (using Laravel's Gate).
     */
    public function example8PermissionBased(): void
    {
        Playground::canAccess(function (): bool {
            return auth()->check() && auth()->user()->can('access-api-playground');
        });
    }

    /**
     * Recommended configuration for most applications.
     * Put this in your AppServiceProvider::boot() method.
     */
    public function recommendedConfiguration(): void
    {
        Playground::canAccess(function (): bool {
            // Never enable in production unless explicitly configured
            if (app()->isProduction() && ! config('volcanic.playground.production_access', false)) {
                return false;
            }

            // In development, allow all access
            if (app()->environment(['local', 'development'])) {
                return true;
            }

            // In staging/other environments, require authentication
            return auth()->check() && auth()->user()->can('access-api-playground');
        });
    }
}

/**
 * Usage in AppServiceProvider:
 *
 * use Volcanic\Facades\Playground;
 *
 * public function boot(): void
 * {
 *     // Simple: Enable for all development environments
 *     if (app()->environment(['local', 'development'])) {
 *         Playground::canAccess(true);
 *     }
 *
 *     // Or: Use the recommended configuration
 *     Playground::canAccess(function (): bool {
 *         if (app()->isProduction()) {
 *             return false;
 *         }
 *
 *         return app()->environment(['local', 'development'])
 *             || (auth()->check() && auth()->user()->isAdmin());
 *     });
 * }
 */
