<?php

declare(strict_types=1);

namespace Volcanic\Tests\Unit;

use Override;
use Volcanic\Services\ApiResourceDiscoveryService;
use Volcanic\Tests\TestCase;

class ApiResourceDiscoveryServiceTest extends TestCase
{
    protected ApiResourceDiscoveryService $service;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ApiResourceDiscoveryService;
    }

    public function test_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ApiResourceDiscoveryService::class, $this->service);
    }

    public function test_discover_models_returns_empty_array_when_no_models_exist(): void
    {
        $models = $this->service->discoverModelsWithApiAttribute();
        $this->assertIsArray($models);
    }
}
