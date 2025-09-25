<?php

declare(strict_types=1);

use Volcanic\Enums\PaginationType;

it('has correct pagination type values', function (): void {
    expect(PaginationType::LENGTH_AWARE->value)->toBe('paginate');
    expect(PaginationType::SIMPLE->value)->toBe('simplePaginate');
    expect(PaginationType::CURSOR->value)->toBe('cursorPaginate');
});

it('can get all pagination type values', function (): void {
    $values = PaginationType::values();

    expect($values)->toBeArray();
    expect($values)->toContain('paginate');
    expect($values)->toContain('simplePaginate');
    expect($values)->toContain('cursorPaginate');
    expect(count($values))->toBe(3);
});

it('provides descriptions for pagination types', function (): void {
    expect(PaginationType::LENGTH_AWARE->description())->toBe('Length-aware pagination with total count');
    expect(PaginationType::SIMPLE->description())->toBe('Simple pagination without total count');
    expect(PaginationType::CURSOR->description())->toBe('Cursor-based pagination for large datasets');
});

it('has correct default pagination type', function (): void {
    expect(PaginationType::default())->toBe(PaginationType::LENGTH_AWARE);
});

it('can create pagination type from string', function (): void {
    expect(PaginationType::tryFrom('paginate'))->toBe(PaginationType::LENGTH_AWARE);
    expect(PaginationType::tryFrom('simplePaginate'))->toBe(PaginationType::SIMPLE);
    expect(PaginationType::tryFrom('cursorPaginate'))->toBe(PaginationType::CURSOR);
    expect(PaginationType::tryFrom('invalid'))->toBeNull();
});

it('can create pagination type from string with fallback', function (): void {
    expect(PaginationType::fromString('paginate'))->toBe(PaginationType::LENGTH_AWARE);
    expect(PaginationType::fromString('simplePaginate'))->toBe(PaginationType::SIMPLE);
    expect(PaginationType::fromString('cursorPaginate'))->toBe(PaginationType::CURSOR);
    expect(PaginationType::fromString('invalid'))->toBe(PaginationType::LENGTH_AWARE);
});
