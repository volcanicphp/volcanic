# Scout Search Integration Examples

This document shows practical examples of using Laravel Scout with Volcanic.

## API Endpoints with Scout Search

When you have a model with the `Searchable` trait:

```php
#[API(searchable: ['name', 'content'])]
class Article extends Model
{
    use Searchable;
    // ...
}
```

The following endpoint automatically uses Scout for full-text search:

```bash
# Uses Scout search (if available) for better relevance and performance
GET /api/articles?search=laravel+framework

# Falls back to database LIKE queries if Scout is not available
GET /api/articles?search=php
```

## Explicit Scout Control

```php
// Force enable Scout (useful for testing or specific requirements)
#[API(
    searchable: ['title', 'body'],
    scoutSearch: true  // Always use Scout, even without Searchable trait
)]
class BlogPost extends Model
{
    // Will attempt Scout search even without the trait
}

// Force disable Scout (useful when you want database search instead)
#[API(
    searchable: ['name', 'email'],
    scoutSearch: false  // Never use Scout, always use database LIKE
)]
class User extends Model
{
    use Searchable;  // Trait present but Scout disabled via config
}
```

## Combined with Wildcard Fields

```php
#[API(
    sortable: ['*'],           // Any field can be sorted
    filterable: ['*'],         // Any field can be filtered  
    searchable: ['name', 'content'],  // Scout search on specific fields
    scoutSearch: null          // Auto-detect Scout usage
)]
class Product extends Model
{
    use Searchable;
    
    protected $fillable = ['name', 'content', 'price', 'category'];
}
```

API Usage:
```bash
# Scout search on name/content fields
GET /api/products?search=smartphone

# Wildcard filtering (validates field exists)
GET /api/products?filter[price:gte]=100&filter[category]=electronics

# Wildcard sorting (validates field exists) 
GET /api/products?sort_by=price&sort_direction=desc

# Error for invalid field
GET /api/products?sort_by=invalid_field
# Returns: 400 Bad Request - Field 'invalid_field' is not allowed for sorting...
```

## Scout Configuration

Make sure to configure Scout in your Laravel application:

```bash
# Install Scout
composer require laravel/scout

# Publish configuration
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"

# Configure your preferred driver in config/scout.php
```

With a driver like Algolia:
```bash
composer require algolia/algoliasearch-client-php
```

Then in `.env`:
```env
SCOUT_DRIVER=algolia
ALGOLIA_APP_ID=your_app_id
ALGOLIA_SECRET=your_secret
```