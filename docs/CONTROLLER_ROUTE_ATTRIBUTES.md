# Controller Route Attributes

Volcanic provides a set of attributes that can be used on controller methods to automatically register routes with customizable configurations. These attributes eliminate the need to manually define routes in `routes/api.php` or `routes/web.php`.

## Available Attributes

### `#[Index]`

Used for listing/index endpoints that typically return collections of resources.

**Parameters:**

-   `uri` (string, optional): Custom URI pattern. Default: auto-generated from method name
-   `name` (string, optional): Route name. Default: auto-generated
-   `middleware` (array): Middleware to apply to the route
-   `paginate` (bool): Enable pagination. Default: `true`
-   `perPage` (int, optional): Items per page for pagination
-   `sortable` (array): Fields that can be sorted
-   `filterable` (array): Fields that can be filtered
-   `searchable` (array): Fields that can be searched
-   `scoutSearch` (bool, optional): Enable Laravel Scout search
-   `methods` (array): HTTP methods. Default: `['GET']`
-   `where` (array): Route parameter constraints
-   `domain` (string, optional): Domain constraint

**Example:**

```php
#[Index(
    uri: '/api/products',
    name: 'products.index',
    middleware: ['auth:api'],
    paginate: true,
    perPage: 20,
    sortable: ['name', 'price', 'created_at'],
    filterable: ['category', 'status'],
    searchable: ['name', 'description']
)]
public function index(Request $request): JsonResponse
{
    // Your index logic here
}
```

### `#[Show]`

Used for showing individual resources.

**Parameters:**

-   `uri` (string, optional): Custom URI pattern. Default: auto-generated with `{id}` parameter
-   `name` (string, optional): Route name. Default: auto-generated
-   `middleware` (array): Middleware to apply to the route
-   `methods` (array): HTTP methods. Default: `['GET']`
-   `where` (array): Route parameter constraints
-   `domain` (string, optional): Domain constraint
-   `parameterName` (string, optional): Custom parameter name for route model binding

**Example:**

```php
#[Show(
    uri: '/api/products/{product}',
    name: 'products.show',
    middleware: ['auth:api'],
    parameterName: 'product'
)]
public function show(Request $request, int $id): JsonResponse
{
    // Your show logic here
}
```

### `#[Store]`

Used for creating new resources.

**Parameters:**

-   `uri` (string, optional): Custom URI pattern. Default: auto-generated
-   `name` (string, optional): Route name. Default: auto-generated
-   `middleware` (array): Middleware to apply to the route
-   `methods` (array): HTTP methods. Default: `['POST']`
-   `where` (array): Route parameter constraints
-   `domain` (string, optional): Domain constraint
-   `rules` (array|string): Validation rules for the request
-   `formRequest` (string, optional): Custom Form Request class for validation

**Example:**

```php
#[Store(
    uri: '/api/products',
    name: 'products.store',
    middleware: ['auth:api'],
    rules: [
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
        'description' => 'nullable|string'
    ]
)]
public function store(Request $request): JsonResponse
{
    // Your store logic here
}
```

### `#[Update]`

Used for updating existing resources.

**Parameters:**

-   `uri` (string, optional): Custom URI pattern. Default: auto-generated with `{id}` parameter
-   `name` (string, optional): Route name. Default: auto-generated
-   `middleware` (array): Middleware to apply to the route
-   `methods` (array): HTTP methods. Default: `['PUT', 'PATCH']`
-   `where` (array): Route parameter constraints
-   `domain` (string, optional): Domain constraint
-   `rules` (array|string): Validation rules for the request
-   `formRequest` (string, optional): Custom Form Request class for validation
-   `parameterName` (string, optional): Custom parameter name for route model binding

**Example:**

```php
#[Update(
    uri: '/api/products/{product}',
    name: 'products.update',
    middleware: ['auth:api'],
    methods: ['PUT', 'PATCH'],
    rules: [
        'name' => 'sometimes|string|max:255',
        'price' => 'sometimes|numeric|min:0',
        'description' => 'nullable|string'
    ],
    parameterName: 'product'
)]
public function update(Request $request, int $id): JsonResponse
{
    // Your update logic here
}
```

### `#[Destroy]`

Used for deleting resources (soft delete by default).

**Parameters:**

-   `uri` (string, optional): Custom URI pattern. Default: auto-generated with `{id}` parameter
-   `name` (string, optional): Route name. Default: auto-generated
-   `middleware` (array): Middleware to apply to the route
-   `methods` (array): HTTP methods. Default: `['DELETE']`
-   `where` (array): Route parameter constraints
-   `domain` (string, optional): Domain constraint
-   `parameterName` (string, optional): Custom parameter name for route model binding
-   `forceDelete` (bool): Whether to perform a force delete. Default: `false`

**Example:**

```php
#[Destroy(
    uri: '/api/products/{product}',
    name: 'products.destroy',
    middleware: ['auth:api'],
    parameterName: 'product'
)]
public function destroy(Request $request, int $id): JsonResponse
{
    // Your destroy logic here (soft delete)
}
```

### `#[Restore]`

Used for restoring soft-deleted resources.

**Parameters:**

-   `uri` (string, optional): Custom URI pattern. Default: auto-generated with `{id}/restore` pattern
-   `name` (string, optional): Route name. Default: auto-generated
-   `middleware` (array): Middleware to apply to the route
-   `methods` (array): HTTP methods. Default: `['PATCH', 'POST']`
-   `where` (array): Route parameter constraints
-   `domain` (string, optional): Domain constraint
-   `parameterName` (string, optional): Custom parameter name for route model binding

**Example:**

```php
#[Restore(
    uri: '/api/products/{product}/restore',
    name: 'products.restore',
    middleware: ['auth:api'],
    methods: ['PATCH'],
    parameterName: 'product'
)]
public function restore(Request $request, int $id): JsonResponse
{
    // Your restore logic here
}
```

### `#[ForceDelete]`

Used for permanently deleting resources.

**Parameters:**

-   `uri` (string, optional): Custom URI pattern. Default: auto-generated with `{id}/force` pattern
-   `name` (string, optional): Route name. Default: auto-generated
-   `middleware` (array): Middleware to apply to the route
-   `methods` (array): HTTP methods. Default: `['DELETE']`
-   `where` (array): Route parameter constraints
-   `domain` (string, optional): Domain constraint
-   `parameterName` (string, optional): Custom parameter name for route model binding

**Example:**

```php
#[ForceDelete(
    uri: '/api/products/{product}/force',
    name: 'products.force-delete',
    middleware: ['auth:api'],
    parameterName: 'product'
)]
public function forceDelete(Request $request, int $id): JsonResponse
{
    // Your force delete logic here
}
```

## Usage

1. **Add attributes to your controller methods:**

    ```php
    use Volcanic\Attributes\Index;
    use Volcanic\Attributes\Show;
    use Volcanic\Attributes\Store;
    // ... other attributes

    class ProductController extends Controller
    {
        #[Index(middleware: ['auth:api'])]
        public function index(Request $request): JsonResponse
        {
            // Your logic here
        }
    }
    ```

2. **Register route discovery in your service provider:**

    ```php
    use Volcanic\Services\ApiRouteDiscoveryService;

    public function boot()
    {
        app(ApiRouteDiscoveryService::class)->discoverAndRegisterRoutes();
    }
    ```

3. **Or register routes for specific controllers:**
    ```php
    app(ApiRouteDiscoveryService::class)->registerControllerRoutes(ProductController::class);
    ```

## Advanced Features

### Route Parameter Constraints

```php
#[Show(
    uri: '/api/users/{user}',
    where: ['user' => '[0-9]+'] // Only numeric IDs
)]
public function show(int $user): JsonResponse { }
```

### Domain-specific Routes

```php
#[Index(
    uri: '/api/products',
    domain: 'api.example.com'
)]
public function index(): JsonResponse { }
```

### Custom Form Requests

```php
#[Store(
    formRequest: ProductStoreRequest::class
)]
public function store(ProductStoreRequest $request): JsonResponse { }
```

### Multiple HTTP Methods

```php
#[Update(
    methods: ['PUT', 'PATCH', 'POST']
)]
public function update(Request $request): JsonResponse { }
```

## Auto-generated Defaults

When parameters are not specified, the attributes will auto-generate sensible defaults:

-   **URI**: Based on method name and attribute type (e.g., `products` for Index, `products/{id}` for Show)
-   **Route Name**: Based on controller and method name (e.g., `product-controller.index`)
-   **HTTP Methods**: Appropriate defaults for each action type

This allows for minimal configuration while maintaining flexibility for complex routing requirements.
