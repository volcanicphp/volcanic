<?php

declare(strict_types=1);

namespace Volcanic\Tests\Unit;

use Volcanic\Services\ApiDiscoveryService;
use Volcanic\Tests\TestCase;

class ApiDiscoveryServiceTest extends TestCase
{
    protected ApiDiscoveryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ApiDiscoveryService;
    }

    public function test_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(ApiDiscoveryService::class, $this->service);
    }

    public function test_discover_models_returns_empty_array_when_no_models_exist(): void
    {
        $models = $this->service->discoverModelsWithApiAttribute();
        $this->assertIsArray($models);
    }
}
