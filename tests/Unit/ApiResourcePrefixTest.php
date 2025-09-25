<?php

declare(strict_types=1);

use Volcanic\Attributes\ApiResource;

test('default prefix is api', function (): void {
    $apiResource = new ApiResource;

    expect($apiResource->getPrefix())->toBe('api');
});

test('custom prefix gets api prepended', function (): void {
    $apiResource = new ApiResource(prefix: 'v1');

    expect($apiResource->getPrefix())->toBe('api/v1');
});

test('prefix with api slash is preserved', function (): void {
    $apiResource = new ApiResource(prefix: 'api/v1');

    expect($apiResource->getPrefix())->toBe('api/v1');
});

test('explicit api prefix returns api', function (): void {
    $apiResource = new ApiResource(prefix: 'api');

    expect($apiResource->getPrefix())->toBe('api');
});

test('complex prefix gets api prepended', function (): void {
    $apiResource = new ApiResource(prefix: 'v2/admin');

    expect($apiResource->getPrefix())->toBe('api/v2/admin');
});

test('prefix with leading slash is handled', function (): void {
    $apiResource = new ApiResource(prefix: '/v1');

    expect($apiResource->getPrefix())->toBe('api/v1');
});
