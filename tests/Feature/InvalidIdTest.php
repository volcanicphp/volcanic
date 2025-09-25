<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Volcanic\Attributes\ApiResource;
use Volcanic\Services\ApiResourceDiscoveryService;

// Test model for invalid ID testing (auto-increment)
#[ApiResource(
    prefix: 'test-api',
    name: 'products'
)]
class Product extends Model
{
    protected $table = 'products';

    protected $fillable = ['name', 'price'];
}

// Test model with UUID primary key
#[ApiResource(
    prefix: 'test-api',
    name: 'uuid-products'
)]
class UuidProduct extends Model
{
    protected $table = 'uuid_products';

    protected $fillable = ['name', 'price'];

    protected $keyType = 'string';

    public $incrementing = false;

    #[Override]
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model): void {
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }
        });
    }
}

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $discoveryService = new ApiResourceDiscoveryService;

    // Create products table (auto-increment ID)
    Schema::create('products', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->decimal('price', 8, 2);
        $table->timestamps();
    });

    // Create uuid_products table (UUID primary key)
    Schema::create('uuid_products', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->string('name');
        $table->decimal('price', 8, 2);
        $table->timestamps();
    });

    // Register routes for both test models
    $discoveryService->registerModelRoutes(Product::class);
    $discoveryService->registerModelRoutes(UuidProduct::class);

    // Create test records
    Product::create(['name' => 'Test Product', 'price' => 19.99]);
    UuidProduct::create(['name' => 'UUID Product', 'price' => 29.99]);
});

test('invalid id returns 404 not sql error', function (): void {
    // Test various invalid ID formats that could cause SQL errors in PostgreSQL
    $invalidIds = [
        'statuss',      // The specific case from the user's issue
        'invalid-id',
        'abc123',
        '123abc',
        '!@#$%',
        'null',
        'undefined',
    ];

    foreach ($invalidIds as $invalidId) {
        $response = $this->getJson("/api/test-api/products/{$invalidId}");

        // Should return 404, not a SQL error
        $response->assertStatus(404);

        // Ensure it's a proper 404 response, not a SQL error response
        $response->assertJsonMissing(['sqlstate', 'sql']);

        // Should contain a proper error message
        $response->assertJsonStructure(['message']);
    }
});

test('invalid uuid returns 404 not sql error', function (): void {
    // Test invalid UUID formats
    $invalidUuids = [
        'not-a-uuid',
        '123-456-789',
        'invalid-uuid-format',
        '550e8400-e29b-41d4-a716',  // incomplete UUID
        'xxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',  // invalid characters
    ];

    foreach ($invalidUuids as $invalidUuid) {
        $response = $this->getJson("/api/test-api/uuid-products/{$invalidUuid}");

        $response->assertStatus(404);
        $response->assertJsonMissing(['sqlstate', 'sql']);
        $response->assertJsonStructure(['message']);
    }
});

test('valid numeric id works', function (): void {
    $response = $this->getJson('/api/test-api/products/1');
    $response->assertStatus(200);
    $response->assertJson([
        'data' => [
            'id' => 1,
            'name' => 'Test Product',
            'price' => '19.99',
        ],
    ]);
});

test('valid uuid works', function (): void {
    $uuidProduct = UuidProduct::first();

    $response = $this->getJson("/api/test-api/uuid-products/{$uuidProduct->id}");
    $response->assertStatus(200);
    $response->assertJson([
        'data' => [
            'id' => $uuidProduct->id,
            'name' => 'UUID Product',
            'price' => '29.99',
        ],
    ]);
});

test('valid numeric id not found returns 404', function (): void {
    $response = $this->getJson('/api/test-api/products/999');
    $response->assertStatus(404);
});

test('valid uuid not found returns 404', function (): void {
    $nonExistentUuid = Str::uuid()->toString();

    $response = $this->getJson("/api/test-api/uuid-products/{$nonExistentUuid}");
    $response->assertStatus(404);
});

test('invalid id in update returns 404', function (): void {
    $response = $this->putJson('/api/test-api/products/statuss', [
        'name' => 'Updated Product',
        'price' => 29.99,
    ]);

    $response->assertStatus(404);
    $response->assertJsonMissing(['sqlstate', 'sql']);
});

test('invalid id in delete returns 404', function (): void {
    $response = $this->deleteJson('/api/test-api/products/statuss');
    $response->assertStatus(404);
    $response->assertJsonMissing(['sqlstate', 'sql']);
});

test('zero id returns 404', function (): void {
    $response = $this->getJson('/api/test-api/products/0');
    $response->assertStatus(404);
});

test('negative id returns 404', function (): void {
    $response = $this->getJson('/api/test-api/products/-1');
    $response->assertStatus(404);
});
