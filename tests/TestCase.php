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

    /**
     * {@inheritDoc}
     */
    protected function getPackageProviders(mixed $app)
    {
        return [
            VolcanicServiceProvider::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getEnvironmentSetUp(mixed $app): void
    {
        config()->set('database.default', 'testing');
        config()->set('app.env', 'testing');
    }
}
