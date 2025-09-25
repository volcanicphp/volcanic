<?php

declare(strict_types=1);

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Volcanic\Attributes\ApiRoute;
use Volcanic\Services\ApiRouteDiscoveryService;

// Simple test controller
class SimpleTestController extends Controller
{
    #[ApiRoute(
        methods: ['GET'],
        uri: '/test/simple',
        name: 'test.simple',
        middleware: ['web']
    )]
    public function simple(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Hello from Route attribute!']);
    }
}

describe('Route Discovery Integration', function (): void {
    it('can register routes using ApiRouteDiscoveryService', function (): void {
        // Register routes for our test controller
        $routeDiscovery = new ApiRouteDiscoveryService;
        $routeDiscovery->registerControllerRoutes(SimpleTestController::class);

        // Check if our specific route exists
        $routes = Route::getRoutes();

        // Find the route by manual iteration (more reliable for testing)
        $route = null;
        foreach ($routes->getRoutes() as $registeredRoute) {
            if ($registeredRoute->getName() === 'test.simple') {
                $route = $registeredRoute;
                break;
            }
        }

        expect($route)->not->toBeNull('Route "test.simple" should be registered');
        expect($route->uri())->toBe('api/test/simple');
        expect($route->methods())->toContain('GET');

        // Verify route helper works (additional confirmation)
        $routeUrl = route('test.simple', [], false);
        expect($routeUrl)->toBe('/api/test/simple');

        // Also verify the middleware was applied correctly
        expect($route->middleware())->toContain('web');
    });

    it('validates ApiRouteDiscoveryService is registered as singleton', function (): void {
        expect(app()->bound(ApiRouteDiscoveryService::class))->toBeTrue();

        $service1 = app(ApiRouteDiscoveryService::class);
        $service2 = app(ApiRouteDiscoveryService::class);

        expect($service1)->toBe($service2); // Same instance (singleton)
    });
});
