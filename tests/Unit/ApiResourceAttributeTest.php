<?php

declare(strict_types=1);

use Volcanic\Attributes\ApiResource;
use Volcanic\Enums\PaginationType;

test('api attribute can be instantiated', function (): void {
    $api = new ApiResource;

    expect($api)->toBeInstanceOf(ApiResource::class);
    expect($api->getPrefix())->toBe('api');
    expect($api->paginate)->toBeTrue();
});

test('api attribute with custom configuration', function (): void {
    $api = new ApiResource(
        prefix: 'v1',
        name: 'custom-users',
        only: ['index', 'show'],
        middleware: ['auth:sanctum'],
        paginate: false,
        perPage: 25
    );

    expect($api->getPrefix())->toBe('api/v1');
    expect($api->getName())->toBe('custom-users');
    expect($api->getOperations())->toBe(['index', 'show']);
    expect($api->middleware)->toBe(['auth:sanctum']);
    expect($api->paginate)->toBeFalse();
    expect($api->perPage)->toBe(25);
});

test('api attribute operations with except', function (): void {
    $api = new ApiResource(except: ['destroy']);

    $expectedOperations = ['index', 'show', 'store', 'update'];
    expect($api->getOperations())->toBe($expectedOperations);
});

test('api attribute allows operation', function (): void {
    $api = new ApiResource(only: ['index', 'show']);

    expect($api->allowsOperation('index'))->toBeTrue();
    expect($api->allowsOperation('show'))->toBeTrue();
    expect($api->allowsOperation('store'))->toBeFalse();
    expect($api->allowsOperation('destroy'))->toBeFalse();
});

test('api attribute query features', function (): void {
    $api = new ApiResource(
        sortable: ['name', 'created_at'],
        filterable: ['status', 'category'],
        searchable: ['name', 'description']
    );

    $features = $api->getQueryFeatures();

    expect($features['sortable'])->toBe(['name', 'created_at']);
    expect($features['filterable'])->toBe(['status', 'category']);
    expect($features['searchable'])->toBe(['name', 'description']);
});

test('api attribute scout search configuration', function (): void {
    // Test explicitly enabled
    $api = new ApiResource(scoutSearch: true);
    expect($api->isScoutSearchEnabled())->toBeTrue();
    expect($api->isScoutSearchExplicitlySet())->toBeTrue();

    // Test explicitly disabled
    $api = new ApiResource(scoutSearch: false);
    expect($api->isScoutSearchEnabled())->toBeFalse();
    expect($api->isScoutSearchExplicitlySet())->toBeTrue();

    // Test default (not set)
    $api = new ApiResource;
    expect($api->isScoutSearchEnabled())->toBeFalse();
    expect($api->isScoutSearchExplicitlySet())->toBeFalse();
});

test('api attribute pagination type configuration', function (): void {
    // Test default pagination type
    $api = new ApiResource;
    expect($api->getPaginationType())->toBe(PaginationType::PAGINATE);

    // Test explicit pagination type
    $api = new ApiResource(paginationType: PaginationType::SIMPLE_PAGINATE);
    expect($api->getPaginationType())->toBe(PaginationType::SIMPLE_PAGINATE);

    $api = new ApiResource(paginationType: PaginationType::CURSOR_PAGINATE);
    expect($api->getPaginationType())->toBe(PaginationType::CURSOR_PAGINATE);
});

test('api attribute pagination type validation', function (): void {
    // Test invalid pagination type - this test is no longer relevant since we use enum
    // but we can test that config fallback works
    $api = new ApiResource;
    expect($api->getPaginationType())->toBe(PaginationType::PAGINATE);
});

test('api attribute pagination settings', function (): void {
    $api = new ApiResource(
        paginate: true,
        paginationType: PaginationType::SIMPLE_PAGINATE,
        perPage: 25
    );

    $settings = $api->getPaginationSettings();

    expect($settings)->toHaveKeys(['enabled', 'type', 'per_page']);
    expect($settings['enabled'])->toBeTrue();
    expect($settings['type'])->toBe(PaginationType::SIMPLE_PAGINATE);
    expect($settings['per_page'])->toBe(25);
});

test('api attribute with soft deletes maintains pagination type', function (): void {
    $api = new ApiResource(
        paginationType: PaginationType::CURSOR_PAGINATE,
        perPage: 50
    );

    $apiWithSoftDeletes = $api->withSoftDeletes();

    expect($apiWithSoftDeletes->getPaginationType())->toBe(PaginationType::CURSOR_PAGINATE);
    expect($apiWithSoftDeletes->getPerPage())->toBe(50);
    expect($apiWithSoftDeletes->isSoftDeletesEnabled())->toBeTrue();
});
