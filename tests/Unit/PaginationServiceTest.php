<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Volcanic\Attributes\ApiResource;
use Volcanic\Enums\PaginationType;
use Volcanic\Services\PaginationService;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Schema::create('test_models', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });

    // Create test data
    for ($i = 1; $i <= 50; $i++) {
        PaginationTestModel::create([
            'name' => "Test Item {$i}",
            'sort_order' => $i,
        ]);
    }
});

class PaginationTestModel extends Model
{
    protected $table = 'test_models';

    protected $fillable = ['name', 'sort_order'];
}

it('applies length-aware pagination by default', function (): void {
    $apiConfig = new ApiResource(paginate: true, perPage: 10);
    $request = new Request;
    $service = new PaginationService;

    $query = PaginationTestModel::query();
    $result = $service->paginate($query, $apiConfig, $request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->perPage())->toBe(10);
    expect($result->total())->toBe(50);
    expect($result->count())->toBe(10);
});

it('applies simple pagination when configured', function (): void {
    $apiConfig = new ApiResource(
        paginate: true,
        paginationType: PaginationType::SIMPLE,
        perPage: 15
    );
    $request = new Request;
    $service = new PaginationService;

    $query = PaginationTestModel::query();
    $result = $service->paginate($query, $apiConfig, $request);

    expect($result)->toBeInstanceOf(Paginator::class);
    expect($result->perPage())->toBe(15);
    expect($result->count())->toBe(15);
    // Simple paginator doesn't have total() method
    expect(method_exists($result, 'total'))->toBeFalse();
});

it('applies cursor pagination when configured', function (): void {
    $apiConfig = new ApiResource(
        paginate: true,
        paginationType: PaginationType::CURSOR,
        perPage: 20
    );
    $request = new Request;
    $service = new PaginationService;

    $query = PaginationTestModel::query();
    $result = $service->paginate($query, $apiConfig, $request);

    expect($result)->toBeInstanceOf(CursorPaginator::class);
    expect($result->perPage())->toBe(20);
    expect($result->count())->toBe(20);
});

it('uses custom cursor column when provided', function (): void {
    $apiConfig = new ApiResource(
        paginate: true,
        paginationType: PaginationType::CURSOR,
        perPage: 10
    );
    $request = new Request(['cursor_column' => 'sort_order']);
    $service = new PaginationService;

    $query = PaginationTestModel::query();
    $result = $service->paginate($query, $apiConfig, $request);

    expect($result)->toBeInstanceOf(CursorPaginator::class);
    expect($result->count())->toBe(10);
});

it('falls back to model key when invalid cursor column is provided', function (): void {
    $apiConfig = new ApiResource(
        paginate: true,
        paginationType: PaginationType::CURSOR,
        perPage: 10
    );
    $request = new Request(['cursor_column' => 'invalid_column']);
    $service = new PaginationService;

    $query = PaginationTestModel::query();
    $result = $service->paginate($query, $apiConfig, $request);

    expect($result)->toBeInstanceOf(CursorPaginator::class);
    expect($result->count())->toBe(10);
});

it('respects page parameter for length-aware pagination', function (): void {
    $apiConfig = new ApiResource(paginate: true, perPage: 10);
    $request = new Request(['page' => 2]);
    $service = new PaginationService;

    $query = PaginationTestModel::query();
    $result = $service->paginate($query, $apiConfig, $request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->currentPage())->toBe(2);
    expect($result->count())->toBe(10);
});

it('respects page parameter for simple pagination', function (): void {
    $apiConfig = new ApiResource(
        paginate: true,
        paginationType: PaginationType::SIMPLE,
        perPage: 10
    );
    $request = new Request(['page' => 3]);
    $service = new PaginationService;

    $query = PaginationTestModel::query();
    $result = $service->paginate($query, $apiConfig, $request);

    expect($result)->toBeInstanceOf(Paginator::class);
    expect($result->currentPage())->toBe(3);
    expect($result->count())->toBe(10);
});

it('validates pagination types', function (): void {
    $service = new PaginationService;

    expect($service->isValidPaginationType('paginate'))->toBeTrue();
    expect($service->isValidPaginationType('simplePaginate'))->toBeTrue();
    expect($service->isValidPaginationType('cursorPaginate'))->toBeTrue();
    expect($service->isValidPaginationType('invalid'))->toBeFalse();
});

it('returns supported pagination types', function (): void {
    $service = new PaginationService;
    $types = $service->getSupportedTypes();

    expect($types)->toBeArray();
    expect($types)->toHaveKeys(['paginate', 'simplePaginate', 'cursorPaginate']);
    expect($types['paginate'])->toBeString();
    expect($types['simplePaginate'])->toBeString();
    expect($types['cursorPaginate'])->toBeString();
});

it('maintains existing order by clauses for cursor pagination', function (): void {
    $apiConfig = new ApiResource(
        paginate: true,
        paginationType: PaginationType::CURSOR,
        perPage: 10
    );
    $request = new Request;
    $service = new PaginationService;

    $query = PaginationTestModel::query()->orderBy('name');
    $result = $service->paginate($query, $apiConfig, $request);

    expect($result)->toBeInstanceOf(CursorPaginator::class);
    expect($result->count())->toBe(10);
});
