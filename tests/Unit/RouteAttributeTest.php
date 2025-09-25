<?php

declare(strict_types=1);

use Volcanic\Attributes\Route;

describe('Route Attribute', function (): void {
    it('can be instantiated with default parameters', function (): void {
        $route = new Route;

        expect($route->getMethods())->toBe(['GET']);
        expect($route->getUri())->toBeNull();
        expect($route->getName())->toBeNull();
        expect($route->getMiddleware())->toBe([]);
        expect($route->getWhereConstraints())->toBe([]);
        expect($route->getDomain())->toBeNull();
    });

    it('can be instantiated with custom parameters', function (): void {
        $route = new Route(
            methods: ['POST', 'PUT'],
            uri: '/api/products/{id}',
            name: 'products.update',
            middleware: ['auth:api', 'throttle:60,1'],
            where: ['id' => '[0-9]+'],
            domain: 'api.example.com'
        );

        expect($route->getMethods())->toBe(['POST', 'PUT']);
        expect($route->getUri())->toBe('/api/products/{id}');
        expect($route->getName())->toBe('products.update');
        expect($route->getMiddleware())->toBe(['auth:api', 'throttle:60,1']);
        expect($route->getWhereConstraints())->toBe(['id' => '[0-9]+']);
        expect($route->getDomain())->toBe('api.example.com');
    });

    it('can handle different HTTP methods', function (): void {
        $getRoute = new Route(methods: ['GET']);
        $postRoute = new Route(methods: ['POST']);
        $putPatchRoute = new Route(methods: ['PUT', 'PATCH']);
        $deleteRoute = new Route(methods: ['DELETE']);

        expect($getRoute->getMethods())->toBe(['GET']);
        expect($postRoute->getMethods())->toBe(['POST']);
        expect($putPatchRoute->getMethods())->toBe(['PUT', 'PATCH']);
        expect($deleteRoute->getMethods())->toBe(['DELETE']);
    });

    it('can handle empty middleware array', function (): void {
        $route = new Route(middleware: []);
        expect($route->getMiddleware())->toBe([]);
    });

    it('can handle single middleware', function (): void {
        $route = new Route(middleware: ['auth']);
        expect($route->getMiddleware())->toBe(['auth']);
    });

    it('can handle multiple middleware', function (): void {
        $route = new Route(middleware: ['auth:api', 'throttle:60,1', 'role:admin']);
        expect($route->getMiddleware())->toBe(['auth:api', 'throttle:60,1', 'role:admin']);
    });

    it('can handle where constraints', function (): void {
        $route = new Route(where: ['id' => '[0-9]+', 'slug' => '[a-zA-Z0-9-]+']);
        expect($route->getWhereConstraints())->toBe(['id' => '[0-9]+', 'slug' => '[a-zA-Z0-9-]+']);
    });

    it('can handle domain constraints', function (): void {
        $route = new Route(domain: 'api.example.com');
        expect($route->getDomain())->toBe('api.example.com');

        $subdomainRoute = new Route(domain: 'admin.{subdomain}.example.com');
        expect($subdomainRoute->getDomain())->toBe('admin.{subdomain}.example.com');
    });

    it('handles null values correctly', function (): void {
        $route = new Route(
            uri: null,
            name: null,
            domain: null
        );

        expect($route->getUri())->toBeNull();
        expect($route->getName())->toBeNull();
        expect($route->getDomain())->toBeNull();
    });
});
