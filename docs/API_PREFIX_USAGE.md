# API Prefix Functionality

The Volcanic package now automatically ensures all API routes are prefixed with `/api` while allowing you to customize additional prefix segments.

## Default Behavior

Without specifying a prefix, all routes will be under `/api`:

```php
use Volcanic\Attributes\ApiResource;

#[ApiResource]
class User extends Model
{
    // Routes will be: /api/users, /api/users/{id}, etc.
}
```

## Custom Prefixes

When you specify a custom prefix, it will be automatically prepended with `api/`:

```php
#[ApiResource(prefix: 'v1')]
class Product extends Model
{
    // Routes will be: /api/v1/products, /api/v1/products/{id}, etc.
}

#[ApiResource(prefix: 'v2/admin')]
class Admin extends Model
{
    // Routes will be: /api/v2/admin/admins, /api/v2/admin/admins/{id}, etc.
}
```

## Explicit API Prefix

If you already specify `api/` in your prefix, it won't be duplicated:

```php
#[ApiResource(prefix: 'api/v1')]
class Order extends Model
{
    // Routes will be: /api/v1/orders, /api/v1/orders/{id}, etc.
}
```

## ApiRoute Attribute

The same logic applies to the `ApiRoute` attribute:

```php
use Volcanic\Attributes\ApiRoute;

class ProductController
{
    #[ApiRoute(uri: 'special-products', prefix: 'v1')]
    public function specialProducts()
    {
        // Route will be: /api/v1/special-products
    }

    #[ApiRoute(uri: 'featured', prefix: 'api/v2')]
    public function featured()
    {
        // Route will be: /api/v2/featured
    }
}
```

This ensures all your API endpoints maintain a consistent `/api` base path while giving you flexibility in organizing different API versions or sections.
