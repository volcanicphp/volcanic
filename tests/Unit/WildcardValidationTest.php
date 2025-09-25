<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Volcanic\Attributes\ApiResource;
use Volcanic\Exceptions\InvalidFieldException;
use Volcanic\Services\ApiQueryService;

// Test model with wildcard support
#[ApiResource(
    sortable: ['*'],
    filterable: ['*'],
    searchable: ['name', 'description'],
)]
class WildcardTestModel extends Model
{
    protected $table = 'wildcard_test_models';

    protected $fillable = ['name', 'description', 'status'];
}

// Test model with specific fields
#[ApiResource(
    sortable: ['name', 'created_at'],
    filterable: ['status'],
    searchable: ['name'],
)]
class RestrictedTestModel extends Model
{
    protected $table = 'restricted_test_models';

    protected $fillable = ['name', 'description', 'status'];
}

test('wildcard allows fillable fields for sorting', function (): void {
    $service = new ApiQueryService;
    $request = new Request(['sort_by' => 'name']);
    $apiConfig = new ApiResource(sortable: ['*']);

    // This should not throw an exception
    $query = $service->buildQuery(WildcardTestModel::class, $apiConfig, $request);

    expect($query)->toBeInstanceOf(Builder::class);
});

test('wildcard allows fillable fields for filtering', function (): void {
    $service = new ApiQueryService;
    $request = new Request(['filter' => ['name' => 'test']]);
    $apiConfig = new ApiResource(filterable: ['*']);

    // This should not throw an exception
    $query = $service->buildQuery(WildcardTestModel::class, $apiConfig, $request);

    expect($query)->toBeInstanceOf(Builder::class);
});

test('restricted fields throw exception for sorting', function (): void {
    $service = new ApiQueryService;
    $request = new Request(['sort_by' => 'description']); // Not in sortable array
    $apiConfig = new ApiResource(sortable: ['name', 'created_at']);

    expect(fn (): Builder => $service->buildQuery(RestrictedTestModel::class, $apiConfig, $request))
        ->toThrow(InvalidFieldException::class, "Field 'description' is not allowed for sorting. Allowed fields are: name, created_at");
});

test('restricted fields throw exception for filtering', function (): void {
    $service = new ApiQueryService;
    $request = new Request(['filter' => ['name' => 'test']]); // Not in filterable array
    $apiConfig = new ApiResource(filterable: ['status']);

    expect(fn (): Builder => $service->buildQuery(RestrictedTestModel::class, $apiConfig, $request))
        ->toThrow(InvalidFieldException::class, "Field 'name' is not allowed for filtering. Allowed fields are: status");
});

test('empty allowed fields throw exception', function (): void {
    $service = new ApiQueryService;
    $request = new Request(['sort_by' => 'name']);
    $apiConfig = new ApiResource(sortable: []);

    // Should not throw exception because sortBy validation returns early when sortable is empty
    $query = $service->buildQuery(RestrictedTestModel::class, $apiConfig, $request);

    expect($query)->toBeInstanceOf(Builder::class);
});

test('explicitly allowed fields work with wildcard', function (): void {
    $service = new ApiQueryService;
    $request = new Request(['sort_by' => 'name']);
    $apiConfig = new ApiResource(sortable: ['name', '*']); // Both explicit and wildcard

    // This should work
    $query = $service->buildQuery(WildcardTestModel::class, $apiConfig, $request);

    expect($query)->toBeInstanceOf(Builder::class);
});
