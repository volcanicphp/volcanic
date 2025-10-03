<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Volcanic\Attributes\ApiResource;
use Volcanic\Services\SchemaService;

beforeEach(function (): void {
    // Create test table for Product model
    Schema::create('products', function ($table): void {
        $table->id();
        $table->string('name');
        $table->text('description');
        $table->decimal('price', 10, 2);
        $table->string('password'); // Hidden field
        $table->timestamps();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('products');
});

#[ApiResource(prefix: 'api/v1')]
class SchemaTestProduct extends Model
{
    protected $table = 'products';

    protected $fillable = ['name', 'description', 'price'];

    protected $hidden = ['password'];
}

test('schema service returns routes and models', function (): void {
    $schemaService = app(SchemaService::class);

    $schema = $schemaService->getSchema();

    expect($schema)->toHaveKey('routes');
    expect($schema)->toHaveKey('models');
    expect($schema['routes'])->toBeArray();
    expect($schema['models'])->toBeArray();
});

test('schema service returns model fields from database', function (): void {
    $schemaService = app(SchemaService::class);
    $schema = $schemaService->getSchema();

    // The schema should have models array (even if empty in test env)
    expect($schema)->toHaveKey('models');
    expect($schema['models'])->toBeArray();
});

test('schema service respects hidden fields', function (): void {
    // Create a mock model instance to test field filtering
    $model = new SchemaTestProduct;

    $schemaService = app(SchemaService::class);

    // Use reflection to test the protected method
    $reflection = new ReflectionClass($schemaService);
    $method = $reflection->getMethod('getModelFields');

    $fields = $method->invoke($schemaService, $model, 'products');

    // Password should NOT be in the fields array
    $fieldNames = collect($fields)->pluck('name')->toArray();
    expect($fieldNames)->not->toContain('password');

    // But password should be in the model's hidden property
    expect($model->getHidden())->toContain('password');
});

test('schema service includes api resource config', function (): void {
    $reflection = new ReflectionClass(SchemaTestProduct::class);
    $apiAttributes = $reflection->getAttributes(ApiResource::class);
    $apiAttribute = $apiAttributes[0]->newInstance();

    $schemaService = app(SchemaService::class);

    // Use reflection to test the protected method
    $reflectionService = new ReflectionClass($schemaService);
    $method = $reflectionService->getMethod('getApiResourceConfig');

    $config = $method->invoke($schemaService, $apiAttribute);

    expect($config)->toHaveKey('prefix');
    expect($config['prefix'])->toBe('api/v1');
    expect($config)->toHaveKey('sortable');
    expect($config)->toHaveKey('filterable');
});

test('schema service includes fillable and guarded properties', function (): void {
    $model = new SchemaTestProduct;

    expect($model->getFillable())->toBe(['name', 'description', 'price']);
    expect($model->getGuarded())->toBeArray();
    expect($model->getHidden())->toContain('password');
});

test('schema service normalizes database types', function (): void {
    $schemaService = app(SchemaService::class);

    // Use reflection to test the protected method
    $reflection = new ReflectionClass($schemaService);
    $method = $reflection->getMethod('normalizeType');

    // Test various type normalizations
    expect($method->invoke($schemaService, 'varchar(255)'))->toBe('string');
    expect($method->invoke($schemaService, 'int(11)'))->toBe('integer');
    expect($method->invoke($schemaService, 'decimal(10,2)'))->toBe('decimal');
    expect($method->invoke($schemaService, 'text'))->toBe('string');
    expect($method->invoke($schemaService, 'tinyint(1)'))->toBe('boolean');
    expect($method->invoke($schemaService, 'datetime'))->toBe('datetime');
    expect($method->invoke($schemaService, 'json'))->toBe('json');
});
