<?php

declare(strict_types=1);

namespace Volcanic\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Override;
use ReflectionClass;
use Volcanic\Attributes\ApiResource;
use Volcanic\Services\ApiResourceDiscoveryService;
use Volcanic\Tests\TestCase;

// Mock model for testing
#[ApiResource(
    prefix: 'test',
    name: 'test-items',
    sortable: ['name'],
    filterable: ['status'],
    searchable: ['name', 'description'],
    rules: [
        'store' => [
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ],
    ]
)]
class TestModel extends Model
{
    protected $table = 'test_models';

    protected $fillable = ['name', 'status', 'description'];
}

class ApiIntegrationTest extends TestCase
{
    protected ApiResourceDiscoveryService $service;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ApiResourceDiscoveryService;
    }

    public function test_api_discovery_service_finds_models_with_api_attribute(): void
    {
        // Create a reflection to test the discovery method
        $discoveredModels = $this->service->discoverModelsWithApiAttribute();

        // The method should return an array
        $this->assertIsArray($discoveredModels);
    }

    public function test_api_attribute_configuration_is_applied_correctly(): void
    {
        $reflection = new ReflectionClass(TestModel::class);
        $attributes = $reflection->getAttributes(ApiResource::class);

        $this->assertNotEmpty($attributes);

        $apiAttribute = $attributes[0]->newInstance();

        $this->assertEquals('api/test', $apiAttribute->getPrefix());
        $this->assertEquals('test-items', $apiAttribute->getName());
        $this->assertEquals(['name'], $apiAttribute->sortable);
        $this->assertEquals(['status'], $apiAttribute->filterable);
        $this->assertEquals(['name', 'description'], $apiAttribute->searchable);

        $validationRules = $apiAttribute->getValidationRules();
        $this->assertArrayHasKey('store', $validationRules);
        $this->assertArrayHasKey('name', $validationRules['store']);
    }

    public function test_api_operations_can_be_filtered(): void
    {
        $apiWithOnly = new ApiResource(only: ['index', 'show']);
        $this->assertEquals(['index', 'show'], $apiWithOnly->getOperations());

        $apiWithExcept = new ApiResource(except: ['destroy']);
        $expectedOperations = ['index', 'show', 'store', 'update'];
        $this->assertEquals($expectedOperations, $apiWithExcept->getOperations());
    }

    public function test_pagination_settings_are_configured_correctly(): void
    {
        $apiWithPagination = new ApiResource(paginate: true, perPage: 25);
        $paginationSettings = $apiWithPagination->getPaginationSettings();

        $this->assertTrue($paginationSettings['enabled']);
        $this->assertEquals(25, $paginationSettings['per_page']);

        $apiWithoutPagination = new ApiResource(paginate: false);
        $paginationSettings = $apiWithoutPagination->getPaginationSettings();

        $this->assertFalse($paginationSettings['enabled']);
    }

    public function test_query_features_are_configured_correctly(): void
    {
        $api = new ApiResource(
            sortable: ['name', 'created_at'],
            filterable: ['status', 'category'],
            searchable: ['name', 'content']
        );

        $queryFeatures = $api->getQueryFeatures();

        $this->assertEquals(['name', 'created_at'], $queryFeatures['sortable']);
        $this->assertEquals(['status', 'category'], $queryFeatures['filterable']);
        $this->assertEquals(['name', 'content'], $queryFeatures['searchable']);
    }
}
