<?php

declare(strict_types=1);

namespace Volcanic\Enums;

enum PaginationType: string
{
    case LENGTH_AWARE = 'paginate';

    case SIMPLE = 'simplePaginate';

    case CURSOR = 'cursorPaginate';

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
            self::LENGTH_AWARE => 'Length-aware pagination with total count',
            self::SIMPLE => 'Simple pagination without total count',
            self::CURSOR => 'Cursor-based pagination for large datasets',
        };
    }

    /**
     * Get the default pagination type.
     */
    public static function default(): self
    {
        return self::LENGTH_AWARE;
    }

    /**
     * Create a PaginationType from a string value with fallback to default.
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom($value) ?? self::default();
    }
}
