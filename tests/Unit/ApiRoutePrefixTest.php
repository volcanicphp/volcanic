<?php

declare(strict_types=1);

namespace Volcanic\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Volcanic\Attributes\ApiRoute;

class ApiRoutePrefixTest extends TestCase
{
    public function test_default_prefix_is_api(): void
    {
        $apiRoute = new ApiRoute;

        $this->assertEquals('api', $apiRoute->getPrefix());
    }

    public function test_custom_prefix_gets_api_prepended(): void
    {
        $apiRoute = new ApiRoute(prefix: 'v1');

        $this->assertEquals('api/v1', $apiRoute->getPrefix());
    }

    public function test_prefix_with_api_slash_is_preserved(): void
    {
        $apiRoute = new ApiRoute(prefix: 'api/v1');

        $this->assertEquals('api/v1', $apiRoute->getPrefix());
    }

    public function test_explicit_api_prefix_returns_api(): void
    {
        $apiRoute = new ApiRoute(prefix: 'api');

        $this->assertEquals('api', $apiRoute->getPrefix());
    }

    public function test_complex_prefix_gets_api_prepended(): void
    {
        $apiRoute = new ApiRoute(prefix: 'v2/admin');

        $this->assertEquals('api/v2/admin', $apiRoute->getPrefix());
    }

    public function test_prefix_with_leading_slash_is_handled(): void
    {
        $apiRoute = new ApiRoute(prefix: '/v1');

        $this->assertEquals('api/v1', $apiRoute->getPrefix());
    }
}
