<?php

declare(strict_types=1);

namespace Volcanic;

use Volcanic\Services\ApiResourceDiscoveryService;

class Volcanic
{
    public function __construct(
        protected ApiResourceDiscoveryService $discoveryService
    ) {}

    /**
     * Discover and register API routes for models with the API attribute.
     */
    public function discoverApiRoutes(): void
    {
        $this->discoveryService->discoverAndRegisterRoutes();
    }

    /**
     * Register routes for a specific model class.
     */
    public function registerModelRoutes(string $modelClass): void
    {
        $this->discoveryService->registerModelRoutes($modelClass);
    }

    /**
     * Get all models that have the API attribute.
     */
    public function getApiModels(): array
    {
        return $this->discoveryService->discoverModelsWithApiAttribute();
    }
}
