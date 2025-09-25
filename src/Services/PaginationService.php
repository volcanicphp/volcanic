<?php

declare(strict_types=1);

namespace Volcanic\Services;

use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Volcanic\Attributes\ApiResource;
use Volcanic\Enums\PaginationType;

class PaginationService
{
    /**
     * Apply pagination to a query based on the API configuration.
     */
    public function paginate(Builder $query, ApiResource $apiConfig, Request $request): LengthAwarePaginator|Paginator|CursorPaginator
    {
        $perPage = $apiConfig->getPerPage();
        $paginationType = $apiConfig->getPaginationType();

        return match ($paginationType) {
            PaginationType::SIMPLE_PAGINATE => $this->applySimplePagination($query, $perPage, $request),
            PaginationType::CURSOR_PAGINATE => $this->applyCursorPagination($query, $perPage, $request),
            PaginationType::PAGINATE => $this->applyLengthAwarePagination($query, $perPage, $request),
        };
    }

    /**
     * Apply standard length-aware pagination.
     */
    protected function applyLengthAwarePagination(Builder $query, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = $request->input('page', 1);
        $pageName = 'page';

        return $query->paginate($perPage, ['*'], $pageName, $page);
    }

    /**
     * Apply simple pagination (without total count).
     */
    protected function applySimplePagination(Builder $query, int $perPage, Request $request): Paginator
    {
        $page = $request->input('page', 1);
        $pageName = 'page';

        return $query->simplePaginate($perPage, ['*'], $pageName, $page);
    }

    /**
     * Apply cursor pagination.
     */
    protected function applyCursorPagination(Builder $query, int $perPage, Request $request): CursorPaginator
    {
        // Allow custom cursor column via query parameter, default to model's key
        $model = $query->getModel();
        $defaultCursorColumn = $model->getKeyName();
        $cursorColumn = $request->input('cursor_column', $defaultCursorColumn);

        // Validate that the cursor column exists on the model
        if (! $this->columnExists($model->getTable(), $cursorColumn, $model->getConnection())) {
            $cursorColumn = $defaultCursorColumn;
        }

        // Get cursor from request
        $cursor = $request->input('cursor');

        // Ensure the query is ordered by the cursor column for proper cursor pagination
        if (! $this->hasOrderBy($query, $cursorColumn)) {
            $query->orderBy($cursorColumn);
        }

        return $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }

    /**
     * Check if a column exists in the database table.
     */
    protected function columnExists(string $table, string $column, Connection $connection): bool
    {
        try {
            return $connection->getSchemaBuilder()->hasColumn($table, $column);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Check if the query already has an order by clause for the given column.
     */
    protected function hasOrderBy(Builder $query, string $column): bool
    {
        $orders = $query->getQuery()->orders ?? [];

        foreach ($orders as $order) {
            if (isset($order['column']) && $order['column'] === $column) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get supported pagination types.
     */
    public function getSupportedTypes(): array
    {
        $types = [];
        foreach (PaginationType::cases() as $type) {
            $types[$type->value] = $type->description();
        }

        return $types;
    }

    /**
     * Validate if a pagination type is supported.
     */
    public function isValidPaginationType(string $type): bool
    {
        return PaginationType::tryFrom($type) !== null;
    }
}
