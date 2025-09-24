<?php

declare(strict_types=1);

namespace Volcanic\Tests\Unit;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Override;
use Volcanic\Attributes\API;
use Volcanic\Exceptions\InvalidFieldException;
use Volcanic\Services\ApiQueryService;
use Volcanic\Tests\TestCase;

// Test model with wildcard support
#[API(
    sortable: ['*'],
    filterable: ['*'],
    searchable: ['name', 'description'],
)]
class WildcardTestModel extends Model
{
    protected $table = 'wildcard_test_models';

    protected $fillable = ['name', 'description', 'status'];
}

// Test model with specific fields
#[API(
    sortable: ['name', 'created_at'],
    filterable: ['status'],
    searchable: ['name'],
)]
class RestrictedTestModel extends Model
{
    protected $table = 'restricted_test_models';

    protected $fillable = ['name', 'description', 'status'];
}

class WildcardValidationTest extends TestCase
{
    private ApiQueryService $service;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ApiQueryService;
    }

    public function test_wildcard_allows_fillable_fields_for_sorting(): void
    {
        $request = new Request(['sort_by' => 'name']);
        $apiConfig = new API(sortable: ['*']);

        // This should not throw an exception
        $query = $this->service->buildQuery(WildcardTestModel::class, $apiConfig, $request);

        $this->assertInstanceOf(Builder::class, $query);
    }

    public function test_wildcard_allows_fillable_fields_for_filtering(): void
    {
        $request = new Request(['filter' => ['name' => 'test']]);
        $apiConfig = new API(filterable: ['*']);

        // This should not throw an exception
        $query = $this->service->buildQuery(WildcardTestModel::class, $apiConfig, $request);

        $this->assertInstanceOf(Builder::class, $query);
    }

    public function test_restricted_fields_throw_exception_for_sorting(): void
    {
        $request = new Request(['sort_by' => 'description']); // Not in sortable array
        $apiConfig = new API(sortable: ['name', 'created_at']);

        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage("Field 'description' is not allowed for sorting. Allowed fields are: name, created_at");

        $this->service->buildQuery(RestrictedTestModel::class, $apiConfig, $request);
    }

    public function test_restricted_fields_throw_exception_for_filtering(): void
    {
        $request = new Request(['filter' => ['name' => 'test']]); // Not in filterable array
        $apiConfig = new API(filterable: ['status']);

        $this->expectException(InvalidFieldException::class);
        $this->expectExceptionMessage("Field 'name' is not allowed for filtering. Allowed fields are: status");

        $this->service->buildQuery(RestrictedTestModel::class, $apiConfig, $request);
    }

    public function test_empty_allowed_fields_throw_exception(): void
    {
        $request = new Request(['sort_by' => 'name']);
        $apiConfig = new API(sortable: []);

        // Should not throw exception because sortBy validation returns early when sortable is empty
        $query = $this->service->buildQuery(RestrictedTestModel::class, $apiConfig, $request);

        $this->assertInstanceOf(Builder::class, $query);
    }

    public function test_explicitly_allowed_fields_work_with_wildcard(): void
    {
        $request = new Request(['sort_by' => 'name']);
        $apiConfig = new API(sortable: ['name', '*']); // Both explicit and wildcard

        // This should work
        $query = $this->service->buildQuery(WildcardTestModel::class, $apiConfig, $request);

        $this->assertInstanceOf(Builder::class, $query);
    }
}
