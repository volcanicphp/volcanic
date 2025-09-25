<?php

declare(strict_types=1);

use Volcanic\Attributes\ApiRoute;

describe('ApiRoute Attribute', function (): void {
    it('can be instantiated with default parameters', function (): void {
        $route = new ApiRoute;

        expect($route->getMethods())->toBe(['GET']);
        expect($route->getUri())->toBeNull();
        expect($route->getName())->toBeNull();
        expect($route->getMiddleware())->toBe(['api']);
        expect($route->getWhereConstraints())->toBe([]);
        expect($route->getDomain())->toBeNull();
    });

    it('can be instantiated with custom parameters', function (): void {
        $route = new ApiRoute(
            uri: '/api/products/{id}',
            methods: ['POST', 'PUT'],
            middleware: ['auth:api', 'throttle:60,1'],
            where: ['id' => '[0-9]+'],
            domain: 'api.example.com',
            name: 'products.update'
        );

        expect($route->getMethods())->toBe(['POST', 'PUT']);
        expect($route->getUri())->toBe('/api/products/{id}');
        expect($route->getName())->toBe('products.update');
        expect($route->getMiddleware())->toBe(['api', 'auth:api', 'throttle:60,1']);
        expect($route->getWhereConstraints())->toBe(['id' => '[0-9]+']);
        expect($route->getDomain())->toBe('api.example.com');
    });

    it('can handle different HTTP methods', function (): void {
        $getRoute = new ApiRoute(methods: ['GET']);
        $postRoute = new ApiRoute(methods: ['POST']);
        $putPatchRoute = new ApiRoute(methods: ['PUT', 'PATCH']);
        $deleteRoute = new ApiRoute(methods: ['DELETE']);

        expect($getRoute->getMethods())->toBe(['GET']);
        expect($postRoute->getMethods())->toBe(['POST']);
        expect($putPatchRoute->getMethods())->toBe(['PUT', 'PATCH']);
        expect($deleteRoute->getMethods())->toBe(['DELETE']);
    });

    it('can handle empty middleware array', function (): void {
        $route = new ApiRoute(middleware: []);
        expect($route->getMiddleware())->toBe(['api']);
    });

    it('can handle single middleware', function (): void {
        $route = new ApiRoute(middleware: ['auth']);
        expect($route->getMiddleware())->toBe(['api', 'auth']);
    });

    it('can handle multiple middleware', function (): void {
        $route = new ApiRoute(middleware: ['auth:api', 'throttle:60,1', 'role:admin']);
        expect($route->getMiddleware())->toBe(['api', 'auth:api', 'throttle:60,1', 'role:admin']);
    });

    it('can handle where constraints', function (): void {
        $route = new ApiRoute(where: ['id' => '[0-9]+', 'slug' => '[a-zA-Z0-9-]+']);
        expect($route->getWhereConstraints())->toBe(['id' => '[0-9]+', 'slug' => '[a-zA-Z0-9-]+']);
    });

    it('can handle domain constraints', function (): void {
        $route = new ApiRoute(domain: 'api.example.com');
        expect($route->getDomain())->toBe('api.example.com');

        $subdomainRoute = new ApiRoute(domain: 'admin.{subdomain}.example.com');
        expect($subdomainRoute->getDomain())->toBe('admin.{subdomain}.example.com');
    });

    it('handles null values correctly', function (): void {
        $route = new ApiRoute(
            uri: null,
            domain: null,
            name: null
        );

        expect($route->getUri())->toBeNull();
        expect($route->getName())->toBeNull();
        expect($route->getDomain())->toBeNull();
    });
});
