<?php

declare(strict_types=1);

namespace App\Models;

use App\Http\Requests\ProductRequest;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Volcanic\Attributes\API;

/**
 * Advanced Product Model demonstrating all validation approaches with the Volcanic API attribute.
 *
 * This example shows how you can configure validation in several ways:
 *
 * 1. Single FormRequest class (applies to both create and update):
 *    rules: ProductRequest::class
 *
 * 2. Per-operation FormRequest classes:
 *    rules: [
 *        'store' => ProductStoreRequest::class,
 *        'update' => ProductUpdateRequest::class,
 *    ]
 *
 * 3. Mixed FormRequest and array rules:
 *    rules: [
 *        'store' => ProductStoreRequest::class,
 *        'update' => ['name' => 'sometimes|string|max:255'],
 *    ]
 *
 * 4. Traditional array rules (backward compatibility):
 *    rules: [
 *        'store' => ['name' => 'required|string|max:255'],
 *        'update' => ['name' => 'sometimes|string|max:255'],
 *    ]
 */
#[API(
    prefix: 'v1',
    name: 'products',
    sortable: ['*'],           // Allow sorting by any field (with validation)
    filterable: ['*'],         // Allow filtering by any field (with validation)
    searchable: ['name', 'description', 'tags'],  // Scout will auto-detect and use full-text search
    scoutSearch: null,         // null = auto-detect (default), true = force enable, false = force disable
    softDeletes: null,         // null = auto-detect (default), true = force enable, false = force disable
    paginate: true,
    perPage: 20,
    middleware: ['auth:sanctum'],

    // Option 1: Single FormRequest for all operations
    // rules: ProductRequest::class,

    // Option 2: Per-operation FormRequest classes
    rules: [
        'store' => ProductStoreRequest::class,
        'update' => ProductUpdateRequest::class,
    ]

    // Option 3: Traditional array rules (still supported)
    // rules: [
    //     'store' => [
    //         'name' => 'required|string|max:255',
    //         'description' => 'required|string',
    //         'price' => 'required|numeric|min:0',
    //         'category_id' => 'required|exists:categories,id',
    //     ],
    //     'update' => [
    //         'name' => 'sometimes|string|max:255',
    //         'description' => 'sometimes|string',
    //         'price' => 'sometimes|numeric|min:0',
    //         'category_id' => 'sometimes|exists:categories,id',
    //     ],
    // ]
)]
class Product extends Model
{
    use Searchable, SoftDeletes;  // Both traits auto-detected by Volcanic

    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
        'tags',
        'status',
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
