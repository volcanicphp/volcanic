<?php

declare(strict_types=1);

namespace Volcanic\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class API
{
    public function __construct(
        public readonly ?string $prefix = null,
        public readonly ?string $name = null,
        public readonly array $only = [],
        public readonly array $except = [],
        public readonly array $middleware = [],
        public readonly bool $paginated = true,
        public readonly int $perPage = 15,
        public readonly array $sortable = [],
        public readonly array $filterable = [],
        public readonly array $searchable = [],
        public readonly bool $softDeletes = false,
        public readonly array $validation = [],
        public readonly array $hidden = [],
        public readonly array $visible = [],
    ) {}

    /**
     * Get the available CRUD operations.
     */
    public function getOperations(): array
    {
        $defaultOperations = ['index', 'show', 'store', 'update', 'destroy'];

        // Add soft delete operations if enabled
        if ($this->softDeletes) {
            $defaultOperations[] = 'restore';
            $defaultOperations[] = 'forceDelete';
        }

        if (! empty($this->only)) {
            return array_intersect($defaultOperations, $this->only);
        }

        if (! empty($this->except)) {
            return array_diff($defaultOperations, $this->except);
        }

        return $defaultOperations;
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
            'enabled' => $this->paginated,
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
    public function getValidationRules(): array
    {
        return $this->validation;
    }

    /**
     * Get field visibility configuration.
     */
    public function getFieldVisibility(): array
    {
        return [
            'hidden' => $this->hidden,
            'visible' => $this->visible,
        ];
    }
}
