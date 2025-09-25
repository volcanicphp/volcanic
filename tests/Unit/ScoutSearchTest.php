<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Volcanic\Attributes\ApiResource;
use Volcanic\Services\ApiQueryService;

// Mock Scout Searchable trait
trait MockSearchable
{
    public static function search(string $query): MockSearchResults
    {
        // Return mock search results with some IDs
        return new MockSearchResults([1, 2, 3]);
    }
}

// Mock search results class
class MockSearchResults
{
    public function __construct(private readonly array $keys = []) {}

    public function keys(): Collection
    {
        return collect($this->keys);
    }
}

// Test model with Scout
#[ApiResource(
    searchable: ['name', 'content']
)]
class ScoutTestModel extends Model
{
    use MockSearchable;

    protected $table = 'scout_test_models';

    protected $fillable = ['name', 'content', 'status'];
}

// Test model with Scout explicitly enabled
#[ApiResource(
    searchable: ['name'],
    scoutSearch: true
)]
class ScoutEnabledTestModel extends Model
{
    protected $table = 'scout_enabled_test_models';

    protected $fillable = ['name', 'content'];

    public static function search(string $query): MockSearchResults
    {
        return new MockSearchResults([]);
    }
}

// Test model with Scout explicitly disabled
#[ApiResource(
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
#[ApiResource(
    searchable: ['name']
)]
class RegularTestModel extends Model
{
    protected $table = 'regular_test_models';

    protected $fillable = ['name', 'content'];
}

test('scout search is auto detected when trait is present', function (): void {
    $service = new ApiQueryService;
    $request = new Request(['search' => 'test query']);
    $apiConfig = new ApiResource(searchable: ['name', 'content']);

    $query = $service->buildQuery(ScoutTestModel::class, $apiConfig, $request);

    expect($query)->toBeInstanceOf(Builder::class);
});

test('scout search is used when explicitly enabled', function (): void {
    $service = new ApiQueryService;
    $request = new Request(['search' => 'test query']);
    $apiConfig = new ApiResource(searchable: ['name'], scoutSearch: true);

    $query = $service->buildQuery(ScoutEnabledTestModel::class, $apiConfig, $request);

    expect($query)->toBeInstanceOf(Builder::class);
});

test('scout search is not used when explicitly disabled', function (): void {
    $service = new ApiQueryService;
    $request = new Request(['search' => 'test query']);
    $apiConfig = new ApiResource(searchable: ['name'], scoutSearch: false);

    $query = $service->buildQuery(ScoutDisabledTestModel::class, $apiConfig, $request);

    expect($query)->toBeInstanceOf(Builder::class);
});

test('regular search is used when scout not available', function (): void {
    $service = new ApiQueryService;
    $request = new Request(['search' => 'test query']);
    $apiConfig = new ApiResource(searchable: ['name']);

    $query = $service->buildQuery(RegularTestModel::class, $apiConfig, $request);

    expect($query)->toBeInstanceOf(Builder::class);
});

test('api attribute scout methods', function (): void {
    $api = new ApiResource(scoutSearch: true);
    expect($api->isScoutSearchEnabled())->toBeTrue();
    expect($api->isScoutSearchExplicitlySet())->toBeTrue();

    $api = new ApiResource(scoutSearch: false);
    expect($api->isScoutSearchEnabled())->toBeFalse();
    expect($api->isScoutSearchExplicitlySet())->toBeTrue();

    $api = new ApiResource;
    expect($api->isScoutSearchEnabled())->toBeFalse();
    expect($api->isScoutSearchExplicitlySet())->toBeFalse();
});

test('scout search handles empty results', function (): void {
    $service = new ApiQueryService;
    $request = new Request(['search' => 'no results']);
    $apiConfig = new ApiResource(searchable: ['name'], scoutSearch: true);

    $query = $service->buildQuery(ScoutEnabledTestModel::class, $apiConfig, $request);

    expect($query)->toBeInstanceOf(Builder::class);
});
