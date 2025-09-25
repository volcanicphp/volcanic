<?php

declare(strict_types=1);

namespace Volcanic\Services;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Volcanic\Attributes\ApiResource;
use Volcanic\Exceptions\InvalidFieldException;

class ApiQueryService
{
    /**
     * Build a query based on the API configuration and request parameters.
     */
    public function buildQuery(string $modelClass, ApiResource $apiConfig, Request $request): Builder
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
    protected function applySorting(Builder $query, ApiResource $apiConfig, Request $request): void
    {
        $sortBy = $request->input('sort_by');
        $sortDirection = $request->input('sort_direction', 'asc');

        if (! $sortBy || $apiConfig->sortable === []) {
            return;
        }

        if (! $this->isFieldAllowed($sortBy, $apiConfig->sortable, $query)) {
            throw new InvalidFieldException($sortBy, 'sorting', $apiConfig->sortable);
        }

        if (! in_array(strtolower((string) $sortDirection), ['asc', 'desc'], true)) {
            $sortDirection = 'asc';
        }

        $query->orderBy($sortBy, $sortDirection);
    }

    /**
     * Apply filtering to the query.
     */
    protected function applyFiltering(Builder $query, ApiResource $apiConfig, Request $request): void
    {
        $filter = $request->array('filter');

        foreach ($filter as $filterKey => $value) {
            if ($value === null) {
                continue;
            }

            $parts = explode(':', (string) $filterKey, 2);
            $field = $parts[0];
            $operator = $parts[1] ?? 'eq';

            if (! $this->isFieldAllowed($field, $apiConfig->filterable, $query)) {
                throw new InvalidFieldException($field, 'filtering', $apiConfig->filterable);
            }

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
                default => $query->where($field, $value),
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
    protected function applySearching(Builder $query, ApiResource $apiConfig, Request $request): void
    {
        $search = $request->input('search');

        if (! $search || $apiConfig->searchable === []) {
            return;
        }

        if ($this->shouldUseScoutSearch($query, $apiConfig)) {
            $this->applyScoutSearch($query, $search);

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
    protected function applySoftDeletes(Builder $query, ApiResource $apiConfig, Request $request): void
    {
        if (! $apiConfig->isSoftDeletesEnabled()) {
            return;
        }

        $includeTrashed = $request->input('include_trashed');
        $onlyTrashed = $request->input('only_trashed');

        $model = $query->getModel();

        if ($onlyTrashed && method_exists($model, 'onlyTrashed')) {
            // @phpstan-ignore-next-line
            $query->onlyTrashed();
        } elseif ($includeTrashed && method_exists($model, 'withTrashed')) {
            // @phpstan-ignore-next-line
            $query->withTrashed();
        }
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

        $relationships = is_string($with) ? explode(',', $with) : $with;

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

        $fieldList = is_string($fields) ? explode(',', $fields) : $fields;

        $fieldList = array_map(fn ($field): ?string => preg_replace('/[^a-zA-Z0-9_]/', '', (string) $field), $fieldList);

        if (! in_array($query->getModel()->getKeyName(), $fieldList, true)) {
            array_unshift($fieldList, $query->getModel()->getKeyName());
        }

        $query->select($fieldList);
    }

    /**
     * Determine if Scout search should be used.
     */
    protected function shouldUseScoutSearch(Builder $query, ApiResource $apiConfig): bool
    {
        $model = $query->getModel();

        if ($apiConfig->isScoutSearchExplicitlySet() && ! $apiConfig->isScoutSearchEnabled()) {
            return false;
        }

        if ($apiConfig->isScoutSearchEnabled()) {
            return true;
        }

        return $this->modelUsesScoutSearchable($model) && class_exists('Laravel\Scout\Searchable');
    }

    /**
     * Check if model uses the Scout Searchable trait.
     */
    protected function modelUsesScoutSearchable($model): bool
    {
        if (! class_exists('Laravel\Scout\Searchable')) {
            return false;
        }

        $traits = class_uses_recursive($model::class);

        return in_array('Laravel\Scout\Searchable', $traits, true);
    }

    /**
     * Apply Scout search to the query.
     */
    protected function applyScoutSearch(Builder $query, string $search): void
    {
        try {
            $model = $query->getModel();

            // @phpstan-ignore-next-line
            $scoutResults = $model::search($search);
            $modelIds = $scoutResults->keys();

            if ($modelIds->isEmpty()) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->whereIn($model->getKeyName(), $modelIds->toArray());
        } catch (Exception) {
            $query->whereRaw('1 = 0');
        }
    }

    /**
     * Check if a field is allowed based on the allowed fields array.
     * Supports wildcard (*) to allow any field.
     */
    protected function isFieldAllowed(string $field, array $allowedFields, ?Builder $query = null): bool
    {
        if (in_array($field, $allowedFields, true)) {
            return true;
        }

        if (in_array('*', $allowedFields, true)) {
            return $query instanceof Builder ? $this->fieldExistsOnModel($field, $query) : true;
        }

        return false;
    }

    /**
     * Check if a field exists on the model (either fillable or in table columns).
     */
    protected function fieldExistsOnModel(string $field, Builder $query): bool
    {
        $model = $query->getModel();

        if (in_array($field, $model->getFillable(), true)) {
            return true;
        }

        try {
            $connection = $model->getConnection();
            $table = $model->getTable();
            $columns = $connection->getSchemaBuilder()->getColumnListing($table);

            return in_array($field, $columns, true);
        } catch (Exception) {
            return true;
        }
    }
}
