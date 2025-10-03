# API Playground

Volcanic includes a powerful API Playground similar to GraphiQL but designed specifically for REST APIs. The playground provides an interactive interface to explore, test, and document your APIs with features like autocomplete, schema introspection, and intelligent request building.

## Features

-   🎯 **Interactive Request Builder** - Build and test API requests with a Postman-like interface
-   🔍 **Auto-complete & Intelligence** - Smart suggestions based on your API schema
-   📊 **Schema Introspection** - Automatic discovery of routes, models, and database schema
-   🔐 **Authorization Support** - Configure Bearer tokens, Basic Auth, and custom headers
-   📝 **Request/Response Formatting** - JSON syntax highlighting and pretty-printing
-   🚀 **Zero Configuration** - Works out of the box with auto-discovered routes
-   🔒 **Environment-Based Access Control** - Secure access control with flexible configuration

## Quick Start

### Accessing the Playground

By default, the playground is accessible in `local` and `development` environments at:

```
http://your-app.test/volcanic/playground
```

### Environment Configuration

The playground is automatically enabled in development environments. You can customize this behavior:

#### Enable in Production

In your `AppServiceProvider`:

```php
use Volcanic\Facades\Playground;

public function boot(): void
{
    // Enable for everyone (use with caution!)
    Playground::canAccess(true);
}
```

#### Custom Access Control

```php
use Volcanic\Facades\Playground;

public function boot(): void
{
    // Custom logic
    Playground::canAccess(function () {
        return auth()->check() && auth()->user()->isAdmin();
    });
}
```

#### Environment Variable

Add to your `.env`:

```env
VOLCANIC_PLAYGROUND_ENABLED=true
```

## Playground Interface

### Main Components

1. **Route Explorer (Left Sidebar)**

    - Browse all available API routes
    - Search and filter routes
    - Click to auto-populate request builder

2. **Model Inspector (Left Sidebar)**

    - View all models with `#[ApiResource]` attribute
    - Explore model fields and types
    - See hidden/fillable properties

3. **Request Builder (Main Panel)**

    - Configure HTTP method (GET, POST, PUT, PATCH, DELETE)
    - Set request URL with autocomplete
    - Manage query parameters
    - Set custom headers
    - Configure authorization
    - Build request body (JSON or Form Data)

4. **Response Viewer (Main Panel)**
    - View formatted response body
    - Inspect response headers
    - See response status and timing

### Using the Request Builder

#### Query Parameters

Add dynamic query parameters to your requests:

```
Key: per_page
Value: 20

Key: sort_by
Value: created_at:desc

Key: filter[status]
Value: active
```

#### Headers

Configure custom headers:

```
Key: Accept
Value: application/json

Key: X-Custom-Header
Value: custom-value
```

#### Authorization

**Bearer Token:**

1. Select "Bearer Token" from Auth Type
2. Enter your API token

**Basic Auth:**

1. Select "Basic Auth" from Auth Type
2. Enter username and password

#### Request Body

**JSON:**

```json
{
    "name": "New Product",
    "description": "Product description",
    "price": 99.99
}
```

**Form Data:**

-   Add key-value pairs for form fields
-   Automatically serialized as JSON

## Schema API

The playground connects to a schema endpoint that provides complete API introspection.

### Endpoint

```
GET /volcanic/playground/schema
```

### Response Structure

```json
{
  "routes": [
    {
      "method": "GET",
      "uri": "/api/products",
      "name": "products.index",
      "action": "...",
      "middleware": [...],
      "parameters": []
    }
  ],
  "models": [
    {
      "name": "Product",
      "class": "App\\Models\\Product",
      "table": "products",
      "fields": [
        {
          "name": "id",
          "type": "integer",
          "nullable": false,
          "default": null,
          "key": "PRI"
        },
        {
          "name": "name",
          "type": "string",
          "nullable": false,
          "default": null,
          "key": ""
        }
      ],
      "hidden": ["password"],
      "fillable": ["name", "description", "price"],
      "guarded": ["*"],
      "casts": {
        "id": "int",
        "price": "decimal:2"
      },
      "hasApiResource": true,
      "apiResourceConfig": {
        "prefix": "api",
        "only": ["index", "show", "store", "update", "destroy"],
        "except": [],
        "paginate": true,
        "perPage": 15,
        "paginationType": "length_aware",
        "sortable": ["*"],
        "filterable": ["*"],
        "searchable": ["name", "description"],
        "softDeletes": false
      }
    }
  ]
}
```

### Schema Features

#### Hidden Fields Protection

The schema respects model `$hidden` properties. Fields like `password` or `api_token` won't be exposed:

```php
class User extends Model
{
    protected $hidden = ['password', 'remember_token'];
}
```

Schema output will **not** include these fields in the `fields` array.

#### Database Type Normalization

Database column types are normalized to common types:

| Database Type | Normalized Type |
| ------------- | --------------- |
| varchar(255)  | string          |
| int(11)       | integer         |
| decimal(10,2) | decimal         |
| tinyint(1)    | boolean         |
| datetime      | datetime        |
| json          | json            |

#### Route Parameters

Route parameters are automatically extracted:

```php
Route: /api/posts/{id}/comments/{comment}

Parameters: [
  {"name": "id", "required": true},
  {"name": "comment", "required": true}
]
```

## Security Considerations

### Production Access

⚠️ **Important:** The playground exposes your API structure, database schema, and available routes.

**Best Practices:**

1. **Never enable in production** unless behind authentication
2. **Use environment-based access control**
3. **Implement IP whitelisting** if needed
4. **Monitor access logs** for unauthorized attempts

### Recommended Configuration

```php
// AppServiceProvider.php
use Volcanic\Facades\Playground;

public function boot(): void
{
    Playground::canAccess(function () {
        // Only in development environments
        if (! app()->environment(['local', 'development'])) {
            return false;
        }

        // Or require authentication
        return auth()->check() && auth()->user()->can('access-playground');
    });
}
```

## Customization

### Route Prefix

Customize the playground route prefix in `config/volcanic.php`:

```php
'playground' => [
    'enabled' => env('VOLCANIC_PLAYGROUND_ENABLED', true),
    'route_prefix' => 'volcanic/playground', // Change this
],
```

### Disable Completely

Set in `.env`:

```env
VOLCANIC_PLAYGROUND_ENABLED=false
```

Or in code:

```php
Playground::canAccess(false);
```

## Integration with Apollo Studio-Like Features

The playground provides Apollo Studio-like features for REST:

1. **Schema-Driven Autocomplete** - As you type URLs, get suggestions based on registered routes
2. **Field Intelligence** - See available fields, types, and constraints for each model
3. **Validation Hints** - Know which fields are required, fillable, or hidden
4. **Real-Time Testing** - Test endpoints immediately with proper type coercion
5. **Documentation** - Self-documenting API through schema introspection

## Troubleshooting

### Playground Returns 403

**Issue:** "Playground is not accessible in this environment."

**Solution:**

-   Check your environment: `php artisan env`
-   Verify `VOLCANIC_PLAYGROUND_ENABLED` in `.env`
-   Review `Playground::canAccess()` configuration

### Schema Not Loading

**Issue:** Routes or models not appearing in schema

**Solution:**

-   Verify models have `#[ApiResource]` attribute
-   Check model paths in `config/volcanic.php`
-   Ensure database tables exist
-   Run `php artisan volcanic:list` to see discovered models

### Database Schema Issues

**Issue:** Model fields not showing correctly

**Solution:**

-   Verify database connection
-   Run migrations: `php artisan migrate`
-   Check table name matches model's `$table` property
-   Review database driver compatibility (MySQL, PostgreSQL, SQLite)

## API Reference

### Playground Facade

```php
use Volcanic\Facades\Playground;

// Enable/disable playground
Playground::canAccess(true);
Playground::canAccess(false);

// Custom access logic
Playground::canAccess(fn() => auth()->check());

// Check current access status
if (Playground::check()) {
    // Playground is accessible
}

// Reset to default (dev environments only)
Playground::reset();
```

### Schema Service

```php
use Volcanic\Services\SchemaService;

$schemaService = app(SchemaService::class);

// Get complete schema
$schema = $schemaService->getSchema();

// Access routes
$routes = $schema['routes'];

// Access models
$models = $schema['models'];
```

## Examples

### Basic Usage

1. Open playground: `http://your-app.test/volcanic/playground`
2. Click a route from the sidebar
3. Modify parameters/headers as needed
4. Click "Send"
5. View formatted response

### Testing Filtering

```
URL: /api/products
Query Params:
  - filter[status]: active
  - filter[price:gte]: 100
  - sort_by: price:desc
  - per_page: 20
```

### Testing Creation

```
Method: POST
URL: /api/products
Headers:
  - Content-Type: application/json
  - Authorization: Bearer your-token-here
Body:
{
  "name": "New Product",
  "description": "Amazing product",
  "price": 149.99,
  "status": "active"
}
```

## Advanced Features

### Model Relationships

The schema includes model casts and types, allowing you to understand data transformations:

```json
{
    "casts": {
        "id": "int",
        "price": "decimal:2",
        "published_at": "datetime",
        "metadata": "json"
    }
}
```

### API Resource Configuration

View the exact configuration for each model's API:

```json
{
    "apiResourceConfig": {
        "prefix": "api/v1",
        "paginate": true,
        "perPage": 25,
        "sortable": ["name", "created_at"],
        "filterable": ["status", "category_id"],
        "searchable": ["name", "description"]
    }
}
```

This helps you understand:

-   Which fields are sortable
-   Which fields can be filtered
-   Which fields are searchable
-   Pagination settings

## Contributing

The playground is an evolving feature. Contributions welcome for:

-   UI/UX improvements
-   Additional database driver support
-   Enhanced autocomplete features
-   Request history/favorites
-   Code generation from playground requests

See [CONTRIBUTING.md](../CONTRIBUTING.md) for details.
