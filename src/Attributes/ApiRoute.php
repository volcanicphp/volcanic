<?php

declare(strict_types=1);

namespace Volcanic\Attributes;

use Attribute;
use Illuminate\Support\Str;

#[Attribute(Attribute::TARGET_METHOD)]
class ApiRoute
{
    public function __construct(
        public readonly array $methods = [],
        public readonly ?string $uri = null,
        public readonly array $middleware = [],
        public readonly array $where = [],
        public readonly ?string $domain = null,
        public readonly ?string $prefix = null,
        public readonly ?string $name = null,
    ) {}

    /**
     * Get the HTTP methods for this route.
     * If no methods are explicitly set, determine based on method name.
     */
    public function getMethods(string $methodName = ''): array
    {
        if ($this->methods !== []) {
            return $this->methods;
        }

        return $this->determineHttpMethodsFromName($methodName);
    }

    /**
     * Determine HTTP methods based on method name.
     */
    private function determineHttpMethodsFromName(string $methodName): array
    {
        return match ($methodName) {
            'index', 'show' => ['GET'],
            'store' => ['POST'],
            'update' => ['PUT', 'PATCH'],
            'destroy', 'delete', 'forceDelete' => ['DELETE'],
            'restore' => ['PATCH'],
            default => ['GET'],
        };
    }

    /**
     * Get the URI pattern for the route.
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * Get the route name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get the middleware for this route.
     * Always includes 'api' middleware automatically.
     */
    public function getMiddleware(): array
    {
        $middleware = $this->middleware;

        // Automatically add 'api' middleware if not already present
        if (! in_array('api', $middleware, true)) {
            array_unshift($middleware, 'api');
        }

        return $middleware;
    }

    /**
     * Get the where constraints for the route.
     */
    public function getWhereConstraints(): array
    {
        return $this->where;
    }

    /**
     * Get the domain constraint for the route.
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Get the API route prefix.
     * Always returns a path starting with 'api'.
     */
    public function getPrefix(): string
    {
        $basePrefix = $this->prefix ?? 'api';

        if ($basePrefix === 'api') {
            return 'api';
        }

        if (! Str::startsWith($basePrefix, 'api/')) {
            return 'api/'.ltrim($basePrefix, '/');
        }

        return $basePrefix;
    }
}
