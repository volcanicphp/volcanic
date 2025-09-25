<?php

declare(strict_types=1);

namespace Volcanic\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Override;
use Volcanic\Attributes\API;
use Volcanic\Services\ApiDiscoveryService;
use Volcanic\Tests\TestCase;

// Test model for invalid ID testing (auto-increment)
#[API(
    prefix: 'test-api',
    name: 'products'
)]
class Product extends Model
{
    protected $table = 'products';

    protected $fillable = ['name', 'price'];
}

// Test model with UUID primary key
#[API(
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

class InvalidIdTest extends TestCase
{
    use RefreshDatabase;

    protected ApiDiscoveryService $discoveryService;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->discoveryService = new ApiDiscoveryService;

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
        $this->discoveryService->registerModelRoutes(Product::class);
        $this->discoveryService->registerModelRoutes(UuidProduct::class);

        // Create test records
        Product::create(['name' => 'Test Product', 'price' => 19.99]);
        UuidProduct::create(['name' => 'UUID Product', 'price' => 29.99]);
    }

    public function test_invalid_id_returns_404_not_sql_error(): void
    {
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
            $response = $this->getJson("/test-api/products/{$invalidId}");

            // Should return 404, not a SQL error
            $response->assertStatus(404);

            // Ensure it's a proper 404 response, not a SQL error response
            $response->assertJsonMissing(['sqlstate', 'sql']);

            // Should contain a proper error message
            $response->assertJsonStructure(['message']);
        }
    }

    public function test_invalid_uuid_returns_404_not_sql_error(): void
    {
        // Test invalid UUID formats
        $invalidUuids = [
            'not-a-uuid',
            '123-456-789',
            'invalid-uuid-format',
            '550e8400-e29b-41d4-a716',  // incomplete UUID
            'xxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',  // invalid characters
        ];

        foreach ($invalidUuids as $invalidUuid) {
            $response = $this->getJson("/test-api/uuid-products/{$invalidUuid}");

            $response->assertStatus(404);
            $response->assertJsonMissing(['sqlstate', 'sql']);
            $response->assertJsonStructure(['message']);
        }
    }

    public function test_valid_numeric_id_works(): void
    {
        $response = $this->getJson('/test-api/products/1');
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => 1,
                'name' => 'Test Product',
                'price' => '19.99',
            ],
        ]);
    }

    public function test_valid_uuid_works(): void
    {
        $uuidProduct = UuidProduct::first();

        $response = $this->getJson("/test-api/uuid-products/{$uuidProduct->id}");
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'id' => $uuidProduct->id,
                'name' => 'UUID Product',
                'price' => '29.99',
            ],
        ]);
    }

    public function test_valid_numeric_id_not_found_returns_404(): void
    {
        $response = $this->getJson('/test-api/products/999');
        $response->assertStatus(404);
    }

    public function test_valid_uuid_not_found_returns_404(): void
    {
        $nonExistentUuid = Str::uuid()->toString();

        $response = $this->getJson("/test-api/uuid-products/{$nonExistentUuid}");
        $response->assertStatus(404);
    }

    public function test_invalid_id_in_update_returns_404(): void
    {
        $response = $this->putJson('/test-api/products/statuss', [
            'name' => 'Updated Product',
            'price' => 29.99,
        ]);

        $response->assertStatus(404);
        $response->assertJsonMissing(['sqlstate', 'sql']);
    }

    public function test_invalid_id_in_delete_returns_404(): void
    {
        $response = $this->deleteJson('/test-api/products/statuss');
        $response->assertStatus(404);
        $response->assertJsonMissing(['sqlstate', 'sql']);
    }

    public function test_zero_id_returns_404(): void
    {
        $response = $this->getJson('/test-api/products/0');
        $response->assertStatus(404);
    }

    public function test_negative_id_returns_404(): void
    {
        $response = $this->getJson('/test-api/products/-1');
        $response->assertStatus(404);
    }
}
