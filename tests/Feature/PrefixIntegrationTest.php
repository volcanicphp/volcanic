<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Volcanic\Attributes\ApiResource;
use Volcanic\Services\ApiResourceDiscoveryService;

#[ApiResource(name: 'example-items')]
class DefaultPrefixModel extends Model
{
    protected $table = 'default_prefix_models';

    protected $fillable = ['name'];
}

#[ApiResource(prefix: 'v1', name: 'v1-items')]
class V1PrefixModel extends Model
{
    protected $table = 'v1_prefix_models';

    protected $fillable = ['name'];
}

#[ApiResource(prefix: 'api/v2', name: 'v2-items')]
class V2PrefixModel extends Model
{
    protected $table = 'v2_prefix_models';

    protected $fillable = ['name'];
}

uses(RefreshDatabase::class);

function createTestTables(): void
{
    app('db')->connection()->getSchemaBuilder()->create('default_prefix_models', function ($table): void {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    app('db')->connection()->getSchemaBuilder()->create('v1_prefix_models', function ($table): void {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });

    app('db')->connection()->getSchemaBuilder()->create('v2_prefix_models', function ($table): void {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });
}

beforeEach(function (): void {
    // Create the test tables
    createTestTables();

    // Register the routes
    $apiDiscovery = new ApiResourceDiscoveryService;
    $apiDiscovery->registerModelRoutes(DefaultPrefixModel::class);
    $apiDiscovery->registerModelRoutes(V1PrefixModel::class);
    $apiDiscovery->registerModelRoutes(V2PrefixModel::class);
});

test('default prefix creates api routes', function (): void {
    $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route): bool => str_contains((string) $route->uri(), 'api/example-items'));

    expect($routes)->not()->toBeEmpty();

    // Check that we have the expected routes under /api/
    $uris = $routes->pluck('uri')->toArray();
    expect($uris)->toContain('api/example-items');
    expect($uris)->toContain('api/example-items/{id}');
});

test('v1 prefix creates api v1 routes', function (): void {
    $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route): bool => str_contains((string) $route->uri(), 'api/v1/v1-items'));

    expect($routes)->not()->toBeEmpty();

    // Check that we have the expected routes under /api/v1/
    $uris = $routes->pluck('uri')->toArray();
    expect($uris)->toContain('api/v1/v1-items');
    expect($uris)->toContain('api/v1/v1-items/{id}');
});

test('api v2 prefix preserves api v2 routes', function (): void {
    $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route): bool => str_contains((string) $route->uri(), 'api/v2/v2-items'));

    expect($routes)->not()->toBeEmpty();

    // Check that we have the expected routes under /api/v2/
    $uris = $routes->pluck('uri')->toArray();
    expect($uris)->toContain('api/v2/v2-items');
    expect($uris)->toContain('api/v2/v2-items/{id}');
});
