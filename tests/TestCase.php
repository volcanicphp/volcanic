<?php

declare(strict_types=1);

namespace Volcanic\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Override;
use Volcanic\VolcanicServiceProvider;

class TestCase extends Orchestra
{
    #[Override]
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

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
    }
}
