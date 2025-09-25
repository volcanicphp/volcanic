<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Volcanic\Attributes\ApiRoute;
use Volcanic\Services\ApiRouteDiscoveryService;

class HttpMethodDetectionController
{
    #[ApiRoute]
    public function index(): void {}

    #[ApiRoute]
    public function show(): void {}

    #[ApiRoute]
    public function store(): void {}

    #[ApiRoute]
    public function update(): void {}

    #[ApiRoute]
    public function destroy(): void {}

    #[ApiRoute]
    public function delete(): void {}

    #[ApiRoute]
    public function forceDelete(): void {}

    #[ApiRoute]
    public function restore(): void {}

    #[ApiRoute]
    public function customMethod(): void {}

    #[ApiRoute(methods: ['POST', 'PUT'])]
    public function explicitMethods(): void {}
}

describe('HTTP Method Auto-Detection Integration', function (): void {
    it('automatically assigns correct HTTP methods to routes', function (): void {
        // Create the discovery service
        $discoveryService = app(ApiRouteDiscoveryService::class);

        // Register routes for our test controller
        $discoveryService->registerControllerRoutes(HttpMethodDetectionController::class);

        // Get all registered routes
        $routes = collect(Route::getRoutes()->getRoutes());

        // Filter routes that belong to our controller
        $controllerRoutes = $routes->filter(fn ($route): bool => str_contains((string) $route->getActionName(), 'HttpMethodDetectionController'));

        expect($controllerRoutes->count())->toBe(10);

        // Check each method has the correct HTTP methods
        $expectedMethods = [
            'index' => ['GET', 'HEAD'],
            'show' => ['GET', 'HEAD'],
            'store' => ['POST'],
            'update' => ['PUT', 'PATCH'],
            'destroy' => ['DELETE'],
            'delete' => ['DELETE'],
            'forceDelete' => ['DELETE'],
            'restore' => ['PATCH'],
            'customMethod' => ['GET', 'HEAD'],
            'explicitMethods' => ['POST', 'PUT'], // Explicit override
        ];

        foreach ($controllerRoutes as $route) {
            // Extract method name from action
            $actionName = $route->getActionName();
            $methodName = substr($actionName, strrpos($actionName, '@') + 1);

            $routeMethods = $route->methods();

            expect($routeMethods)->toBe($expectedMethods[$methodName], "Method {$methodName} should have HTTP methods ".json_encode($expectedMethods[$methodName]).' but got '.json_encode($routeMethods));
        }
    });

    it('respects explicit method overrides in ApiRoute attribute', function (): void {
        // Create discovery service and register routes
        $discoveryService = app(ApiRouteDiscoveryService::class);
        $discoveryService->registerControllerRoutes(HttpMethodDetectionController::class);

        $routes = collect(Route::getRoutes()->getRoutes());
        $explicitMethodRoute = $routes->first(fn ($route): bool => str_contains((string) $route->getActionName(), 'explicitMethods'));

        expect($explicitMethodRoute)->not->toBeNull();
        expect($explicitMethodRoute->methods())->toBe(['POST', 'PUT']);
    });
});
