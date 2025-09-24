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

        return $query;
    }

    /**
     * Apply sorting to the query.
     */
    protected function applySorting(Builder $query, API $apiConfig, Request $request): void
    {
        $sortBy = $request->get('sort_by');
        $sortDirection = $request->get('sort_direction', 'asc');

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
        foreach ($apiConfig->filterable as $field) {
            $value = $request->get("filter[{$field}]");

            if ($value === null) {
                continue;
            }

            // Handle different filter types
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } elseif (str_contains((string) $value, ',')) {
                $query->whereIn($field, explode(',', (string) $value));
            } elseif (str_contains((string) $value, '|')) {
                // Range filter (e.g., "10|20" for between 10 and 20)
                $range = explode('|', (string) $value, 2);
                if (count($range) === 2) {
                    $query->whereBetween($field, [$range[0], $range[1]]);
                }
            } elseif (str_starts_with((string) $value, '>')) {
                $query->where($field, '>', substr((string) $value, 1));
            } elseif (str_starts_with((string) $value, '<')) {
                $query->where($field, '<', substr((string) $value, 1));
            } elseif (str_starts_with((string) $value, '>=')) {
                $query->where($field, '>=', substr((string) $value, 2));
            } elseif (str_starts_with((string) $value, '<=')) {
                $query->where($field, '<=', substr((string) $value, 2));
            } elseif (str_starts_with((string) $value, '!=')) {
                $query->where($field, '!=', substr((string) $value, 2));
            } else {
                $query->where($field, $value);
            }
        }
    }

    /**
     * Apply searching to the query.
     */
    protected function applySearching(Builder $query, API $apiConfig, Request $request): void
    {
        $search = $request->get('search');

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

        $includeTrashed = $request->get('include_trashed');
        $onlyTrashed = $request->get('only_trashed');

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
        $with = $request->get('with');

        if (! $with) {
            return;
        }

        // Parse comma-separated relationships
        $relationships = is_string($with) ? explode(',', $with) : $with;

        // Sanitize relationship names
        $relationships = array_map(fn ($relation): ?string => preg_replace('/[^a-zA-Z0-9_.]/', '', (string) $relation), $relationships);

        $query->with($relationships);
    }

    /**
     * Apply field selection to the query.
     */
    public function applySelect(Builder $query, Request $request): void
    {
        $fields = $request->get('fields');

        if (! $fields) {
            return;
        }

        // Parse comma-separated fields
        $fieldList = is_string($fields) ? explode(',', $fields) : $fields;

        // Sanitize field names
        $fieldList = array_map(fn ($field): ?string => preg_replace('/[^a-zA-Z0-9_]/', '', (string) $field), $fieldList);

        // Always include the primary key
        if (! in_array('id', $fieldList, true)) {
            array_unshift($fieldList, 'id');
        }

        $query->select($fieldList);
    }
}
