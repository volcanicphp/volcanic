<?php

declare(strict_types=1);

use Volcanic\Services\ApiResourceDiscoveryService;

test('service can be instantiated', function (): void {
    $service = new ApiResourceDiscoveryService;

    expect($service)->toBeInstanceOf(ApiResourceDiscoveryService::class);
});

test('discover models returns empty array when no models exist', function (): void {
    $service = new ApiResourceDiscoveryService;
    $models = $service->discoverModelsWithApiAttribute();

    expect($models)->toBeArray();
});
