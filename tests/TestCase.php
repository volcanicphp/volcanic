<?php

declare(strict_types=1);

namespace Volcanic\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Volcanic\VolcanicServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            VolcanicServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }
}
