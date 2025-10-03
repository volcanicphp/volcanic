<?php

declare(strict_types=1);

namespace Volcanic\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void canAccess((bool|Closure) $callback)
 * @method static bool check()
 * @method static void reset()
 *
 * @see \Volcanic\Playground
 */
class Playground extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Volcanic\Playground::class;
    }
}
