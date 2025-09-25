<?php

declare(strict_types=1);

namespace Volcanic\Examples;

use Illuminate\Database\Eloquent\Model;
use Volcanic\Attributes\ApiResource;
use Volcanic\Enums\PaginationType;

/**
 * Example: E-commerce Product with length-aware pagination
 * Best for: Product catalogs where users need page numbers and total count
 */
#[ApiResource(
    paginationType: PaginationType::LENGTH_AWARE,
    perPage: 24, // Standard product grid (4x6)
    sortable: ['name', 'price', 'created_at'],
    filterable: ['category_id', 'brand_id', 'in_stock'],
    searchable: ['name', 'description']
)]
class ProductExample extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'category_id',
        'brand_id',
        'in_stock',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'in_stock' => 'boolean',
    ];
}

/**
 * Example: Application Log with simple pagination
 * Best for: Log files where total count is not needed and performance is key
 */
#[ApiResource(
    paginationType: PaginationType::SIMPLE,
    perPage: 100,
    sortable: ['created_at', 'level'],
    filterable: ['level', 'context'],
    searchable: ['message']
)]
class LogEntryExample extends Model
{
    protected $fillable = [
        'level',
        'message',
        'context',
        'user_id',
        'ip_address',
    ];

    protected $casts = [
        'context' => 'json',
    ];
}

/**
 * Example: Chat Message with cursor pagination
 * Best for: Real-time feeds where consistency during pagination is crucial
 */
#[ApiResource(
    paginationType: PaginationType::CURSOR,
    perPage: 50,
    sortable: ['sent_at', 'id'],
    filterable: ['user_id', 'channel_id'],
    searchable: ['content']
)]
class MessageExample extends Model
{
    protected $fillable = [
        'user_id',
        'channel_id',
        'content',
        'message_type',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}

/**
 * Example: Static data without pagination
 * Best for: Small datasets that should be returned in full
 */
#[ApiResource(
    paginate: false,
    sortable: ['name', 'sort_order'],
    filterable: ['active'],
    searchable: ['name', 'description']
)]
class CategoryExample extends Model
{
    protected $fillable = [
        'name',
        'description',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
