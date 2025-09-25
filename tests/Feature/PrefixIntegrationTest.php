<?php

declare(strict_types=1);

namespace Volcanic\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Override;
use Volcanic\Attributes\ApiResource;
use Volcanic\Services\ApiResourceDiscoveryService;
use Volcanic\Tests\TestCase;

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

class PrefixIntegrationTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Create the test tables
        $this->createTestTables();

        // Register the routes
        $apiDiscovery = new ApiResourceDiscoveryService;
        $apiDiscovery->registerModelRoutes(DefaultPrefixModel::class);
        $apiDiscovery->registerModelRoutes(V1PrefixModel::class);
        $apiDiscovery->registerModelRoutes(V2PrefixModel::class);
    }

    private function createTestTables(): void
    {
        $this->app['db']->connection()->getSchemaBuilder()->create('default_prefix_models', function ($table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('v1_prefix_models', function ($table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $this->app['db']->connection()->getSchemaBuilder()->create('v2_prefix_models', function ($table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    public function test_default_prefix_creates_api_routes(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route): bool => str_contains((string) $route->uri(), 'api/example-items'));

        $this->assertNotEmpty($routes);

        // Check that we have the expected routes under /api/
        $uris = $routes->pluck('uri')->toArray();
        $this->assertContains('api/example-items', $uris);
        $this->assertContains('api/example-items/{id}', $uris);
    }

    public function test_v1_prefix_creates_api_v1_routes(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route): bool => str_contains((string) $route->uri(), 'api/v1/v1-items'));

        $this->assertNotEmpty($routes);

        // Check that we have the expected routes under /api/v1/
        $uris = $routes->pluck('uri')->toArray();
        $this->assertContains('api/v1/v1-items', $uris);
        $this->assertContains('api/v1/v1-items/{id}', $uris);
    }

    public function test_api_v2_prefix_preserves_api_v2_routes(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route): bool => str_contains((string) $route->uri(), 'api/v2/v2-items'));

        $this->assertNotEmpty($routes);

        // Check that we have the expected routes under /api/v2/
        $uris = $routes->pluck('uri')->toArray();
        $this->assertContains('api/v2/v2-items', $uris);
        $this->assertContains('api/v2/v2-items/{id}', $uris);
    }
}
