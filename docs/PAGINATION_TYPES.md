# Pagination Types in Volcanic

Volcanic supports multiple pagination types to handle different use cases and performance requirements. This document explains how to configure and use each type.

## Available Pagination Types

### 1. Length-Aware Pagination (Default)

**Type**: `paginate`

This is the default pagination type that provides full pagination information including total record count.

```php
use Volcanic\Enums\PaginationType;

#[ApiResource(
    paginationType: PaginationType::PAGINATE, // Explicit enum usage (optional, this is the default)
    perPage: 15
)]
class Product extends Model
{
    // Your model code
}
```

**Response Structure:**

```json
{
    "data": [...],
    "links": {
        "first": "http://example.com/api/products?page=1",
        "last": "http://example.com/api/products?page=10",
        "prev": null,
        "next": "http://example.com/api/products?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 10,
        "per_page": 15,
        "to": 15,
        "total": 150
    }
}
```

**Use Case:** Best for smaller datasets where total count is needed for UI pagination controls.

### 2. Simple Pagination

**Type**: `simplePaginate`

Provides pagination without calculating the total record count, making it faster for large datasets.

```php
use Volcanic\Enums\PaginationType;

#[ApiResource(
    paginationType: PaginationType::SIMPLE_PAGINATE,
    perPage: 20
)]
class LogEntry extends Model
{
    // Your model code
}
```

**Response Structure:**

```json
{
    "data": [...],
    "links": {
        "first": "http://example.com/api/log-entries?page=1",
        "last": null,
        "prev": null,
        "next": "http://example.com/api/log-entries?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "per_page": 20,
        "to": 20
    }
}
```

**Use Case:** Ideal for large datasets where total count is not necessary, such as logs or feeds.

### 3. Cursor Pagination

**Type**: `cursorPaginate`

Uses cursor-based pagination for consistent results even when data is being inserted/deleted.

```php
use Volcanic\Enums\PaginationType;

#[ApiResource(
    paginationType: PaginationType::CURSOR_PAGINATE,
    perPage: 25
)]
class Message extends Model
{
    // Your model code
}
```

**Response Structure:**

```json
{
    "data": [...],
    "links": {
        "first": "http://example.com/api/messages?cursor=eyJpZCI6MX0",
        "last": "http://example.com/api/messages?cursor=eyJpZCI6MjV9",
        "prev": null,
        "next": "http://example.com/api/messages?cursor=eyJpZCI6MjZ9"
    },
    "meta": {
        "per_page": 25
    }
}
```

**Use Case:** Perfect for real-time feeds, messaging systems, or any dataset where consistency during pagination is crucial.

## Configuration

### Global Default Configuration

Set the default pagination type in your `config/volcanic.php` file using the PaginationType enum:

```php
use Volcanic\Enums\PaginationType;

return [
    // Other configuration...

    'default_pagination_type' => PaginationType::PAGINATE, // Type-safe enum usage
];
```

For backward compatibility, you can still use strings:

````php
return [
    // Other configuration...

    'default_pagination_type' => 'paginate', // Still supported but enum is preferred
];
```### Per-Model Configuration

Override the global default for specific models using the enum:

```php
use Volcanic\Enums\PaginationType;

#[ApiResource(
    paginationType: PaginationType::CURSOR_PAGINATE,
    perPage: 50
)]
class HighVolumeModel extends Model
{
    // Your model code
}
````

## Advanced Usage

### Custom Cursor Column

For cursor pagination, you can specify a custom column to use as the cursor:

```http
GET /api/messages?cursor_column=created_at
```

If the specified column doesn't exist, the pagination service will fall back to the model's primary key.

### Pagination Parameters

#### Standard Parameters

-   `page`: Page number (for paginate and simplePaginate)
-   `cursor`: Cursor value (for cursorPaginate)

#### Custom Parameters

-   `cursor_column`: Column to use for cursor pagination (defaults to model's primary key)

### Disabling Pagination

To disable pagination entirely for a model:

```php
#[ApiResource(
    paginate: false
)]
class StaticData extends Model
{
    // This will return all records without pagination
}
```

## Performance Considerations

### Length-Aware Pagination (`paginate`)

-   **Pros**: Full pagination information, familiar UI patterns
-   **Cons**: Slower on large datasets due to COUNT query
-   **Best for**: Small to medium datasets (< 100k records)

### Simple Pagination (`simplePaginate`)

-   **Pros**: Faster than length-aware, no COUNT query
-   **Cons**: No total count or last page information
-   **Best for**: Large datasets where total count isn't needed

### Cursor Pagination (`cursorPaginate`)

-   **Pros**: Consistent results, best performance on large datasets, handles real-time data well
-   **Cons**: More complex implementation, limited navigation options
-   **Best for**: Large datasets with frequent updates, real-time feeds

## Examples

### E-commerce Products (Length-Aware)

```php
use Volcanic\Enums\PaginationType;

#[ApiResource(
    paginationType: PaginationType::PAGINATE,
    perPage: 24 // Standard product grid
)]
class Product extends Model
{
    protected $fillable = ['name', 'price', 'category_id'];
}
```

### Application Logs (Simple)

```php
use Volcanic\Enums\PaginationType;

#[ApiResource(
    paginationType: PaginationType::SIMPLE_PAGINATE,
    perPage: 100 // Process logs in batches
)]
class ApplicationLog extends Model
{
    protected $fillable = ['level', 'message', 'context', 'created_at'];
}
```

### Chat Messages (Cursor)

```php
use Volcanic\Enums\PaginationType;

#[ApiResource(
    paginationType: PaginationType::CURSOR_PAGINATE,
    perPage: 50 // Smooth scrolling experience
)]
class ChatMessage extends Model
{
    protected $fillable = ['user_id', 'channel_id', 'content', 'sent_at'];
}
```

## Migration from Basic Pagination

If you're upgrading from a version that only supported basic pagination, your existing code will continue to work. The default pagination type is `paginate`, which maintains the same behavior.

To optimize performance, consider updating models with large datasets to use `simplePaginate` or `cursorPaginate`:

```php
// Before (still works)
#[ApiResource(paginate: true, perPage: 15)]

// After (optimized with enum)
use Volcanic\Enums\PaginationType;

#[ApiResource(paginationType: PaginationType::SIMPLE_PAGINATE, perPage: 15)]
```
