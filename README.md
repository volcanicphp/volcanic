# The ultimate so- ✅ **Automatic CRUD API generation** with PHP attributes

-   ✅ **Advanced query capabilities** (sorting, filtering, searching)
-   ✅ **Built-in pagination** with customizable settings
-   ✅ **Flexible validation** with per-operation rules
-   ✅ **Middleware support** for authentication and authorization
-   ✅ **Field visibility control** (hidden/visible fields)
-   ✅ **Smart soft delete handling** with automatic trait detection
-   ✅ **Route customization** (prefix, names, operations)
-   ✅ **Auto-discovery** with manual override optionsution for Laravel APIs

[![Latest Version on Packagist](https://img.shields.io/packagist/v/volcanic/volcanic.svg?style=flat-square)](https://packagist.org/packages/volcanic/volcanic)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/volcanic/volcanic/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/volcanic/volcanic/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/volcanic/volcanic/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/volcanic/volcanic/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/volcanic/volcanic.svg?style=flat-square)](https://packagist.org/packages/volcanic/volcanic)

Volcanic is a Laravel package that provides a powerful, attribute-based approach to creating RESTful APIs. Simply add the `#[API]` attribute to your Eloquent models and get full CRUD operations automatically, with advanced features like filtering, sorting, searching, pagination, and validation.

## Features

-   ✅ **Automatic CRUD API generation** with PHP attributes
-   ✅ **Advanced query capabilities** (sorting, filtering, searching)
-   ✅ **Built-in pagination** with customizable settings
-   ✅ **Flexible validation** with per-operation rules
-   ✅ **Middleware support** for authentication and authorization
-   ✅ **Field visibility control** (hidden/visible fields)
-   ✅ **Soft delete handling** with restore and force delete operations
-   ✅ **Route customization** (prefix, names, operations)
-   ✅ **Auto-discovery** with manual override options

## Installation

You can install the package via composer:

```bash
composer require volcanic/volcanic
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="volcanic-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="volcanic-views"
```

## Usage

### Basic Usage

Add the `#[API]` attribute to any Eloquent model to automatically expose CRUD operations:

```php
<?php

use Illuminate\Database\Eloquent\Model;
use Volcanic\Attributes\API;

#[API]
class User extends Model
{
    protected $fillable = ['name', 'email'];
}
```

This automatically creates these endpoints:

-   `GET /api/users` - List users (paginated)
-   `GET /api/users/{id}` - Show specific user
-   `POST /api/users` - Create user
-   `PUT /api/users/{id}` - Update user
-   `DELETE /api/users/{id}` - Delete user

### Advanced Configuration

```php
#[API(
    prefix: 'v1',
    name: 'customers',
    only: ['index', 'show', 'store'],
    middleware: ['auth:sanctum'],
    sortable: ['name', 'created_at'],
    filterable: ['status', 'type'],
    searchable: ['name', 'email'],
    validation: [
        'store' => [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users'
        ]
    ]
)]
class User extends Model
{
    // ...
}
```

### Query Features

```php
// Pagination
GET /api/users?page=2&per_page=10

// Sorting
GET /api/users?sort_by=name&sort_direction=desc

// Filtering
GET /api/users?filter[status]=active&filter[created_at]=>2023-01-01

// Searching
GET /api/users?search=john

// Field selection
GET /api/users?fields=id,name,email

// Include relationships
GET /api/users?with=posts,profile
```

### Management Commands

```bash
# List all API-enabled models
php artisan volcanic list

# Manually discover routes
php artisan volcanic discover

# Show registered routes
php artisan volcanic routes
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Volcanic](https://github.com/volcanicphp)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
