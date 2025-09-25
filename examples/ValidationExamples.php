<?php

declare(strict_types=1);

namespace App\Models;

use App\Http\Requests\ProductRequest;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Volcanic\Attributes\ApiResource;

/**
 * This example shows different ways to configure validation rules in the ApiResource attribute:
 *
 * 1. Single FormRequest for all operations
 * 2. Per-operation FormRequest classes
 * 3. Mixed FormRequest and array rules
 * 4. Traditional array rules (backward compatibility)
 */

// Example 1: Single FormRequest for both create and update
#[ApiResource(
    prefix: 'v1',
    name: 'products-single-request',
    rules: ProductRequest::class  // Applies to both store and update
)]
class ProductWithSingleRequest extends Model
{
    use Searchable, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'name', 'description', 'price', 'category_id', 'tags', 'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'tags' => 'array',
    ];
}

// Example 2: Per-operation FormRequest classes
#[ApiResource(
    prefix: 'v1',
    name: 'products-per-operation',
    rules: [
        'store' => ProductStoreRequest::class,
        'update' => ProductUpdateRequest::class,
    ]
)]
class ProductWithPerOperationRequests extends Model
{
    use Searchable, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'name', 'description', 'price', 'category_id', 'tags', 'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'tags' => 'array',
    ];
}

// Example 3: Mixed FormRequest and array rules
#[ApiResource(
    prefix: 'v1',
    name: 'products-mixed',
    rules: [
        'store' => ProductStoreRequest::class,  // Use FormRequest for store
        'update' => [                            // Use array rules for update
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'category_id' => 'sometimes|exists:categories,id',
        ],
        'destroy' => [],  // No validation for delete
    ]
)]
class ProductWithMixedValidation extends Model
{
    use Searchable, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'name', 'description', 'price', 'category_id', 'tags', 'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'tags' => 'array',
    ];
}

// Example 4: Traditional array rules (backward compatibility maintained)
#[ApiResource(
    prefix: 'v1',
    name: 'products-traditional',
    sortable: ['*'],
    filterable: ['*'],
    searchable: ['name', 'description', 'tags'],
    scoutSearch: null,
    softDeletes: null,
    paginate: true,
    perPage: 20,
    middleware: ['auth:sanctum'],
    rules: [
        'store' => [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
        ],
        'update' => [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'category_id' => 'sometimes|exists:categories,id',
        ],
    ]
)]
class ProductWithTraditionalValidation extends Model
{
    use Searchable, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'name', 'description', 'price', 'category_id', 'tags', 'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'tags' => 'array',
    ];

    /**
     * Configure Scout searchable data.
     */
    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'tags' => $this->tags,
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

// Example 5: No validation (empty rules)
#[ApiResource(
    prefix: 'v1',
    name: 'products-no-validation'
    // No rules property = no validation
)]
class ProductWithoutValidation extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'name', 'description', 'price', 'category_id', 'tags', 'status',
    ];
}
