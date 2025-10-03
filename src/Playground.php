<?php

declare(strict_types=1);

namespace Volcanic;

use Closure;

class Playground
{
    protected static ?Closure $accessCallback = null;

    /**
     * Determine if the playground can be accessed.
     */
    public static function canAccess(bool|Closure $callback): void
    {
        self::$accessCallback = is_bool($callback) ? fn (): bool => $callback : $callback;
    }

    /**
     * Check if playground access is allowed.
     */
    public static function check(): bool
    {
        if (! self::$accessCallback instanceof Closure) {
            // Default: enabled in local/development environment
            return app()->environment(['local', 'development']);
        }

        return (bool) call_user_func(self::$accessCallback);
    }

    /**
     * Reset the access callback (useful for testing).
     */
    public static function reset(): void
    {
        self::$accessCallback = null;
    }
}
