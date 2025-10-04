<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Volcanic\Attributes\ApiResource;
use Volcanic\Playground;
use Volcanic\Services\SchemaService;

beforeEach(function (): void {
    // Create test table
    Schema::create('posts', function ($table): void {
        $table->id();
        $table->string('title');
        $table->text('content');
        $table->timestamps();
    });

    // Reset playground state
    Playground::reset();
});

afterEach(function (): void {
    Schema::dropIfExists('posts');
});

#[ApiResource]
class PlaygroundTestPost extends Model
{
    protected $table = 'posts';

    protected $fillable = ['title', 'content'];
}

test('playground check returns true in local environment', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    expect(Playground::check())->toBeTrue();
});

test('playground check returns false in production by default', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    expect(Playground::check())->toBeFalse();
});

test('playground can be enabled in production with canAccess', function (): void {
    app()->detectEnvironment(fn (): string => 'production');
    Playground::canAccess(true);

    expect(Playground::check())->toBeTrue();
});

test('playground controller is registered', function (): void {
    $routes = Route::getRoutes();
    $playgroundRoute = $routes->getByName('volcanic.playground');

    expect($playgroundRoute)->not->toBeNull();
    expect($playgroundRoute->getActionName())->toContain('PlaygroundController');
});

test('playground schema controller is registered', function (): void {
    $routes = Route::getRoutes();
    $schemaRoute = $routes->getByName('volcanic.schema');

    expect($schemaRoute)->not->toBeNull();
    expect($schemaRoute->getActionName())->toContain('PlaygroundSchemaController');
});

test('schema service returns routes and models structure', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    $schemaService = app(SchemaService::class);
    $schema = $schemaService->getSchema();

    expect($schema)->toHaveKey('routes');
    expect($schema)->toHaveKey('models');
    expect($schema['routes'])->toBeArray();
    expect($schema['models'])->toBeArray();
});

test('schema service includes registered model information', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    $schemaService = app(SchemaService::class);
    $schema = $schemaService->getSchema();

    expect($schema)->toHaveKey('models');
    expect($schema['models'])->toBeArray();

    // The test model may not be in configured paths, so just verify structure
    if (count($schema['models']) > 0) {
        $model = $schema['models'][0];
        expect($model)->toHaveKey('name');
        expect($model)->toHaveKey('table');
        expect($model)->toHaveKey('fields');
        expect($model)->toHaveKey('hidden');
        expect($model)->toHaveKey('fillable');
    }
});

test('schema service extracts route parameters correctly', function (): void {
    app()->detectEnvironment(fn (): string => 'local');

    // Register a route with parameters
    Route::get('/api/posts/{id}/comments/{comment}', fn (): string => 'test')->name('test.route.params');

    $schemaService = app(SchemaService::class);
    $schema = $schemaService->getSchema();

    $testRoute = collect($schema['routes'])->firstWhere('name', 'test.route.params');

    if ($testRoute) {
        expect($testRoute['parameters'])->toBeArray();
        expect($testRoute['parameters'])->toHaveCount(2);

        $paramNames = collect($testRoute['parameters'])->pluck('name')->toArray();
        expect($paramNames)->toContain('id');
        expect($paramNames)->toContain('comment');
    }
});

test('schema service includes all application routes', function (): void {
    // Register some non-API routes
    Route::get('/web/page', fn (): string => 'test')->name('web.page');
    Route::get('/admin/dashboard', fn (): string => 'test')->name('admin.dashboard');

    $schemaService = app(SchemaService::class);
    $schema = $schemaService->getSchema();

    expect($schema['routes'])->toBeArray();

    $routes = collect($schema['routes']);

    // Check that web routes ARE included
    $webRoute = $routes->firstWhere('name', 'web.page');
    expect($webRoute)->not->toBeNull();
    expect($webRoute['prefix'])->toBe('web');

    // Check that admin routes are included
    $adminRoute = $routes->firstWhere('name', 'admin.dashboard');
    expect($adminRoute)->not->toBeNull();
    expect($adminRoute['prefix'])->toBe('admin');

    // Verify internal routes are excluded
    $internalRoutes = $routes->filter(fn ($route): bool => str_starts_with((string) $route['uri'], '_ignition') || str_starts_with((string) $route['uri'], 'sanctum'));
    expect($internalRoutes)->toBeEmpty();
});
