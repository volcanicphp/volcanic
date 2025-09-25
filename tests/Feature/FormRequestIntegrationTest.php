<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Volcanic\Attributes\ApiResource;
use Volcanic\Services\ApiResourceDiscoveryService;

// Test FormRequest classes
class ProductFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0.01',
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'name.required' => 'The product name is required.',
            'price.min' => 'The price must be at least 0.01.',
        ];
    }
}

class ProductStoreFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:form_request_products,name',
            'price' => 'required|numeric|min:0.01',
            'category' => 'sometimes|string|max:100',
        ];
    }
}

class ProductUpdateFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0.01',
            'category' => 'sometimes|string|max:100',
        ];
    }
}

// Test models
#[ApiResource(
    prefix: 'api/test',
    name: 'form-request-products-single',
    rules: ProductFormRequest::class
)]
class FormRequestProductSingle extends Model
{
    protected $table = 'form_request_products';

    protected $fillable = ['name', 'price', 'category'];
}

#[ApiResource(
    prefix: 'api/test',
    name: 'form-request-products-per-operation',
    rules: [
        'store' => ProductStoreFormRequest::class,
        'update' => ProductUpdateFormRequest::class,
    ]
)]
class FormRequestProductPerOperation extends Model
{
    protected $table = 'form_request_products';

    protected $fillable = ['name', 'price', 'category'];
}

#[ApiResource(
    prefix: 'api/test',
    name: 'form-request-products-mixed',
    rules: [
        'store' => ProductStoreFormRequest::class,
        'update' => [
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0.01',
        ],
    ]
)]
class FormRequestProductMixed extends Model
{
    protected $table = 'form_request_products';

    protected $fillable = ['name', 'price', 'category'];
}

beforeEach(function (): void {
    // Create test table
    Schema::create('form_request_products', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->decimal('price', 8, 2);
        $table->string('category')->nullable();
        $table->timestamps();
    });

    // Register routes for test models
    $discoveryService = new ApiResourceDiscoveryService;
    $models = [
        FormRequestProductSingle::class,
        FormRequestProductPerOperation::class,
        FormRequestProductMixed::class,
    ];

    foreach ($models as $modelClass) {
        $discoveryService->registerModelRoutes($modelClass);
    }
});

test('validation with single form request for store', function (): void {
    $response = $this->postJson('/api/test/form-request-products-single', [
        'name' => '', // Invalid: required
        'price' => 0, // Invalid: min:0.01
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name', 'price']);
    expect($response->json('errors.name.0'))->toBe('The product name is required.');
    expect($response->json('errors.price.0'))->toBe('The price must be at least 0.01.');
});

test('validation with single form request for update', function (): void {
    // Create a product first
    $product = FormRequestProductSingle::create([
        'name' => 'Test Product',
        'price' => 10.50,
    ]);

    $response = $this->putJson("/api/test/form-request-products-single/{$product->id}", [
        'price' => 0, // Invalid: min:0.01
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['price']);
    expect($response->json('errors.price.0'))->toBe('The price must be at least 0.01.');
});

test('validation with per operation form requests store', function (): void {
    $response = $this->postJson('/api/test/form-request-products-per-operation', [
        'name' => 'Test Product',
        'price' => 10.50,
    ]);

    $response->assertStatus(201);

    // Test duplicate name (unique validation from ProductStoreFormRequest)
    $response = $this->postJson('/api/test/form-request-products-per-operation', [
        'name' => 'Test Product', // Duplicate name
        'price' => 15.75,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);
});

test('validation with per operation form requests update', function (): void {
    // Create a product first
    $product = FormRequestProductPerOperation::create([
        'name' => 'Original Product',
        'price' => 20.00,
    ]);

    $response = $this->putJson("/api/test/form-request-products-per-operation/{$product->id}", [
        'name' => 'Updated Product',
        'price' => 25.50,
    ]);

    $response->assertStatus(200);
    expect($response->json('data.name'))->toBe('Updated Product');
});

test('validation with mixed rules', function (): void {
    // Test store with FormRequest
    $response = $this->postJson('/api/test/form-request-products-mixed', [
        'name' => '', // Invalid: required from FormRequest
        'price' => 10.50,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['name']);

    // Create a product first
    $product = FormRequestProductMixed::create([
        'name' => 'Mixed Product',
        'price' => 30.00,
    ]);

    // Test update with array rules
    $response = $this->putJson("/api/test/form-request-products-mixed/{$product->id}", [
        'price' => 0, // Invalid: min:0.01 from array rules
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['price']);
});

test('successful validation and creation', function (): void {
    $response = $this->postJson('/api/test/form-request-products-single', [
        'name' => 'Valid Product',
        'price' => 49.99,
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure([
        'data' => [
            'id',
            'name',
            'price',
        ],
    ]);

    $this->assertDatabaseHas('form_request_products', [
        'name' => 'Valid Product',
        'price' => 49.99,
    ]);
});

test('form request custom messages are used', function (): void {
    $response = $this->postJson('/api/test/form-request-products-single', [
        'name' => '',
        'price' => 0,
    ]);

    $response->assertStatus(422);

    // Check that custom messages from FormRequest are used
    expect($response->json('errors.name.0'))->toBe('The product name is required.');
    expect($response->json('errors.price.0'))->toBe('The price must be at least 0.01.');
});
