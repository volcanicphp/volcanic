# Route Attribute Usage

The `#[Route]` attribute allows you to define route configurations directly on controller methods, eliminating the need to manually register routes in your route files.

## Basic Usage

```php
use Volcanic\Attributes\Route;

class ProductController extends Controller
{
    #[Route(
        methods: ['GET'],
        uri: '/api/products',
        name: 'products.index',
        middleware: ['auth:api']
    )]
    public function index(Request $request): JsonResponse
    {
        // Your controller logic here
        return response()->json(['data' => []]);
    }
}
```

## Attribute Parameters

### `methods` (array)

HTTP methods that this route should respond to.

-   **Default:** `['GET']`
-   **Examples:** `['GET']`, `['POST']`, `['PUT', 'PATCH']`, `['DELETE']`

```php
#[Route(methods: ['POST'])]
public function store(Request $request): JsonResponse { }

#[Route(methods: ['PUT', 'PATCH'])]
public function update(Request $request): JsonResponse { }
```

### `uri` (string, optional)

The URI pattern for the route. If not specified, a default URI will be generated based on the controller and method names.

-   **Default:** Auto-generated (e.g., `controller-name/method-name`)
-   **Examples:** `/api/products`, `/api/products/{id}`, `/admin/users/{user}/profile`

```php
#[Route(uri: '/api/products')]
public function index(): JsonResponse { }

#[Route(uri: '/api/products/{id}')]
public function show(int $id): JsonResponse { }

// Auto-generated URI: /product/featured
#[Route(methods: ['GET'])]
public function featured(): JsonResponse { }
```

### `name` (string, optional)

The name for the route, used for route generation with `route()` helper.

-   **Default:** Auto-generated (e.g., `controller-name.method-name`)
-   **Examples:** `products.index`, `admin.users.show`

```php
#[Route(name: 'products.index')]
public function index(): JsonResponse { }

// Auto-generated name: product.featured
#[Route(methods: ['GET'])]
public function featured(): JsonResponse { }
```

### `middleware` (array)

Middleware to apply to this route.

-   **Default:** `[]` (no middleware)
-   **Examples:** `['auth']`, `['auth:api', 'throttle:60,1']`, `['can:view-products']`

```php
#[Route(middleware: ['auth:api'])]
public function index(): JsonResponse { }

#[Route(middleware: ['auth:api', 'role:admin', 'throttle:10,1'])]
public function adminOnly(): JsonResponse { }
```

### `where` (array)

Regular expression constraints for route parameters.

-   **Default:** `[]` (no constraints)
-   **Examples:** `['id' => '[0-9]+']`, `['slug' => '[a-zA-Z0-9-]+']`

```php
#[Route(
    uri: '/api/products/{id}',
    where: ['id' => '[0-9]+']
)]
public function show(int $id): JsonResponse { }

#[Route(
    uri: '/api/categories/{slug}/products/{id}',
    where: [
        'slug' => '[a-zA-Z0-9-]+',
        'id' => '[0-9]+'
    ]
)]
public function categoryProduct(string $slug, int $id): JsonResponse { }
```

### `domain` (string, optional)

Domain constraint for the route.

-   **Default:** `null` (no domain constraint)
-   **Examples:** `api.example.com`, `admin.mysite.com`

```php
#[Route(
    uri: '/api/products',
    domain: 'api.example.com'
)]
public function index(): JsonResponse { }

#[Route(
    uri: '/admin/dashboard',
    domain: 'admin.{subdomain}.example.com',
    where: ['subdomain' => '[a-zA-Z0-9-]+']
)]
public function adminDashboard(): JsonResponse { }
```

## Complete Examples

### CRUD Operations

```php
class ProductController extends Controller
{
    // List all products
    #[Route(
        methods: ['GET'],
        uri: '/api/products',
        name: 'products.index',
        middleware: ['auth:api']
    )]
    public function index(Request $request): JsonResponse
    {
        // Your logic here
        return response()->json(['data' => []]);
    }

    // Show single product
    #[Route(
        methods: ['GET'],
        uri: '/api/products/{id}',
        name: 'products.show',
        middleware: ['auth:api'],
        where: ['id' => '[0-9]+']
    )]
    public function show(int $id): JsonResponse
    {
        // Your logic here
        return response()->json(['data' => ['id' => $id]]);
    }

    // Create new product
    #[Route(
        methods: ['POST'],
        uri: '/api/products',
        name: 'products.store',
        middleware: ['auth:api', 'can:create-products']
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0'
        ]);

        // Your logic here
        return response()->json(['data' => $validated], 201);
    }

    // Update existing product
    #[Route(
        methods: ['PUT', 'PATCH'],
        uri: '/api/products/{id}',
        name: 'products.update',
        middleware: ['auth:api', 'can:update-products'],
        where: ['id' => '[0-9]+']
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0'
        ]);

        // Your logic here
        return response()->json(['data' => $validated]);
    }

    // Delete product
    #[Route(
        methods: ['DELETE'],
        uri: '/api/products/{id}',
        name: 'products.destroy',
        middleware: ['auth:api', 'can:delete-products'],
        where: ['id' => '[0-9]+']
    )]
    public function destroy(int $id): JsonResponse
    {
        // Your logic here
        return response()->json(['message' => 'Product deleted']);
    }
}
```

### Advanced Examples

#### Multiple Domains

```php
// Public API
#[Route(
    methods: ['GET'],
    uri: '/api/products',
    domain: 'api.example.com',
    middleware: ['throttle:100,1']
)]
public function publicIndex(): JsonResponse { }

// Admin API
#[Route(
    methods: ['GET'],
    uri: '/api/products',
    domain: 'admin.example.com',
    middleware: ['auth:admin', 'role:admin']
)]
public function adminIndex(): JsonResponse { }
```

#### Complex Route Patterns

```php
#[Route(
    methods: ['GET'],
    uri: '/api/categories/{category}/products/{product}/reviews',
    name: 'product.reviews.index',
    where: [
        'category' => '[a-zA-Z0-9-]+',
        'product' => '[0-9]+'
    ],
    middleware: ['auth:api']
)]
public function productReviews(string $category, int $product): JsonResponse { }
```

#### Auto-generated Routes

```php
// URI will be: /product/featured
// Name will be: product.featured
#[Route(methods: ['GET'])]
public function featured(): JsonResponse { }

// URI will be: /product/on-sale
// Name will be: product.on-sale
#[Route(methods: ['GET'], middleware: ['cache:1hour'])]
public function onSale(): JsonResponse { }
```

## Route Registration

The RouteDiscoveryService is automatically enabled by default through the VolcanicServiceProvider.

### Automatic Registration (Default)

By default, routes are automatically discovered and registered when your Laravel application boots. This is controlled by the `volcanic.auto_discover_controller_routes` configuration option.

### Configuration

In your `config/volcanic.php` file:

```php
return [
    // Enable/disable automatic controller route discovery
    'auto_discover_controller_routes' => true,

    // Specify custom controller paths to scan
    'controller_paths' => [
        // app_path('Http/Controllers/Api'),
        // app_path('Http/Controllers/Admin'),
    ],
];
```

If `controller_paths` is empty, it defaults to scanning `app_path('Http/Controllers')`.

### Manual Registration

If you prefer manual control or need to register routes at specific times:

#### In a Service Provider

```php
use Volcanic\Services\RouteDiscoveryService;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Discover and register all routes in controllers
        app(RouteDiscoveryService::class)->discoverAndRegisterRoutes();

        // Or specify custom controller paths
        app(RouteDiscoveryService::class)->discoverAndRegisterRoutes([
            app_path('Http/Controllers/Api'),
            app_path('Http/Controllers/Admin'),
        ]);
    }
}
```

#### For Specific Controllers

```php
use Volcanic\Services\RouteDiscoveryService;

// Register routes for a specific controller
app(RouteDiscoveryService::class)->registerControllerRoutes(ProductController::class);
```

### Disabling Automatic Discovery

To disable automatic route discovery, set the config option to `false`:

```php
// config/volcanic.php
'auto_discover_controller_routes' => false,
```

Then register routes manually using one of the methods above.

## Auto-generation Rules

When `uri` or `name` are not specified, they are auto-generated:

### URI Generation

-   Controller: `ProductController`
-   Method: `featured`
-   Generated URI: `/product/featured`

### Name Generation

-   Controller: `ProductController`
-   Method: `featured`
-   Generated Name: `product.featured`

The generation uses kebab-case and removes the "Controller" suffix from class names.

## Tips and Best Practices

1. **Keep It Simple**: The Route attribute is for configuration only. Keep all business logic in your controller methods.

2. **Use Middleware**: Apply appropriate middleware for authentication, authorization, and rate limiting.

3. **Validate Parameters**: Use `where` constraints to validate route parameters at the route level.

4. **Naming Convention**: Use consistent naming patterns for your route names to make them predictable.

5. **Group Related Routes**: Consider using consistent URI patterns and middleware for related operations.

6. **Domain Separation**: Use domain constraints to separate different API versions or admin interfaces.

This attribute-based approach keeps your route definitions close to your controller logic while maintaining clean separation of concerns.
