<?php

declare(strict_types=1);

namespace Volcanic\Tests\Unit;

use Volcanic\Attributes\API;
use Volcanic\Tests\TestCase;

class SoftDeleteApiTest extends TestCase
{
    public function test_api_attribute_includes_soft_delete_operations_when_enabled(): void
    {
        $apiWithSoftDeletes = new API(softDeletes: true);

        $operations = $apiWithSoftDeletes->getOperations();

        // Should include standard operations
        $this->assertContains('index', $operations);
        $this->assertContains('show', $operations);
        $this->assertContains('store', $operations);
        $this->assertContains('update', $operations);
        $this->assertContains('destroy', $operations);

        // Should include soft delete operations
        $this->assertContains('restore', $operations);
        $this->assertContains('forceDelete', $operations);
    }

    public function test_api_attribute_excludes_soft_delete_operations_when_disabled(): void
    {
        $apiWithoutSoftDeletes = new API(softDeletes: false);

        $operations = $apiWithoutSoftDeletes->getOperations();

        // Should include standard operations
        $this->assertContains('index', $operations);
        $this->assertContains('destroy', $operations);

        // Should NOT include soft delete operations
        $this->assertNotContains('restore', $operations);
        $this->assertNotContains('forceDelete', $operations);
    }

    public function test_api_attribute_respects_only_filter_with_soft_deletes(): void
    {
        $api = new API(
            softDeletes: true,
            only: ['index', 'restore']
        );

        $operations = $api->getOperations();

        // Should only include specified operations
        $this->assertEqualsCanonicalizing(['index', 'restore'], $operations);
        $this->assertNotContains('forceDelete', $operations);
        $this->assertNotContains('store', $operations);
    }

    public function test_api_attribute_respects_except_filter_with_soft_deletes(): void
    {
        $api = new API(
            softDeletes: true,
            except: ['forceDelete', 'destroy']
        );

        $operations = $api->getOperations();

        // Should include all operations except the excluded ones
        $this->assertContains('index', $operations);
        $this->assertContains('restore', $operations);
        $this->assertNotContains('forceDelete', $operations);
        $this->assertNotContains('destroy', $operations);
    }

    public function test_allows_operation_works_with_soft_delete_operations(): void
    {
        $api = new API(
            softDeletes: true,
            only: ['restore', 'forceDelete']
        );

        $this->assertTrue($api->allowsOperation('restore'));
        $this->assertTrue($api->allowsOperation('forceDelete'));
        $this->assertFalse($api->allowsOperation('index'));
        $this->assertFalse($api->allowsOperation('store'));
    }
}
