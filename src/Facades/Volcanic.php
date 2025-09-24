<?php

declare(strict_types=1);

namespace Volcanic\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Volcanic\Volcanic
 */
class Volcanic extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Volcanic\Volcanic::class;
    }
}
