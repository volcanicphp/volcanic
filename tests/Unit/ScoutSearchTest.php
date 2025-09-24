<?php

declare(strict_types=1);

namespace Volcanic\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Volcanic\Attributes\API;
use Volcanic\Services\ApiQueryService;
use Volcanic\Tests\TestCase;

// Mock Scout Searchable trait
trait MockSearchable
{
    public static function search(string $query)
    {
        // Return mock search results with some IDs
        return new MockSearchResults([1, 2, 3]);
    }
}

// Mock search results class
class MockSearchResults
{
    public function __construct(private array $keys = []) {}

    public function keys()
    {
        return collect($this->keys);
    }
}

// Test model with Scout
#[API(
    searchable: ['name', 'content']
)]
class ScoutTestModel extends Model
{
    use MockSearchable;

    protected $table = 'scout_test_models';

    protected $fillable = ['name', 'content', 'status'];
}

// Test model with Scout explicitly enabled
#[API(
    searchable: ['name'],
    scoutSearch: true
)]
class ScoutEnabledTestModel extends Model
{
    protected $table = 'scout_enabled_test_models';

    protected $fillable = ['name', 'content'];

    public static function search(string $query)
    {
        return new MockSearchResults([]);
    }
}

// Test model with Scout explicitly disabled
#[API(
    searchable: ['name'],
    scoutSearch: false
)]
class ScoutDisabledTestModel extends Model
{
    use MockSearchable;

    protected $table = 'scout_disabled_test_models';

    protected $fillable = ['name', 'content'];
}

// Test model without Scout trait
#[API(
    searchable: ['name']
)]
class RegularTestModel extends Model
{
    protected $table = 'regular_test_models';

    protected $fillable = ['name', 'content'];
}

class ScoutSearchTest extends TestCase
{
    private ApiQueryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ApiQueryService;
    }

    public function test_scout_search_is_auto_detected_when_trait_is_present(): void
    {
        $request = new Request(['search' => 'test query']);
        $apiConfig = new API(searchable: ['name', 'content']);

        $query = $this->service->buildQuery(ScoutTestModel::class, $apiConfig, $request);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
    }

    public function test_scout_search_is_used_when_explicitly_enabled(): void
    {
        $request = new Request(['search' => 'test query']);
        $apiConfig = new API(searchable: ['name'], scoutSearch: true);

        $query = $this->service->buildQuery(ScoutEnabledTestModel::class, $apiConfig, $request);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
    }

    public function test_scout_search_is_not_used_when_explicitly_disabled(): void
    {
        $request = new Request(['search' => 'test query']);
        $apiConfig = new API(searchable: ['name'], scoutSearch: false);

        $query = $this->service->buildQuery(ScoutDisabledTestModel::class, $apiConfig, $request);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
    }

    public function test_regular_search_is_used_when_scout_not_available(): void
    {
        $request = new Request(['search' => 'test query']);
        $apiConfig = new API(searchable: ['name']);

        $query = $this->service->buildQuery(RegularTestModel::class, $apiConfig, $request);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
    }

    public function test_api_attribute_scout_methods(): void
    {
        $api = new API(scoutSearch: true);
        $this->assertTrue($api->isScoutSearchEnabled());
        $this->assertTrue($api->isScoutSearchExplicitlySet());

        $api = new API(scoutSearch: false);
        $this->assertFalse($api->isScoutSearchEnabled());
        $this->assertTrue($api->isScoutSearchExplicitlySet());

        $api = new API;
        $this->assertFalse($api->isScoutSearchEnabled());
        $this->assertFalse($api->isScoutSearchExplicitlySet());
    }

    public function test_scout_search_handles_empty_results(): void
    {
        $request = new Request(['search' => 'no results']);
        $apiConfig = new API(searchable: ['name'], scoutSearch: true);

        $query = $this->service->buildQuery(ScoutEnabledTestModel::class, $apiConfig, $request);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
    }
}
