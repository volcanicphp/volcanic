<?php

declare(strict_types=1);

use Volcanic\Attributes\ApiRoute;

test('default prefix is api', function (): void {
    $apiRoute = new ApiRoute;

    expect($apiRoute->getPrefix())->toBe('api');
});

test('custom prefix gets api prepended', function (): void {
    $apiRoute = new ApiRoute(prefix: 'v1');

    expect($apiRoute->getPrefix())->toBe('api/v1');
});

test('prefix with api slash is preserved', function (): void {
    $apiRoute = new ApiRoute(prefix: 'api/v1');

    expect($apiRoute->getPrefix())->toBe('api/v1');
});

test('explicit api prefix returns api', function (): void {
    $apiRoute = new ApiRoute(prefix: 'api');

    expect($apiRoute->getPrefix())->toBe('api');
});

test('complex prefix gets api prepended', function (): void {
    $apiRoute = new ApiRoute(prefix: 'v2/admin');

    expect($apiRoute->getPrefix())->toBe('api/v2/admin');
});

test('prefix with leading slash is handled', function (): void {
    $apiRoute = new ApiRoute(prefix: '/v1');

    expect($apiRoute->getPrefix())->toBe('api/v1');
});
