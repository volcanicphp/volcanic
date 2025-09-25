<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Volcanic\Attributes\ApiResource;
use Volcanic\Services\ApiResourceDiscoveryService;

// Mock model for testing
#[ApiResource(
    prefix: 'test',
    name: 'test-items',
    sortable: ['name'],
    filterable: ['status'],
    searchable: ['name', 'description'],
    rules: [
        'store' => [
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ],
    ]
)]
class TestModel extends Model
{
    protected $table = 'test_models';

    protected $fillable = ['name', 'status', 'description'];
}

test('api discovery service finds models with api attribute', function (): void {
    $service = new ApiResourceDiscoveryService;

    // Create a reflection to test the discovery method
    $discoveredModels = $service->discoverModelsWithApiAttribute();

    // The method should return an array
    expect($discoveredModels)->toBeArray();
});

test('api attribute configuration is applied correctly', function (): void {
    $reflection = new ReflectionClass(TestModel::class);
    $attributes = $reflection->getAttributes(ApiResource::class);

    expect($attributes)->not()->toBeEmpty();

    $apiAttribute = $attributes[0]->newInstance();

    expect($apiAttribute->getPrefix())->toBe('api/test');
    expect($apiAttribute->getName())->toBe('test-items');
    expect($apiAttribute->sortable)->toBe(['name']);
    expect($apiAttribute->filterable)->toBe(['status']);
    expect($apiAttribute->searchable)->toBe(['name', 'description']);

    $validationRules = $apiAttribute->getValidationRules();
    expect($validationRules)->toHaveKey('store');
    expect($validationRules['store'])->toHaveKey('name');
});

test('api operations can be filtered', function (): void {
    $apiWithOnly = new ApiResource(only: ['index', 'show']);
    expect($apiWithOnly->getOperations())->toBe(['index', 'show']);

    $apiWithExcept = new ApiResource(except: ['destroy']);
    $expectedOperations = ['index', 'show', 'store', 'update'];
    expect($apiWithExcept->getOperations())->toBe($expectedOperations);
});

test('pagination settings are configured correctly', function (): void {
    $apiWithPagination = new ApiResource(paginate: true, perPage: 25);
    $paginationSettings = $apiWithPagination->getPaginationSettings();

    expect($paginationSettings['enabled'])->toBeTrue();
    expect($paginationSettings['per_page'])->toBe(25);

    $apiWithoutPagination = new ApiResource(paginate: false);
    $paginationSettings = $apiWithoutPagination->getPaginationSettings();

    expect($paginationSettings['enabled'])->toBeFalse();
});

test('query features are configured correctly', function (): void {
    $api = new ApiResource(
        sortable: ['name', 'created_at'],
        filterable: ['status', 'category'],
        searchable: ['name', 'content']
    );

    $queryFeatures = $api->getQueryFeatures();

    expect($queryFeatures['sortable'])->toBe(['name', 'created_at']);
    expect($queryFeatures['filterable'])->toBe(['status', 'category']);
    expect($queryFeatures['searchable'])->toBe(['name', 'content']);
});
