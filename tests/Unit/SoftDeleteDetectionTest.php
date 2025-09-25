<?php

declare(strict_types=1);

namespace Volcanic\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Override;
use ReflectionClass;
use Volcanic\Attributes\ApiResource;
use Volcanic\Services\ApiDiscoveryService;
use Volcanic\Tests\TestCase;

// Mock model without SoftDeletes
#[ApiResource(prefix: 'test')]
class RegularModel extends Model
{
    protected $table = 'regular_models';
}

// Mock model with SoftDeletes
#[ApiResource(prefix: 'test')]
class SoftDeleteModel extends Model
{
    use SoftDeletes;

    protected $table = 'soft_delete_models';
}

// Mock model with SoftDeletes explicitly disabled in API attribute
#[ApiResource(prefix: 'test', softDeletes: false)]
class ExplicitlyDisabledSoftDeleteModel extends Model
{
    use SoftDeletes;

    protected $table = 'explicitly_disabled_models';
}

class SoftDeleteDetectionTest extends TestCase
{
    protected ApiDiscoveryService $service;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ApiDiscoveryService;
    }

    public function test_uses_soft_deletes_detects_trait_correctly(): void
    {
        // Test model without SoftDeletes
        $reflection = new ReflectionClass(RegularModel::class);
        $this->assertFalse($this->callProtectedMethod($this->service, 'usesSoftDeletes', [$reflection]));

        // Test model with SoftDeletes
        $reflection = new ReflectionClass(SoftDeleteModel::class);
        $this->assertTrue($this->callProtectedMethod($this->service, 'usesSoftDeletes', [$reflection]));
    }

    public function test_api_attribute_with_soft_deletes_creates_correct_operations(): void
    {
        $reflection = new ReflectionClass(SoftDeleteModel::class);
        $attributes = $reflection->getAttributes(ApiResource::class);
        $apiAttribute = $attributes[0]->newInstance();

        // Simulate the automatic soft deletes detection
        if (! $apiAttribute->isSoftDeletesExplicitlySet() && $this->callProtectedMethod($this->service, 'usesSoftDeletes', [$reflection])) {
            $apiAttribute = $apiAttribute->withSoftDeletes(true);
        }

        $this->assertTrue($apiAttribute->isSoftDeletesEnabled());
        $expectedOperations = ['index', 'show', 'store', 'update', 'destroy', 'restore', 'forceDelete'];
        $this->assertEquals($expectedOperations, $apiAttribute->getOperations());
    }

    public function test_api_attribute_without_soft_deletes_has_regular_operations(): void
    {
        $reflection = new ReflectionClass(RegularModel::class);
        $attributes = $reflection->getAttributes(ApiResource::class);
        $apiAttribute = $attributes[0]->newInstance();

        // Simulate the automatic soft deletes detection (should not change anything)
        if (! $apiAttribute->isSoftDeletesExplicitlySet() && $this->callProtectedMethod($this->service, 'usesSoftDeletes', [$reflection])) {
            $apiAttribute = $apiAttribute->withSoftDeletes(true);
        }

        $this->assertFalse($apiAttribute->isSoftDeletesEnabled());
        $expectedOperations = ['index', 'show', 'store', 'update', 'destroy'];
        $this->assertEquals($expectedOperations, $apiAttribute->getOperations());
    }

    public function test_explicit_soft_deletes_false_is_respected(): void
    {
        $reflection = new ReflectionClass(ExplicitlyDisabledSoftDeleteModel::class);
        $attributes = $reflection->getAttributes(ApiResource::class);
        $apiAttribute = $attributes[0]->newInstance();

        // Even though the model uses SoftDeletes trait, the explicit false should be respected
        $this->assertFalse($apiAttribute->isSoftDeletesEnabled());
        $this->assertTrue($apiAttribute->isSoftDeletesExplicitlySet());

        // Simulate the automatic detection - it should NOT override explicit false
        if (! $apiAttribute->isSoftDeletesExplicitlySet() && $this->callProtectedMethod($this->service, 'usesSoftDeletes', [$reflection])) {
            $apiAttribute = $apiAttribute->withSoftDeletes(true);
        }

        // Should remain false because it was explicitly set
        $this->assertFalse($apiAttribute->isSoftDeletesEnabled());
    }

    public function test_with_soft_deletes_creates_new_instance(): void
    {
        $original = new ApiResource(prefix: 'test', softDeletes: false);
        $withSoftDeletes = $original->withSoftDeletes(true);

        $this->assertNotSame($original, $withSoftDeletes);
        $this->assertFalse($original->isSoftDeletesEnabled());
        $this->assertTrue($withSoftDeletes->isSoftDeletesEnabled());
        $this->assertEquals('test', $withSoftDeletes->getPrefix());
    }

    /**
     * Helper method to call protected methods for testing.
     */
    protected function callProtectedMethod($object, $method, array $parameters = []): mixed
    {
        $reflection = new ReflectionClass($object::class);
        $method = $reflection->getMethod($method);

        return $method->invokeArgs($object, $parameters);
    }
}
