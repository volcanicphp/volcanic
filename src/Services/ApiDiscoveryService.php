<?php

declare(strict_types=1);

namespace Volcanic\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionClass;
use Volcanic\Attributes\API;
use Volcanic\Http\Controllers\ApiController;

class ApiDiscoveryService
{
    /**
     * Discover and register API routes for models with the API attribute.
     */
    public function discoverAndRegisterRoutes(): void
    {
        $models = $this->discoverModelsWithApiAttribute();

        foreach ($models as $modelClass => $apiAttribute) {
            $this->registerRoutesForModel($modelClass, $apiAttribute);
        }
    }

    /**
     * Register routes for a single model class (used for testing).
     */
    public function registerModelRoutes(string $modelClass): void
    {
        $reflection = new ReflectionClass($modelClass);
        $apiAttributes = $reflection->getAttributes(API::class);

        if ($apiAttributes === []) {
            return;
        }

        $apiAttribute = $apiAttributes[0]->newInstance();

        // Auto-detect soft deletes if not explicitly set
        if (! $apiAttribute->isSoftDeletesExplicitlySet() && in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $apiAttribute = $apiAttribute->withSoftDeletes(true);
        }

        $this->registerRoutesForModel($modelClass, $apiAttribute);
    }

    /**
     * Discover all models with the API attribute.
     */
    public function discoverModelsWithApiAttribute(): array
    {
        $models = [];
        $modelPaths = $this->getModelPaths();

        foreach ($modelPaths as $path) {
            $files = glob($path.'/*.php');

            foreach ($files as $file) {
                $className = $this->getClassNameFromFile($file);

                if ($className && class_exists($className)) {
                    $reflection = new ReflectionClass($className);

                    if (! $this->isEloquentModel($reflection)) {
                        continue;
                    }

                    $attributes = $reflection->getAttributes(API::class);

                    $reflectionAttribute = Arr::first($attributes);

                    if ($reflectionAttribute) {
                        $apiAttribute = $reflectionAttribute->newInstance();

                        if (! $apiAttribute->isSoftDeletesExplicitlySet() && $this->usesSoftDeletes($reflection)) {
                            $apiAttribute = $apiAttribute->withSoftDeletes(true);
                        }

                        $models[$className] = $apiAttribute;
                    }
                }
            }
        }

        return $models;
    }

    /**
     * Register routes for a specific model.
     */
    protected function registerRoutesForModel(string $modelClass, API $apiAttribute): void
    {
        $resourceName = $this->getResourceName($modelClass, $apiAttribute);
        $prefix = $apiAttribute->getPrefix();
        $operations = $apiAttribute->getOperations();

        Route::middleware('api')->prefix($prefix)->group(function () use ($resourceName, $modelClass, $apiAttribute, $operations): void {
            $controllerClass = ApiController::class;

            if (in_array('index', $operations, true)) {
                $middleware = $apiAttribute->getMiddlewareForOperation('index');
                Route::middleware($middleware)->get($resourceName, [$controllerClass, 'index'])
                    ->defaults('model', $modelClass)
                    ->defaults('api_config', $apiAttribute)
                    ->name("$resourceName.index");
            }

            if (in_array('store', $operations, true)) {
                $middleware = $apiAttribute->getMiddlewareForOperation('store');
                Route::middleware($middleware)->post($resourceName, [$controllerClass, 'store'])
                    ->defaults('model', $modelClass)
                    ->defaults('api_config', $apiAttribute)
                    ->name("$resourceName.store");
            }

            if (in_array('show', $operations, true)) {
                $middleware = $apiAttribute->getMiddlewareForOperation('show');
                Route::middleware($middleware)->get($resourceName.'/{id}', [$controllerClass, 'show'])
                    ->defaults('model', $modelClass)
                    ->defaults('api_config', $apiAttribute)
                    ->name("$resourceName.show");
            }

            if (in_array('update', $operations, true)) {
                $middleware = $apiAttribute->getMiddlewareForOperation('update');
                Route::middleware($middleware)->put($resourceName.'/{id}', [$controllerClass, 'update'])
                    ->defaults('model', $modelClass)
                    ->defaults('api_config', $apiAttribute)
                    ->name("$resourceName.update");

                Route::middleware($middleware)->patch($resourceName.'/{id}', [$controllerClass, 'update'])
                    ->defaults('model', $modelClass)
                    ->defaults('api_config', $apiAttribute)
                    ->name("$resourceName.update");
            }

            if (in_array('destroy', $operations, true)) {
                $middleware = $apiAttribute->getMiddlewareForOperation('destroy');
                Route::middleware($middleware)->delete($resourceName.'/{id}', [$controllerClass, 'destroy'])
                    ->defaults('model', $modelClass)
                    ->defaults('api_config', $apiAttribute)
                    ->name("$resourceName.destroy");
            }

            if (in_array('restore', $operations, true)) {
                $middleware = $apiAttribute->getMiddlewareForOperation('restore');
                Route::middleware($middleware)->post($resourceName.'/{id}/restore', [$controllerClass, 'restore'])
                    ->defaults('model', $modelClass)
                    ->defaults('api_config', $apiAttribute)
                    ->name("$resourceName.restore");
            }

            if (in_array('forceDelete', $operations, true)) {
                $middleware = $apiAttribute->getMiddlewareForOperation('forceDelete');
                Route::middleware($middleware)->delete($resourceName.'/{id}/force', [$controllerClass, 'forceDelete'])
                    ->defaults('model', $modelClass)
                    ->defaults('api_config', $apiAttribute)
                    ->name("$resourceName.forceDelete");
            }
        });
    }

    /**
     * Get the resource name for the model.
     */
    protected function getResourceName(string $modelClass, API $apiAttribute): string
    {
        if ($apiAttribute->getName()) {
            return $apiAttribute->getName();
        }

        $className = class_basename($modelClass);

        return Str::kebab(Str::plural($className));
    }

    /**
     * Get potential model paths.
     */
    protected function getModelPaths(): array
    {
        return [
            app_path('Models'),
            app_path(),
        ];
    }

    /**
     * Extract class name from file path.
     */
    protected function getClassNameFromFile(string $filePath): ?string
    {
        $content = file_get_contents($filePath);

        if (! $content) {
            return null;
        }

        preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches);
        $namespace = $namespaceMatches[1] ?? '';

        preg_match('/class\s+(\w+)/', $content, $classMatches);
        $className = $classMatches[1] ?? '';

        if (! $className) {
            return null;
        }

        return $namespace ? $namespace.'\\'.$className : $className;
    }

    /**
     * Check if the class is an Eloquent model.
     */
    protected function isEloquentModel(ReflectionClass $reflection): bool
    {
        return $reflection->isSubclassOf(Model::class) && ! $reflection->isAbstract();
    }

    /**
     * Check if the model uses the SoftDeletes trait.
     */
    protected function usesSoftDeletes(ReflectionClass $reflection): bool
    {
        $traits = $reflection->getTraitNames();

        return in_array(SoftDeletes::class, $traits, true);
    }
}
