<?php

declare(strict_types=1);

namespace Volcanic\Attributes;

use Attribute;
use Illuminate\Container\Attributes\Config;
use Illuminate\Support\Arr;

#[Attribute(Attribute::TARGET_CLASS)]
class API
{
    public function __construct(
        public readonly ?string $prefix = null,
        public readonly ?string $name = null,
        public readonly array $only = [],
        public readonly array $except = [],
        public readonly array $middleware = [],
        public readonly bool $paginate = true,
        #[Config('volcanic.default_per_page', 15)]
        public readonly int $perPage = 15,
        public readonly array $sortable = [],
        public readonly array $filterable = [],
        public readonly array $searchable = [],
        public readonly ?bool $softDeletes = null,
        public readonly ?bool $scoutSearch = null,
        public readonly array|string $rules = [],
    ) {}

    /**
     * Get the available CRUD operations.
     */
    public function getOperations(): array
    {
        $defaultOperations = ['index', 'show', 'store', 'update', 'destroy'];

        if ($this->isSoftDeletesEnabled()) {
            $defaultOperations[] = 'restore';
            $defaultOperations[] = 'forceDelete';
        }

        if ($this->only !== []) {
            return array_values(array_intersect($defaultOperations, $this->only));
        }

        if ($this->except !== []) {
            return array_diff($defaultOperations, $this->except);
        }

        return $defaultOperations;
    }

    /**
     * Check if soft deletes is enabled.
     */
    public function isSoftDeletesEnabled(): bool
    {
        return $this->softDeletes === true;
    }

    /**
     * Check if soft deletes was explicitly set (not null).
     */
    public function isSoftDeletesExplicitlySet(): bool
    {
        return $this->softDeletes !== null;
    }

    /**
     * Check if scout search was explicitly set (not null).
     */
    public function isScoutSearchExplicitlySet(): bool
    {
        return $this->scoutSearch !== null;
    }

    /**
     * Check if scout search is enabled.
     */
    public function isScoutSearchEnabled(): bool
    {
        return $this->scoutSearch === true;
    }

    /**
     * Get the API resource prefix.
     */
    public function getPrefix(): string
    {
        return $this->prefix ?? 'api';
    }

    /**
     * Get the resource name for routes.
     */
    public function getName(): string
    {
        return $this->name ?? '';
    }

    /**
     * Check if the operation is allowed.
     */
    public function allowsOperation(string $operation): bool
    {
        return in_array($operation, $this->getOperations(), true);
    }

    /**
     * Get pagination settings.
     */
    public function getPaginationSettings(): array
    {
        return [
            'enabled' => $this->paginate,
            'per_page' => $this->perPage,
        ];
    }

    /**
     * Get query features configuration.
     */
    public function getQueryFeatures(): array
    {
        return [
            'sortable' => $this->sortable,
            'filterable' => $this->filterable,
            'searchable' => $this->searchable,
        ];
    }

    /**
     * Get validation rules for the model.
     */
    public function getValidationRules(): array|string
    {
        return $this->rules;
    }

    /**
     * Get validation rules for a specific operation.
     */
    public function getValidationRulesForOperation(string $operation): array|string
    {
        if (is_string($this->rules)) {
            return $this->rules;
        }

        if (is_array($this->rules)) {
            if (isset($this->rules[$operation])) {
                return $this->rules[$operation];
            }

            if ($this->rules !== [] && ! $this->hasOperationKeys($this->rules)) {
                return $this->rules;
            }
        }

        return [];
    }

    /**
     * Check if the rules array contains operation keys (store, update, etc.).
     */
    private function hasOperationKeys(array $rules): bool
    {
        $operationKeys = ['index', 'store', 'show', 'update', 'destroy', 'restore', 'forceDelete'];

        return array_intersect(array_keys($rules), $operationKeys) !== [];
    }

    /**
     * Get middleware for all routes (string values or keys with array values).
     */
    public function getGlobalMiddleware(): array
    {
        $global = [];

        foreach ($this->middleware as $key => $value) {
            if (is_int($key)) {
                $global[] = $value;
            }
        }

        return $global;
    }

    /**
     * Get middleware for specific routes.
     */
    public function getRouteSpecificMiddleware(): array
    {
        $specific = [];

        foreach ($this->middleware as $key => $value) {
            if (is_string($key)) {
                $specific[$key] = Arr::wrap($value);
            }
        }

        return $specific;
    }

    /**
     * Get all middleware for a specific operation.
     */
    public function getMiddlewareForOperation(string $operation): array
    {
        $middleware = $this->getGlobalMiddleware();
        $routeSpecific = $this->getRouteSpecificMiddleware();

        foreach ($routeSpecific as $middlewareName => $routes) {
            if (in_array($operation, $routes, true)) {
                $middleware[] = $middlewareName;
            }
        }

        return $middleware;
    }

    /**
     * Create a new API instance with softDeletes enabled.
     */
    public function withSoftDeletes(bool $softDeletes = true): self
    {
        return new self(
            prefix: $this->prefix,
            name: $this->name,
            only: $this->only,
            except: $this->except,
            middleware: $this->middleware,
            paginate: $this->paginate,
            perPage: $this->perPage,
            sortable: $this->sortable,
            filterable: $this->filterable,
            searchable: $this->searchable,
            softDeletes: $softDeletes,
            scoutSearch: $this->scoutSearch,
            rules: $this->rules,
        );
    }
}
