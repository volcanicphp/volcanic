<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Volcanic\Attributes\API;

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
    validation: [
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
        ]
    ]
)]
class Product extends Model
{
    use SoftDeletes, Searchable;  // Both traits auto-detected by Volcanic

    protected $fillable = [
        'name',
        'description', 
        'price',
        'category_id',
        'tags',
        'status'
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