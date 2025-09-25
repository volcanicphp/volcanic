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
    Schema::create('paginated_models', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->text('content');
        $table->integer('priority')->default(0);
        $table->timestamps();
    });

    // Create test data
    for ($i = 1; $i <= 50; $i++) {
        PaginatedModel::create([
            'title' => "Post {$i}",
            'content' => "Content for post {$i}",
            'priority' => $i,
        ]);
    }
});

class PaginatedModel extends Model
{
    protected $fillable = ['title', 'content', 'priority'];
}

it('pagination service works with different types', function (): void {
    $service = new PaginationService;

    // Test length-aware pagination
    $apiConfig = new ApiResource(paginationType: PaginationType::PAGINATE, perPage: 10);
    $request = Request::create('/');
    $query = PaginatedModel::query();
    $result = $service->paginate($query, $apiConfig, $request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->perPage())->toBe(10);
    expect($result->total())->toBe(50);

    // Test simple pagination
    $apiConfig = new ApiResource(paginationType: PaginationType::SIMPLE_PAGINATE, perPage: 15);
    $query = PaginatedModel::query();
    $result = $service->paginate($query, $apiConfig, $request);

    expect($result)->toBeInstanceOf(Paginator::class);
    expect($result->perPage())->toBe(15);

    // Test cursor pagination
    $apiConfig = new ApiResource(paginationType: PaginationType::CURSOR_PAGINATE, perPage: 20);
    $query = PaginatedModel::query();
    $result = $service->paginate($query, $apiConfig, $request);

    expect($result)->toBeInstanceOf(CursorPaginator::class);
    expect($result->perPage())->toBe(20);
});

it('pagination service respects page parameters', function (): void {
    $service = new PaginationService;

    // Test page parameter with length-aware pagination
    $apiConfig = new ApiResource(paginationType: PaginationType::PAGINATE, perPage: 10);
    $request = Request::create('/', 'GET', ['page' => 3]);
    $query = PaginatedModel::query();
    $result = $service->paginate($query, $apiConfig, $request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class);
    expect($result->currentPage())->toBe(3);
    expect($result->perPage())->toBe(10);
});

it('pagination service handles cursor columns correctly', function (): void {
    $service = new PaginationService;

    // Test with custom cursor column
    $apiConfig = new ApiResource(paginationType: PaginationType::CURSOR_PAGINATE, perPage: 10);
    $request = Request::create('/', 'GET', ['cursor_column' => 'priority']);
    $query = PaginatedModel::query();
    $result = $service->paginate($query, $apiConfig, $request);

    expect($result)->toBeInstanceOf(CursorPaginator::class);
    expect($result->perPage())->toBe(10);

    // Test with invalid cursor column (should fallback to default)
    $request = Request::create('/', 'GET', ['cursor_column' => 'nonexistent_column']);
    $query = PaginatedModel::query();
    $result = $service->paginate($query, $apiConfig, $request);

    expect($result)->toBeInstanceOf(CursorPaginator::class);
    expect($result->perPage())->toBe(10);
});
