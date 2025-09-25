<?php

declare(strict_types=1);

use Volcanic\Attributes\ApiResource;

test('api attribute includes soft delete operations when enabled', function (): void {
    $apiWithSoftDeletes = new ApiResource(softDeletes: true);

    $operations = $apiWithSoftDeletes->getOperations();

    // Should include standard operations
    expect($operations)->toContain('index');
    expect($operations)->toContain('show');
    expect($operations)->toContain('store');
    expect($operations)->toContain('update');
    expect($operations)->toContain('destroy');

    // Should include soft delete operations
    expect($operations)->toContain('restore');
    expect($operations)->toContain('forceDelete');
});

test('api attribute excludes soft delete operations when disabled', function (): void {
    $apiWithoutSoftDeletes = new ApiResource(softDeletes: false);

    $operations = $apiWithoutSoftDeletes->getOperations();

    // Should include standard operations
    expect($operations)->toContain('index');
    expect($operations)->toContain('destroy');

    // Should NOT include soft delete operations
    expect($operations)->not()->toContain('restore');
    expect($operations)->not()->toContain('forceDelete');
});

test('api attribute respects only filter with soft deletes', function (): void {
    $api = new ApiResource(
        only: ['index', 'restore'],
        softDeletes: true
    );

    $operations = $api->getOperations();

    // Should only include specified operations
    expect($operations)->toEqualCanonicalizing(['index', 'restore']);
    expect($operations)->not()->toContain('forceDelete');
    expect($operations)->not()->toContain('store');
});

test('api attribute respects except filter with soft deletes', function (): void {
    $api = new ApiResource(
        except: ['forceDelete', 'destroy'],
        softDeletes: true
    );

    $operations = $api->getOperations();

    // Should include all operations except the excluded ones
    expect($operations)->toContain('index');
    expect($operations)->toContain('restore');
    expect($operations)->not()->toContain('forceDelete');
    expect($operations)->not()->toContain('destroy');
});

test('allows operation works with soft delete operations', function (): void {
    $api = new ApiResource(
        only: ['restore', 'forceDelete'],
        softDeletes: true
    );

    expect($api->allowsOperation('restore'))->toBeTrue();
    expect($api->allowsOperation('forceDelete'))->toBeTrue();
    expect($api->allowsOperation('index'))->toBeFalse();
    expect($api->allowsOperation('store'))->toBeFalse();
});
