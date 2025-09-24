<?php

declare(strict_types=1);

namespace Volcanic\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Volcanic\Attributes\API;

class ApiQueryService
{
    /**
     * Build a query based on the API configuration and request parameters.
     */
    public function buildQuery(string $modelClass, API $apiConfig, Request $request): Builder
    {
        $query = $modelClass::query();

        $this->applySorting($query, $apiConfig, $request);
        $this->applyFiltering($query, $apiConfig, $request);
        $this->applySearching($query, $apiConfig, $request);
        $this->applySoftDeletes($query, $apiConfig, $request);
        $this->applyWith($query, $request);
        $this->applySelect($query, $request);

        return $query;
    }

    /**
     * Apply sorting to the query.
     */
    protected function applySorting(Builder $query, API $apiConfig, Request $request): void
    {
        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction', 'asc');

        if (! $sortBy || $apiConfig->sortable === []) {
            return;
        }

        // Validate sort field is allowed
        if (! in_array($sortBy, $apiConfig->sortable, true)) {
            return;
        }

        // Validate sort direction
        if (! in_array(strtolower((string) $sortDirection), ['asc', 'desc'], true)) {
            $sortDirection = 'asc';
        }

        $query->orderBy($sortBy, $sortDirection);
    }

    /**
     * Apply filtering to the query.
     */
    protected function applyFiltering(Builder $query, API $apiConfig, Request $request): void
    {
        $filter = $request->array('filter');

        foreach ($filter as $filterKey => $value) {
            if ($value === null) {
                continue;
            }

            // Parse field and operator from filter key (e.g., "count:gte" or just "count")
            $parts = explode(':', $filterKey, 2);
            $field = $parts[0];
            $operator = $parts[1] ?? 'eq'; // Default to equals if no operator specified

            // Check if field is filterable
            if (! in_array($field, $apiConfig->filterable, true)) {
                continue;
            }

            // Apply filter based on operator
            match ($operator) {
                'eq' => $query->where($field, $value),
                'not' => $query->where($field, '!=', $value),
                'gt' => $query->where($field, '>', $value),
                'gte' => $query->where($field, '>=', $value),
                'lt' => $query->where($field, '<', $value),
                'lte' => $query->where($field, '<=', $value),
                'in' => $this->applyInFilter($query, $field, $value),
                'not_in' => $this->applyNotInFilter($query, $field, $value),
                'between' => $this->applyBetweenFilter($query, $field, $value),
                default => $query->where($field, $value), // Fallback to equals
            };
        }
    }

    /**
     * Apply IN filter to the query.
     */
    protected function applyInFilter(Builder $query, string $field, mixed $value): void
    {
        if (is_array($value)) {
            $query->whereIn($field, $value);
        } elseif (is_string($value) && str_contains($value, ',')) {
            $query->whereIn($field, explode(',', $value));
        } else {
            $query->where($field, $value);
        }
    }

    /**
     * Apply NOT IN filter to the query.
     */
    protected function applyNotInFilter(Builder $query, string $field, mixed $value): void
    {
        if (is_array($value)) {
            $query->whereNotIn($field, $value);
        } elseif (is_string($value) && str_contains($value, ',')) {
            $query->whereNotIn($field, explode(',', $value));
        } else {
            $query->where($field, '!=', $value);
        }
    }

    /**
     * Apply BETWEEN filter to the query.
     */
    protected function applyBetweenFilter(Builder $query, string $field, mixed $value): void
    {
        if (is_array($value) && count($value) === 2) {
            $query->whereBetween($field, [$value[0], $value[1]]);
        } elseif (is_string($value) && str_contains($value, ',')) {
            $range = explode(',', $value, 2);
            if (count($range) === 2) {
                $query->whereBetween($field, [$range[0], $range[1]]);
            }
        }
    }

    /**
     * Apply searching to the query.
     */
    protected function applySearching(Builder $query, API $apiConfig, Request $request): void
    {
        $search = $request->input('search');

        if (! $search || $apiConfig->searchable === []) {
            return;
        }

        $query->where(function (Builder $searchQuery) use ($apiConfig, $search): void {
            foreach ($apiConfig->searchable as $field) {
                $searchQuery->orWhere($field, 'LIKE', "%{$search}%");
            }
        });
    }

    /**
     * Apply soft delete handling to the query.
     */
    protected function applySoftDeletes(Builder $query, API $apiConfig, Request $request): void
    {
        if (! $apiConfig->isSoftDeletesEnabled()) {
            return;
        }

        $includeTrashed = $request->input('include_trashed');
        $onlyTrashed = $request->input('only_trashed');

        // Check if the model uses soft deletes
        $model = $query->getModel();

        if ($onlyTrashed && method_exists($model, 'onlyTrashed')) {
            // @phpstan-ignore-next-line
            $query->onlyTrashed();
        } elseif ($includeTrashed && method_exists($model, 'withTrashed')) {
            // @phpstan-ignore-next-line
            $query->withTrashed();
        }
        // Default behavior will exclude trashed records
    }

    /**
     * Apply relationship loading to the query.
     */
    public function applyWith(Builder $query, Request $request): void
    {
        $with = $request->input('with');

        if (! $with) {
            return;
        }

        // Parse comma-separated relationships
        $relationships = is_string($with) ? explode(',', $with) : $with;

        // Sanitize relationship names
        $relationships = array_map(fn ($relation): ?string => preg_replace(
            '/[^a-zA-Z0-9_.]/', '', (string) $relation
        ), $relationships);

        $query->with($relationships);
    }

    /**
     * Apply field selection to the query.
     */
    public function applySelect(Builder $query, Request $request): void
    {
        $fields = $request->input('fields');

        if (! $fields) {
            return;
        }

        // Parse comma-separated fields
        $fieldList = is_string($fields) ? explode(',', $fields) : $fields;

        // Sanitize field names
        $fieldList = array_map(fn ($field): ?string => preg_replace('/[^a-zA-Z0-9_]/', '', (string) $field), $fieldList);

        // Always include the primary key
        if (! in_array($query->getModel()->getKeyName(), $fieldList, true)) {
            array_unshift($fieldList, $query->getModel()->getKeyName());
        }

        $query->select($fieldList);
    }
}
