<?php

declare(strict_types=1);

namespace Volcanic\Services;

use Illuminate\Support\Facades\Route as LaravelRoute;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use Volcanic\Attributes\ApiRoute;

class RouteDiscoveryService
{
    /**
     * Discover and register routes for all controllers.
     */
    public function discoverAndRegisterRoutes(array $controllerPaths = []): void
    {
        $controllers = $this->discoverControllersWithRouteAttributes($controllerPaths);

        foreach ($controllers as $controllerClass => $methods) {
            $this->registerRoutesForController($controllerClass, $methods);
        }
    }

    /**
     * Register routes for a specific controller class.
     */
    public function registerControllerRoutes(string $controllerClass): void
    {
        $methods = $this->getMethodsWithRouteAttributes($controllerClass);

        if ($methods === []) {
            return;
        }

        $this->registerRoutesForController($controllerClass, $methods);
    }

    /**
     * Discover controllers with the ApiRoute attribute.
     */
    private function discoverControllersWithRouteAttributes(array $paths = []): array
    {
        $controllers = [];

        if ($paths === []) {
            $paths = [
                app_path('Http/Controllers'),
            ];
        }

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $files = glob($path.'/*.php');

            foreach ($files as $file) {
                $className = $this->getClassNameFromFile($file);

                if ($className && class_exists($className)) {
                    $methods = $this->getMethodsWithRouteAttributes($className);

                    if ($methods !== []) {
                        $controllers[$className] = $methods;
                    }
                }
            }
        }

        return $controllers;
    }

    /**
     * Get methods with the ApiRoute attribute from a controller class.
     */
    private function getMethodsWithRouteAttributes(string $controllerClass): array
    {
        $methods = [];
        $reflection = new ReflectionClass($controllerClass);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(ApiRoute::class);

            if ($attributes !== []) {
                $methods[$method->getName()] = [
                    'attribute' => $attributes[0]->newInstance(),
                    'method' => $method,
                ];
            }
        }

        return $methods;
    }

    /**
     * Register routes for a controller and its methods.
     */
    private function registerRoutesForController(string $controllerClass, array $methods): void
    {
        foreach ($methods as $methodName => $methodData) {
            $attribute = $methodData['attribute'];
            $this->registerRouteForMethod($controllerClass, $methodName, $attribute);
        }
    }

    /**
     * Register a single route for a controller method.
     */
    private function registerRouteForMethod(string $controllerClass, string $methodName, ApiRoute $attribute): void
    {
        $uri = $this->buildUri($attribute, $controllerClass, $methodName);

        $route = LaravelRoute::match(
            $attribute->getMethods(),
            $uri,
            [$controllerClass, $methodName]
        );

        // Apply prefix
        $route->prefix($attribute->getPrefix());

        // Apply middleware
        if ($attribute->getMiddleware() !== []) {
            $route->middleware($attribute->getMiddleware());
        }

        // Apply where constraints
        if ($attribute->getWhereConstraints() !== []) {
            $route->where($attribute->getWhereConstraints());
        }

        // Apply domain constraint
        if ($attribute->getDomain() !== null) {
            $route->domain($attribute->getDomain());
        }

        // Apply route name
        if ($attribute->getName() !== null) {
            $route->name($attribute->getName());
        }
    }

    /**
     * Build the URI for the route.
     */
    private function buildUri(ApiRoute $attribute, string $controllerClass, string $methodName): string
    {
        if ($attribute->getUri() !== null) {
            return $attribute->getUri();
        }

        // Generate default URI based on controller and method name
        return $this->generateDefaultUri($controllerClass, $methodName);
    }

    /**
     * Generate default URI based on controller and method name.
     */
    private function generateDefaultUri(string $controllerClass, string $methodName): string
    {
        $controllerName = Str::kebab(
            Str::replaceLast('Controller', '', class_basename($controllerClass))
        );

        return $controllerName.'/'.Str::kebab($methodName);
    }

    /**
     * Extract class name from file path.
     */
    private function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            return null;
        }

        // Match namespace
        if (! preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)) {
            return null;
        }

        // Match class name
        if (! preg_match('/class\s+([a-zA-Z_]\w*)\s*/', $content, $classMatch)) {
            return null;
        }

        return $namespaceMatch[1].'\\'.$classMatch[1];
    }
}
