<?php

declare(strict_types=1);

namespace Volcanic\Enums;

enum PaginationType: string
{
    case PAGINATE = 'paginate';
    case SIMPLE_PAGINATE = 'simplePaginate';
    case CURSOR_PAGINATE = 'cursorPaginate';

    /**
     * Get all available pagination type values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a description for the pagination type.
     */
    public function description(): string
    {
        return match ($this) {
            self::PAGINATE => 'Length-aware pagination with total count',
            self::SIMPLE_PAGINATE => 'Simple pagination without total count',
            self::CURSOR_PAGINATE => 'Cursor-based pagination for large datasets',
        };
    }

    /**
     * Get the default pagination type.
     */
    public static function default(): self
    {
        return self::PAGINATE;
    }

    /**
     * Create a PaginationType from a string value with fallback to default.
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? self::default();
    }
}
