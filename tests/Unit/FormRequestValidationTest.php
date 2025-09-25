<?php

declare(strict_types=1);

namespace Volcanic\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Override;
use ReflectionClass;
use Volcanic\Attributes\ApiResource;
use Volcanic\Tests\TestCase;

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

class FormRequestValidationTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_api_attribute_accepts_string_form_request(): void
    {
        $reflection = new ReflectionClass(ProductWithSingleFormRequest::class);
        $apiAttribute = $reflection->getAttributes(ApiResource::class)[0]->newInstance();

        $rules = $apiAttribute->getValidationRules();

        $this->assertIsString($rules);
        $this->assertEquals(TestProductRequest::class, $rules);
    }

    public function test_api_attribute_accepts_per_operation_form_requests(): void
    {
        $reflection = new ReflectionClass(ProductWithPerOperationFormRequests::class);
        $apiAttribute = $reflection->getAttributes(ApiResource::class)[0]->newInstance();

        $rules = $apiAttribute->getValidationRules();

        $this->assertIsArray($rules);
        $this->assertEquals(TestProductStoreRequest::class, $rules['store']);
        $this->assertEquals(TestProductUpdateRequest::class, $rules['update']);
    }

    public function test_get_validation_rules_for_operation_with_string_rules(): void
    {
        $reflection = new ReflectionClass(ProductWithSingleFormRequest::class);
        $apiAttribute = $reflection->getAttributes(ApiResource::class)[0]->newInstance();

        $storeRules = $apiAttribute->getValidationRulesForOperation('store');
        $updateRules = $apiAttribute->getValidationRulesForOperation('update');

        $this->assertEquals(TestProductRequest::class, $storeRules);
        $this->assertEquals(TestProductRequest::class, $updateRules);
    }

    public function test_get_validation_rules_for_operation_with_per_operation_rules(): void
    {
        $reflection = new ReflectionClass(ProductWithPerOperationFormRequests::class);
        $apiAttribute = $reflection->getAttributes(ApiResource::class)[0]->newInstance();

        $storeRules = $apiAttribute->getValidationRulesForOperation('store');
        $updateRules = $apiAttribute->getValidationRulesForOperation('update');

        $this->assertEquals(TestProductStoreRequest::class, $storeRules);
        $this->assertEquals(TestProductUpdateRequest::class, $updateRules);
    }

    public function test_get_validation_rules_for_operation_with_mixed_validation(): void
    {
        $reflection = new ReflectionClass(ProductWithMixedValidation::class);
        $apiAttribute = $reflection->getAttributes(ApiResource::class)[0]->newInstance();

        $storeRules = $apiAttribute->getValidationRulesForOperation('store');
        $updateRules = $apiAttribute->getValidationRulesForOperation('update');

        $this->assertEquals(TestProductStoreRequest::class, $storeRules);
        $this->assertIsArray($updateRules);
        $this->assertEquals('sometimes|string|max:255', $updateRules['name']);
    }

    public function test_get_validation_rules_for_operation_with_traditional_array_rules(): void
    {
        $reflection = new ReflectionClass(ProductWithArrayRules::class);
        $apiAttribute = $reflection->getAttributes(ApiResource::class)[0]->newInstance();

        $storeRules = $apiAttribute->getValidationRulesForOperation('store');
        $updateRules = $apiAttribute->getValidationRulesForOperation('update');

        $this->assertIsArray($storeRules);
        $this->assertIsArray($updateRules);
        $this->assertEquals('required|string|max:255', $storeRules['name']);
        $this->assertEquals('sometimes|string|max:255', $updateRules['name']);
    }

    public function test_get_validation_rules_for_operation_with_no_rules(): void
    {
        $apiAttribute = new ApiResource;

        $storeRules = $apiAttribute->getValidationRulesForOperation('store');
        $updateRules = $apiAttribute->getValidationRulesForOperation('update');

        $this->assertEquals([], $storeRules);
        $this->assertEquals([], $updateRules);
    }

    public function test_get_validation_rules_for_operation_with_non_existent_operation(): void
    {
        $reflection = new ReflectionClass(ProductWithPerOperationFormRequests::class);
        $apiAttribute = $reflection->getAttributes(ApiResource::class)[0]->newInstance();

        $rules = $apiAttribute->getValidationRulesForOperation('nonexistent');

        $this->assertEquals([], $rules);
    }

    public function test_backward_compatibility_with_simple_array_rules(): void
    {
        // Test that simple arrays without operation keys still work
        $apiAttribute = new ApiResource(rules: [
            'name' => 'required|string',
            'price' => 'required|numeric',
        ]);

        $storeRules = $apiAttribute->getValidationRulesForOperation('store');
        $updateRules = $apiAttribute->getValidationRulesForOperation('update');

        $this->assertIsArray($storeRules);
        $this->assertIsArray($updateRules);
        $this->assertEquals('required|string', $storeRules['name']);
        $this->assertEquals('required|string', $updateRules['name']);
    }
}
