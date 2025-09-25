<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Volcanic\Attributes\ApiResource;

// Test FormRequest for validation
class TestProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'name.required' => 'Custom name required message',
        ];
    }
}

// Test FormRequest for store operation
class TestProductStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:products,name',
            'price' => 'required|numeric|min:0.01',
        ];
    }
}

// Test FormRequest for update operation
class TestProductUpdateRequest extends FormRequest
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
        ];
    }
}

// Test models with different validation configurations
#[ApiResource(rules: TestProductRequest::class)]
class ProductWithSingleFormRequest extends Model
{
    protected $table = 'products';

    protected $fillable = ['name', 'price'];
}

#[ApiResource(rules: [
    'store' => TestProductStoreRequest::class,
    'update' => TestProductUpdateRequest::class,
])]
class ProductWithPerOperationFormRequests extends Model
{
    protected $table = 'products';

    protected $fillable = ['name', 'price'];
}

#[ApiResource(rules: [
    'store' => TestProductStoreRequest::class,
    'update' => [
        'name' => 'sometimes|string|max:255',
        'price' => 'sometimes|numeric|min:0',
    ],
])]
class ProductWithMixedValidation extends Model
{
    protected $table = 'products';

    protected $fillable = ['name', 'price'];
}

#[ApiResource(rules: [
    'store' => [
        'name' => 'required|string|max:255',
        'price' => 'required|numeric|min:0',
    ],
    'update' => [
        'name' => 'sometimes|string|max:255',
        'price' => 'sometimes|numeric|min:0',
    ],
])]
class ProductWithArrayRules extends Model
{
    protected $table = 'products';

    protected $fillable = ['name', 'price'];
}

test('api attribute accepts string form request', function (): void {
    $reflection = new ReflectionClass(ProductWithSingleFormRequest::class);
    $apiAttribute = $reflection->getAttributes(ApiResource::class)[0]->newInstance();

    $rules = $apiAttribute->getValidationRules();

    expect($rules)->toBeString();
    expect($rules)->toBe(TestProductRequest::class);
});

test('api attribute accepts per operation form requests', function (): void {
    $reflection = new ReflectionClass(ProductWithPerOperationFormRequests::class);
    $apiAttribute = $reflection->getAttributes(ApiResource::class)[0]->newInstance();

    $rules = $apiAttribute->getValidationRules();

    expect($rules)->toBeArray();
    expect($rules['store'])->toBe(TestProductStoreRequest::class);
    expect($rules['update'])->toBe(TestProductUpdateRequest::class);
});

test('get validation rules for operation with string rules', function (): void {
    $reflection = new ReflectionClass(ProductWithSingleFormRequest::class);
    $apiAttribute = $reflection->getAttributes(ApiResource::class)[0]->newInstance();

    $storeRules = $apiAttribute->getValidationRulesForOperation('store');
    $updateRules = $apiAttribute->getValidationRulesForOperation('update');

    expect($storeRules)->toBe(TestProductRequest::class);
    expect($updateRules)->toBe(TestProductRequest::class);
});

test('get validation rules for operation with per operation rules', function (): void {
    $reflection = new ReflectionClass(ProductWithPerOperationFormRequests::class);
    $apiAttribute = $reflection->getAttributes(ApiResource::class)[0]->newInstance();

    $storeRules = $apiAttribute->getValidationRulesForOperation('store');
    $updateRules = $apiAttribute->getValidationRulesForOperation('update');

    expect($storeRules)->toBe(TestProductStoreRequest::class);
    expect($updateRules)->toBe(TestProductUpdateRequest::class);
});

test('get validation rules for operation with mixed validation', function (): void {
    $reflection = new ReflectionClass(ProductWithMixedValidation::class);
    $apiAttribute = $reflection->getAttributes(ApiResource::class)[0]->newInstance();

    $storeRules = $apiAttribute->getValidationRulesForOperation('store');
    $updateRules = $apiAttribute->getValidationRulesForOperation('update');

    expect($storeRules)->toBe(TestProductStoreRequest::class);
    expect($updateRules)->toBeArray();
    expect($updateRules['name'])->toBe('sometimes|string|max:255');
});

test('get validation rules for operation with traditional array rules', function (): void {
    $reflection = new ReflectionClass(ProductWithArrayRules::class);
    $apiAttribute = $reflection->getAttributes(ApiResource::class)[0]->newInstance();

    $storeRules = $apiAttribute->getValidationRulesForOperation('store');
    $updateRules = $apiAttribute->getValidationRulesForOperation('update');

    expect($storeRules)->toBeArray();
    expect($updateRules)->toBeArray();
    expect($storeRules['name'])->toBe('required|string|max:255');
    expect($updateRules['name'])->toBe('sometimes|string|max:255');
});

test('get validation rules for operation with no rules', function (): void {
    $apiAttribute = new ApiResource;

    $storeRules = $apiAttribute->getValidationRulesForOperation('store');
    $updateRules = $apiAttribute->getValidationRulesForOperation('update');

    expect($storeRules)->toBe([]);
    expect($updateRules)->toBe([]);
});

test('get validation rules for operation with non existent operation', function (): void {
    $reflection = new ReflectionClass(ProductWithPerOperationFormRequests::class);
    $apiAttribute = $reflection->getAttributes(ApiResource::class)[0]->newInstance();

    $rules = $apiAttribute->getValidationRulesForOperation('nonexistent');

    expect($rules)->toBe([]);
});

test('backward compatibility with simple array rules', function (): void {
    // Test that simple arrays without operation keys still work
    $apiAttribute = new ApiResource(rules: [
        'name' => 'required|string',
        'price' => 'required|numeric',
    ]);

    $storeRules = $apiAttribute->getValidationRulesForOperation('store');
    $updateRules = $apiAttribute->getValidationRulesForOperation('update');

    expect($storeRules)->toBeArray();
    expect($updateRules)->toBeArray();
    expect($storeRules['name'])->toBe('required|string');
    expect($updateRules['name'])->toBe('required|string');
});
