<?php

declare(strict_types=1);

namespace Volcanic\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Volcanic\Attributes\ApiResource;

class ApiResourceAttributeTest extends TestCase
{
    public function test_api_attribute_can_be_instantiated(): void
    {
        $api = new ApiResource;

        $this->assertInstanceOf(ApiResource::class, $api);
        $this->assertEquals('api', $api->getPrefix());
        $this->assertTrue($api->paginate);
    }

    public function test_api_attribute_with_custom_configuration(): void
    {
        $api = new ApiResource(
            prefix: 'v1',
            name: 'custom-users',
            only: ['index', 'show'],
            middleware: ['auth:sanctum'],
            paginate: false,
            perPage: 25
        );

        $this->assertEquals('api/v1', $api->getPrefix());
        $this->assertEquals('custom-users', $api->getName());
        $this->assertEquals(['index', 'show'], $api->getOperations());
        $this->assertEquals(['auth:sanctum'], $api->middleware);
        $this->assertFalse($api->paginate);
        $this->assertEquals(25, $api->perPage);
    }

    public function test_api_attribute_operations_with_except(): void
    {
        $api = new ApiResource(except: ['destroy']);

        $expectedOperations = ['index', 'show', 'store', 'update'];
        $this->assertEquals($expectedOperations, $api->getOperations());
    }

    public function test_api_attribute_allows_operation(): void
    {
        $api = new ApiResource(only: ['index', 'show']);

        $this->assertTrue($api->allowsOperation('index'));
        $this->assertTrue($api->allowsOperation('show'));
        $this->assertFalse($api->allowsOperation('store'));
        $this->assertFalse($api->allowsOperation('destroy'));
    }

    public function test_api_attribute_query_features(): void
    {
        $api = new ApiResource(
            sortable: ['name', 'created_at'],
            filterable: ['status', 'category'],
            searchable: ['name', 'description']
        );

        $features = $api->getQueryFeatures();

        $this->assertEquals(['name', 'created_at'], $features['sortable']);
        $this->assertEquals(['status', 'category'], $features['filterable']);
        $this->assertEquals(['name', 'description'], $features['searchable']);
    }

    public function test_api_attribute_scout_search_configuration(): void
    {
        // Test explicitly enabled
        $api = new ApiResource(scoutSearch: true);
        $this->assertTrue($api->isScoutSearchEnabled());
        $this->assertTrue($api->isScoutSearchExplicitlySet());

        // Test explicitly disabled
        $api = new ApiResource(scoutSearch: false);
        $this->assertFalse($api->isScoutSearchEnabled());
        $this->assertTrue($api->isScoutSearchExplicitlySet());

        // Test default (not set)
        $api = new ApiResource;
        $this->assertFalse($api->isScoutSearchEnabled());
        $this->assertFalse($api->isScoutSearchExplicitlySet());
    }
}
