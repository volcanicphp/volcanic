<?php

declare(strict_types=1);

namespace Volcanic\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public readonly array $methods = ['GET'],
        public readonly ?string $uri = null,
        public readonly ?string $name = null,
        public readonly array $middleware = [],
        public readonly array $where = [],
        public readonly ?string $domain = null,
    ) {}

    /**
     * Get the HTTP methods for this route.
     */
    public function getMethods(): array
    {
        return $this->methods;
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
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
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
}
