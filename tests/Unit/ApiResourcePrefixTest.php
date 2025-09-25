<?php

declare(strict_types=1);

namespace Volcanic\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Volcanic\Attributes\ApiResource;

class ApiResourcePrefixTest extends TestCase
{
    public function test_default_prefix_is_api(): void
    {
        $apiResource = new ApiResource;

        $this->assertEquals('api', $apiResource->getPrefix());
    }

    public function test_custom_prefix_gets_api_prepended(): void
    {
        $apiResource = new ApiResource(prefix: 'v1');

        $this->assertEquals('api/v1', $apiResource->getPrefix());
    }

    public function test_prefix_with_api_slash_is_preserved(): void
    {
        $apiResource = new ApiResource(prefix: 'api/v1');

        $this->assertEquals('api/v1', $apiResource->getPrefix());
    }

    public function test_explicit_api_prefix_returns_api(): void
    {
        $apiResource = new ApiResource(prefix: 'api');

        $this->assertEquals('api', $apiResource->getPrefix());
    }

    public function test_complex_prefix_gets_api_prepended(): void
    {
        $apiResource = new ApiResource(prefix: 'v2/admin');

        $this->assertEquals('api/v2/admin', $apiResource->getPrefix());
    }

    public function test_prefix_with_leading_slash_is_handled(): void
    {
        $apiResource = new ApiResource(prefix: '/v1');

        $this->assertEquals('api/v1', $apiResource->getPrefix());
    }
}
