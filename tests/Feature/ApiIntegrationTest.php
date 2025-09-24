<?php

declare(strict_types=1);

namespace Volcanic\Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Override;
use ReflectionClass;
use Volcanic\Attributes\API;
use Volcanic\Services\ApiDiscoveryService;
use Volcanic\Tests\TestCase;

// Mock model for testing
#[API(
    prefix: 'test',
    name: 'test-items',
    sortable: ['name'],
    filterable: ['status'],
    searchable: ['name', 'description'],
    validation: [
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
    protected ApiDiscoveryService $service;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ApiDiscoveryService;
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
        $attributes = $reflection->getAttributes(API::class);

        $this->assertNotEmpty($attributes);

        $apiAttribute = $attributes[0]->newInstance();

        $this->assertEquals('test', $apiAttribute->getPrefix());
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
        $apiWithOnly = new API(only: ['index', 'show']);
        $this->assertEquals(['index', 'show'], $apiWithOnly->getOperations());

        $apiWithExcept = new API(except: ['destroy']);
        $expectedOperations = ['index', 'show', 'store', 'update'];
        $this->assertEquals($expectedOperations, $apiWithExcept->getOperations());
    }

    public function test_pagination_settings_are_configured_correctly(): void
    {
        $apiWithPagination = new API(paginated: true, perPage: 25);
        $paginationSettings = $apiWithPagination->getPaginationSettings();

        $this->assertTrue($paginationSettings['enabled']);
        $this->assertEquals(25, $paginationSettings['per_page']);

        $apiWithoutPagination = new API(paginated: false);
        $paginationSettings = $apiWithoutPagination->getPaginationSettings();

        $this->assertFalse($paginationSettings['enabled']);
    }

    public function test_query_features_are_configured_correctly(): void
    {
        $api = new API(
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
