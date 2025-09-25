<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Volcanic\Attributes\API;
use Volcanic\VolcanicServiceProvider;

// Test model with API attribute
#[API(prefix: 'api', name: 'test-products')]
class TestProduct extends Model
{
    protected $table = 'test_products';

    protected $fillable = ['name', 'price'];
}

// Test controller for custom route
class TestProductController extends Controller
{
    public function customShow(Request $request, string $id): JsonResponse
    {
        return response()->json([
            'message' => 'Custom controller route',
            'source' => 'controller',
            'id' => $id,
        ]);
    }
}

describe('Route Registration Order', function (): void {
    it('demonstrates that route registration order matters', function (): void {
        // This test demonstrates the principle: last registered route wins

        // Register first route
        Route::get('/test-conflict/{id}', fn (): array => ['source' => 'first'])->name('first.route');

        // Register second route with same pattern
        Route::get('/test-conflict/{id}', fn (): array => ['source' => 'second'])->name('second.route');

        // Test which route Laravel matches
        $request = Request::create('/test-conflict/123', 'GET');
        $matchedRoute = Route::getRoutes()->match($request);

        // The last route should win (this is Laravel's behavior)
        expect($matchedRoute->getName())->toBe('second.route');
    });

    it('confirms service provider registration order change', function (): void {
        // This test verifies that the VolcanicServiceProvider change is working
        // by checking that the service provider registers API routes first, then controller routes
        // so that controller routes take precedence (last registered wins)

        $serviceProvider = new VolcanicServiceProvider(app());

        // Use reflection to check the packageBooted method order
        $reflection = new ReflectionClass($serviceProvider);
        $method = $reflection->getMethod('packageBooted');

        // Get the source code of the method
        $filename = $reflection->getFileName();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $source = implode('', array_slice(file($filename), $startLine - 1, $endLine - $startLine + 1));

        // Verify that API routes are mentioned before controller routes in the source
        // This ensures controller routes are registered last and thus take precedence
        $apiPos = strpos($source, 'auto_discover_routes');
        $controllerPos = strpos($source, 'auto_discover_controller_routes');

        expect($apiPos)->toBeLessThan($controllerPos,
            'API route discovery should come before controller route discovery so controller routes take precedence'
        );
    });
});
