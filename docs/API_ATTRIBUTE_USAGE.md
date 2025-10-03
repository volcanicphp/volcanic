# Volcanic API Attribute Usage Examples

## Basic Usage

To expose a full CRUD API for a Laravel model, simply add the `#[API]` attribute:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Volcanic\Attributes\API;

#[API]
class User extends Model
{
    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password'];
}
```

This will automatically create the following API endpoints:

- `GET /api/users` - List all users (paginated)
- `GET /api/users/{id}` - Show a specific user
- `POST /api/users` - Create a new user
- `PUT/PATCH /api/users/{id}` - Update a user
- `DELETE /api/users/{id}` - Delete a user

## Automatic Soft Delete Detection

Volcanic automatically detects if your model uses the `SoftDeletes` trait and enables soft delete operations:

```php
<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Volcanic\Attributes\API;

#[API]
class Post extends Model
{
    use SoftDeletes; // Automatically detected!

    protected $fillable = ['title', 'content'];
}
```

This automatically adds the following soft delete endpoints:

- `POST /api/posts/{id}/restore` - Restore a soft-deleted post
- `DELETE /api/posts/{id}/force` - Permanently delete a post
- Query parameters: `?include_trashed=1` and `?only_trashed=1`

You can explicitly disable soft delete operations even when the trait is present:

```php
#[API(softDeletes: false)] // Explicit override
class Post extends Model
{
    use SoftDeletes;
}
```

## Advanced Configuration

You can customize the API behavior using various parameters:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Volcanic\Attributes\API;

#[API(
    prefix: 'v1',
    name: 'customers',
    only: ['index', 'show', 'store'],
    middleware: ['auth:sanctum', 'throttle:60,1'],
    paginated: true,
    perPage: 20,
    sortable: ['name', 'created_at', 'email'],
    filterable: ['status', 'type', 'created_at'],
    searchable: ['name', 'email', 'company'],
    validation: [
        'store' => [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'status' => 'required|in:active,inactive'
        ],
        'update' => [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email',
            'status' => 'sometimes|in:active,inactive'
        ]
    ],
    hidden: ['password', 'remember_token'],
    visible: ['id', 'name', 'email', 'status', 'created_at']
)]
class User extends Model
{
    protected $fillable = ['name', 'email', 'password', 'status'];
}
```

This configuration will create endpoints under `/v1/customers` with:

- Only `index`, `show`, and `store` operations
- Authentication and rate limiting middleware
- Pagination with 20 items per page
- Sorting by name, created_at, or email
- Filtering by status, type, or created_at
- Search across name, email, and company fields
- Validation rules for create and update operations
- Field visibility controls

## API Query Parameters

### Pagination

```
GET /api/users?page=2&per_page=10
```

### Sorting

```
GET /api/users?sort_by=name&sort_direction=desc
```

### Filtering

```
GET /api/users?filter[status]=active&filter[type]=premium
GET /api/users?filter[created_at]=>2023-01-01
GET /api/users?filter[age]=18|65  (between 18 and 65)
```

### Searching

```
GET /api/users?search=john
```

### Field Selection

```
GET /api/users?fields=id,name,email
```

### Including Relationships

```
GET /api/users?with=posts,comments
```

### Wildcard Field Support

Volcanic supports wildcard (`*`) configuration for `sortable` and `filterable` fields to allow any field:

```php
#[API(
    sortable: ['*'],      // Allow sorting by any field
    filterable: ['*'],    // Allow filtering by any field
    searchable: ['name']  // Explicit fields only (no wildcard support)
)]
class User extends Model
{
    protected $fillable = ['name', 'email', 'status', 'role'];
}
```

With wildcard configuration, users can sort and filter by any field that exists on the model:

```
GET /api/users?sort_by=email&sort_direction=desc
GET /api/users?filter[role]=admin&filter[status]=active
```

**Important Notes:**

- Wildcard validation checks if the field exists on the model (either in `fillable` or table columns)
- If an invalid field is used, an `InvalidFieldException` will be thrown with a clear error message
- Wildcard is currently only supported for `sortable` and `filterable` fields
- You can combine wildcards with explicit fields: `sortable: ['name', '*']`

**Error Responses:**
When an invalid field is used, you'll receive a 400 error:

```json
{
  "message": "Field 'invalid_field' is not allowed for sorting. This API accepts any field due to wildcard (*) configuration, but 'invalid_field' may not exist on this model."
}
```

### Soft Deletes

```
GET /api/users?include_trashed=1
GET /api/users?only_trashed=1
PATCH /api/users/{id}/restore     (restore soft deleted record)
DELETE /api/users/{id}/force      (permanently delete record)
```

When `softDeletes: true` is enabled, additional endpoints are automatically created:

- **Restore**: `PATCH /{resource}/{id}/restore` - Restore a soft deleted record
- **Force Delete**: `DELETE /{resource}/{id}/force` - Permanently delete a record

Example model with soft deletes:

```php
use Illuminate\Database\Eloquent\SoftDeletes;

#[API(
    softDeletes: true,
    // Other operations will include 'restore' and 'forceDelete'
)]
class User extends Model
{
    use SoftDeletes;
    // ...
}
```

## Available Configuration Options

| Parameter     | Type   | Default        | Description              |
| ------------- | ------ | -------------- | ------------------------ |
| `prefix`      | string | `'api'`        | Route prefix             |
| `name`        | string | auto-generated | Resource name for routes |
| `only`        | array  | all operations | Limit operations         |
| `except`      | array  | none           | Exclude operations       |
| `middleware`  | array  | none           | Apply middleware         |
| `paginated`   | bool   | `true`         | Enable pagination        |
| `perPage`     | int    | `15`           | Items per page           |
| `sortable`    | array  | none           | Sortable fields          |
| `filterable`  | array  | none           | Filterable fields        |
| `searchable`  | array  | none           | Searchable fields        |
| `softDeletes` | bool   | `false`        | Handle soft deletes      |
| `scoutSearch` | bool   | auto-detect    | Use Laravel Scout search |
| `validation`  | array  | none           | Validation rules         |

## Laravel Scout Integration

Volcanic automatically detects if your model uses Laravel Scout's `Searchable` trait and will use Scout for search operations when available. This provides powerful full-text search capabilities through various drivers like Algolia, Elasticsearch, or MeiliSearch.

### Automatic Scout Detection

```php
<?php

use Laravel\Scout\Searchable;
use Volcanic\Attributes\API;

#[API(
    searchable: ['name', 'content']  // Scout will be used automatically
)]
class Article extends Model
{
    use Searchable;  // Volcanic detects this trait

    protected $fillable = ['name', 'content', 'status'];

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'content' => $this->content,
        ];
    }
}
```

### Explicit Scout Control

You can explicitly enable or disable Scout search:

```php
// Force enable Scout search (even without Searchable trait)
#[API(
    searchable: ['name'],
    scoutSearch: true  // Explicitly enabled
)]
class Product extends Model
{
    // Scout will be used even without the trait
    // (will fail gracefully if Scout is not properly configured)
}

// Force disable Scout search (even with Searchable trait)
#[API(
    searchable: ['name'],
    scoutSearch: false  // Explicitly disabled
)]
class User extends Model
{
    use Searchable;  // Scout will NOT be used, falls back to database LIKE queries
}
```

### Scout Search Behavior

When Scout search is enabled:

1. **Search Query**: `GET /api/articles?search=laravel` uses Scout's full-text search
2. **Fallback**: If Scout fails or returns no results, the query gracefully handles empty results
3. **Performance**: Scout search is typically much faster than database LIKE queries for text search
4. **Relevance**: Scout provides better search relevance scoring than basic SQL LIKE queries

When Scout search is disabled or not available, Volcanic falls back to standard database `LIKE` queries across the specified searchable fields.

## Wildcard Field Support

For `sortable` and `filterable` fields, you can use the asterisk (`*`) wildcard to allow any field:

```php
#[API(
    sortable: ['*'],        // Allow sorting by any field on the model
    filterable: ['*'],      // Allow filtering by any field on the model
    searchable: ['name', 'content']  // Searchable fields remain explicit
)]
class FlexibleModel extends Model
{
    protected $fillable = ['name', 'content', 'status', 'category'];
}
```

### Wildcard Validation

When using wildcards, Volcanic validates that the requested field actually exists on the model:

- **Fillable Fields**: First checks if the field is in the model's `$fillable` array
- **Table Columns**: If not fillable, checks if the field exists as a database column
- **Error Handling**: Returns a `400 Bad Request` with a descriptive error message for invalid fields

```php
// These requests will work:
GET /api/flexible-models?sort_by=name&sort_direction=desc
GET /api/flexible-models?filter[status]=active

// This request will return an error:
GET /api/flexible-models?sort_by=nonexistent_field
// Response: 400 Bad Request
// "Field 'nonexistent_field' is not allowed for sorting..."
```

## Management Commands

Use the `volcanic` Artisan command to manage your API endpoints:

```bash
# List all models with API attribute
php artisan volcanic list

# Manually discover and register routes
php artisan volcanic discover

# Show all registered API routes
php artisan volcanic routes
```

## Configuration

Configure Volcanic in `config/volcanic.php`:

```php
return [
    'auto_discover_routes' => true,
    'default_api_prefix' => 'api',
    'default_per_page' => 15,
    'max_per_page' => 100,
    'global_middleware' => [
        'throttle:api',
    ],
];
```
