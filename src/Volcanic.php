<?php

declare(strict_types=1);

namespace Volcanic;

use Volcanic\Services\ApiDiscoveryService;

class Volcanic
{
    public function __construct(
        protected ApiDiscoveryService $discoveryService
    ) {}

    /**
     * Discover and register API routes for models with the API attribute.
     */
    public function discoverApiRoutes(): void
    {
        $this->discoveryService->discoverAndRegisterRoutes();
    }

    /**
     * Get all models that have the API attribute.
     */
    public function getApiModels(): array
    {
        return $this->discoveryService->discoverModelsWithApiAttribute();
    }
}
