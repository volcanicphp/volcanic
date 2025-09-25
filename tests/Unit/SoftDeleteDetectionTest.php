<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Volcanic\Attributes\ApiResource;
use Volcanic\Services\ApiResourceDiscoveryService;

// Mock model without SoftDeletes
#[ApiResource(prefix: 'test')]
class RegularModel extends Model
{
    protected $table = 'regular_models';
}

// Mock model with SoftDeletes
#[ApiResource(prefix: 'test')]
class SoftDeleteModel extends Model
{
    use SoftDeletes;

    protected $table = 'soft_delete_models';
}

// Mock model with SoftDeletes explicitly disabled in API attribute
#[ApiResource(prefix: 'test', softDeletes: false)]
class ExplicitlyDisabledSoftDeleteModel extends Model
{
    use SoftDeletes;

    protected $table = 'explicitly_disabled_models';
}

/**
 * Helper function to call protected methods for testing.
 */
function callProtectedMethod($object, $method, array $parameters = []): mixed
{
    $reflection = new ReflectionClass($object::class);
    $method = $reflection->getMethod($method);

    return $method->invokeArgs($object, $parameters);
}

test('uses soft deletes detects trait correctly', function (): void {
    $service = new ApiResourceDiscoveryService;

    // Test model without SoftDeletes
    $reflection = new ReflectionClass(RegularModel::class);
    expect(callProtectedMethod($service, 'usesSoftDeletes', [$reflection]))->toBeFalse();

    // Test model with SoftDeletes
    $reflection = new ReflectionClass(SoftDeleteModel::class);
    expect(callProtectedMethod($service, 'usesSoftDeletes', [$reflection]))->toBeTrue();
});

test('api attribute with soft deletes creates correct operations', function (): void {
    $service = new ApiResourceDiscoveryService;
    $reflection = new ReflectionClass(SoftDeleteModel::class);
    $attributes = $reflection->getAttributes(ApiResource::class);
    $apiAttribute = $attributes[0]->newInstance();

    // Simulate the automatic soft deletes detection
    if (! $apiAttribute->isSoftDeletesExplicitlySet() && callProtectedMethod($service, 'usesSoftDeletes', [$reflection])) {
        $apiAttribute = $apiAttribute->withSoftDeletes(true);
    }

    expect($apiAttribute->isSoftDeletesEnabled())->toBeTrue();
    $expectedOperations = ['index', 'show', 'store', 'update', 'destroy', 'restore', 'forceDelete'];
    expect($apiAttribute->getOperations())->toBe($expectedOperations);
});

test('api attribute without soft deletes has regular operations', function (): void {
    $service = new ApiResourceDiscoveryService;
    $reflection = new ReflectionClass(RegularModel::class);
    $attributes = $reflection->getAttributes(ApiResource::class);
    $apiAttribute = $attributes[0]->newInstance();

    // Simulate the automatic soft deletes detection (should not change anything)
    if (! $apiAttribute->isSoftDeletesExplicitlySet() && callProtectedMethod($service, 'usesSoftDeletes', [$reflection])) {
        $apiAttribute = $apiAttribute->withSoftDeletes(true);
    }

    expect($apiAttribute->isSoftDeletesEnabled())->toBeFalse();
    $expectedOperations = ['index', 'show', 'store', 'update', 'destroy'];
    expect($apiAttribute->getOperations())->toBe($expectedOperations);
});

test('explicit soft deletes false is respected', function (): void {
    $service = new ApiResourceDiscoveryService;
    $reflection = new ReflectionClass(ExplicitlyDisabledSoftDeleteModel::class);
    $attributes = $reflection->getAttributes(ApiResource::class);
    $apiAttribute = $attributes[0]->newInstance();

    // Even though the model uses SoftDeletes trait, the explicit false should be respected
    expect($apiAttribute->isSoftDeletesEnabled())->toBeFalse();
    expect($apiAttribute->isSoftDeletesExplicitlySet())->toBeTrue();

    // Simulate the automatic detection - it should NOT override explicit false
    if (! $apiAttribute->isSoftDeletesExplicitlySet() && callProtectedMethod($service, 'usesSoftDeletes', [$reflection])) {
        $apiAttribute = $apiAttribute->withSoftDeletes(true);
    }

    // Should remain false because it was explicitly set
    expect($apiAttribute->isSoftDeletesEnabled())->toBeFalse();
});

test('with soft deletes creates new instance', function (): void {
    $original = new ApiResource(prefix: 'test', softDeletes: false);
    $withSoftDeletes = $original->withSoftDeletes(true);

    expect($original)->not()->toBe($withSoftDeletes);
    expect($original->isSoftDeletesEnabled())->toBeFalse();
    expect($withSoftDeletes->isSoftDeletesEnabled())->toBeTrue();
    expect($withSoftDeletes->getPrefix())->toBe('api/test');
});
