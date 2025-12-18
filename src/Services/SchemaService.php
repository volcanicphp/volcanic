<?php

declare(strict_types=1);

namespace Volcanic\Services;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionClass;
use Volcanic\Attributes\ApiResource;

class SchemaService
{
    public function __construct(
        protected ApiResourceDiscoveryService $resourceDiscovery,
        protected ApiRouteDiscoveryService $routeDiscovery
    ) {}

    /**
     * Get the complete schema for the API including routes and models.
     */
    public function getSchema(): array
    {
        return [
            'routes' => $this->getRoutes(),
            'models' => $this->getModels(),
        ];
    }

    /**
     * Get all registered routes (API and web routes).
     */
    protected function getRoutes(): array
    {
        $routes = [];
        $routeCollection = Route::getRoutes();

        /** @var \Illuminate\Routing\Route $route */
        foreach ($routeCollection->getRoutes() as $route) {
            $uri = $route->uri();

            // Skip internal Laravel/framework routes
            if ($this->shouldSkipRoute($uri)) {
                continue;
            }

            foreach ($route->methods() as $method) {
                if ($method === 'HEAD') {
                    continue;
                }

                $routes[] = [
                    'method' => $method,
                    'uri' => $uri === '/' ? $uri : "/$uri",
                    'name' => $route->getName(),
                    'action' => $route->getActionName(),
                    'middleware' => $route->gatherMiddleware(),
                    'parameters' => $this->extractParameters($uri),
                    'prefix' => $this->getRoutePrefix($uri),
                ];
            }
        }

        return $routes;
    }

    /**
     * Determine if a route should be skipped from the schema.
     */
    protected function shouldSkipRoute(string $uri): bool
    {
        $playgroundRoute = config('volcanic.playground.uri', 'volcanic/playground');

        if (Str::startsWith($playgroundRoute, '/')) {
            $playgroundRoute = ltrim($playgroundRoute, '/');
        }

        $skipPatterns = [
            '__schema__',
            '_ignition',
            '_debugbar',
            '_boost/*',
            'sanctum/*',
            'telescope/*',
            'horizon/*',
            'nova/*',
            'vendor/*',
            'livewire/*',
            'filament/*',
            'storage/{path}',
            $playgroundRoute === '/' ? '/' : $playgroundRoute,
        ];

        return array_any($skipPatterns, fn (string $pattern): bool => Str::is($pattern, $uri));
    }

    /**
     * Get the route prefix (api, web, etc.).
     */
    protected function getRoutePrefix(string $uri): string
    {
        $parts = explode('/', trim($uri, '/'));
        $firstSegment = $parts[0] ?? '';

        // Common prefixes
        if (in_array($firstSegment, ['api', 'admin', 'dashboard', 'web'], true)) {
            return $firstSegment;
        }

        // Check if it's an API route
        $apiPrefix = config('volcanic.default_api_prefix', 'api');
        if (str_starts_with($uri, (string) $apiPrefix)) {
            return 'api';
        }

        return 'web';
    }

    /**
     * Extract route parameters from URI.
     */
    protected function extractParameters(string $uri): array
    {
        preg_match_all('/\{([^}]+)\}/', $uri, $matches);

        if (empty($matches[1])) {
            return [];
        }

        return array_map(fn (string $param): array => [
            'name' => str_replace('?', '', $param),
            'required' => ! str_contains($param, '?'),
        ], $matches[1]);
    }

    /**
     * Get all models with their schema information.
     */
    protected function getModels(): array
    {
        $models = [];
        $modelPaths = config('volcanic.model_paths', [app_path('Models')]);

        foreach ($modelPaths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $files = glob($path.'/*.php') ?: [];

            foreach ($files as $file) {
                $className = $this->getClassNameFromFile($file);

                if ($className && class_exists($className)) {
                    $reflection = new ReflectionClass($className);

                    if (! $this->isEloquentModel($reflection)) {
                        continue;
                    }

                    // Get ApiResource attribute if present
                    $apiAttributes = $reflection->getAttributes(ApiResource::class);
                    $apiAttribute = $apiAttributes[0] ?? null;

                    $modelInstance = app($className);
                    $tableName = $modelInstance->getTable();

                    $models[] = [
                        'name' => class_basename($className),
                        'class' => $className,
                        'table' => $tableName,
                        'fields' => $this->getModelFields($modelInstance, $tableName),
                        'hidden' => $modelInstance->getHidden(),
                        'fillable' => $modelInstance->getFillable(),
                        'guarded' => $modelInstance->getGuarded(),
                        'casts' => $modelInstance->getCasts(),
                        'hasApiResource' => $apiAttribute !== null,
                        'apiResourceConfig' => $apiAttribute ? $this->getApiResourceConfig($apiAttribute->newInstance()) : null,
                    ];
                }
            }
        }

        return $models;
    }

    /**
     * Get the API resource configuration.
     */
    protected function getApiResourceConfig(ApiResource $attribute): array
    {
        return [
            'prefix' => $attribute->prefix,
            'only' => $attribute->only,
            'except' => $attribute->except,
            'paginate' => $attribute->paginate,
            'perPage' => $attribute->perPage,
            'paginationType' => $attribute->paginationType?->value,
            'sortable' => $attribute->sortable,
            'filterable' => $attribute->filterable,
            'searchable' => $attribute->searchable,
            'softDeletes' => $attribute->softDeletes,
        ];
    }

    /**
     * Get model fields from database table, respecting hidden fields.
     */
    protected function getModelFields(Model $model, string $tableName): array
    {
        $hidden = $model->getHidden();
        $columns = $this->getTableColumns($tableName);
        $fields = [];

        foreach ($columns as $column) {
            // Skip hidden fields
            if (in_array($column['name'], $hidden, true)) {
                continue;
            }

            $fields[] = $column;
        }

        return $fields;
    }

    /**
     * Get table column information from database.
     */
    protected function getTableColumns(string $tableName): array
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        try {
            if ($driver === 'mysql') {
                return $this->getMySQLColumns($tableName);
            }

            if ($driver === 'pgsql') {
                return $this->getPostgreSQLColumns($tableName);
            }

            if ($driver === 'sqlite') {
                return $this->getSQLiteColumns($tableName);
            }

            // Fallback for other drivers
            return $this->getGenericColumns($tableName);
        } catch (Exception) {
            return [];
        }
    }

    /**
     * Get MySQL table columns.
     */
    protected function getMySQLColumns(string $tableName): array
    {
        $columns = DB::select("SHOW COLUMNS FROM `{$tableName}`");

        return array_map(fn ($column): array => [
            'name' => $column->Field,
            'type' => $this->normalizeType($column->Type),
            'nullable' => $column->Null === 'YES',
            'default' => $column->Default,
            'key' => $column->Key,
        ], $columns);
    }

    /**
     * Get PostgreSQL table columns.
     */
    protected function getPostgreSQLColumns(string $tableName): array
    {
        $columns = DB::select('
            SELECT column_name, data_type, is_nullable, column_default
            FROM information_schema.columns
            WHERE table_name = ?
            ORDER BY ordinal_position
        ', [$tableName]);

        return array_map(fn ($column): array => [
            'name' => $column->column_name,
            'type' => $this->normalizeType($column->data_type),
            'nullable' => $column->is_nullable === 'YES',
            'default' => $column->column_default,
            'key' => '',
        ], $columns);
    }

    /**
     * Get SQLite table columns.
     */
    protected function getSQLiteColumns(string $tableName): array
    {
        $columns = DB::select("PRAGMA table_info(`{$tableName}`)");

        return array_map(fn ($column): array => [
            'name' => $column->name,
            'type' => $this->normalizeType($column->type),
            'nullable' => $column->notnull === 0,
            'default' => $column->dflt_value,
            'key' => $column->pk === 1 ? 'PRI' : '',
        ], $columns);
    }

    /**
     * Get generic table columns (fallback).
     */
    protected function getGenericColumns(string $tableName): array
    {
        $schemaBuilder = DB::getSchemaBuilder();
        $columns = $schemaBuilder->getColumnListing($tableName);

        return array_map(fn (string $column): array => [
            'name' => $column,
            'type' => 'unknown',
            'nullable' => false,
            'default' => null,
            'key' => '',
        ], $columns);
    }

    /**
     * Normalize database type to a simpler format.
     */
    protected function normalizeType(string $type): string
    {
        $type = strtolower($type);

        // Extract base type (e.g., "varchar(255)" -> "varchar")
        if (preg_match('/^([a-z]+)/', $type, $matches)) {
            $baseType = $matches[1];

            // Map to common types
            $typeMap = [
                'int' => 'integer',
                'tinyint' => 'boolean',
                'bigint' => 'integer',
                'smallint' => 'integer',
                'mediumint' => 'integer',
                'varchar' => 'string',
                'char' => 'string',
                'text' => 'string',
                'mediumtext' => 'string',
                'longtext' => 'string',
                'tinytext' => 'string',
                'decimal' => 'decimal',
                'float' => 'float',
                'double' => 'float',
                'datetime' => 'datetime',
                'timestamp' => 'datetime',
                'date' => 'date',
                'time' => 'time',
                'json' => 'json',
                'boolean' => 'boolean',
                'bool' => 'boolean',
            ];

            return $typeMap[$baseType] ?? $baseType;
        }

        return $type;
    }

    /**
     * Get class name from file path.
     */
    protected function getClassNameFromFile(string $file): ?string
    {
        $content = file_get_contents($file);

        if ($content === false) {
            return null;
        }

        // Extract namespace
        preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches);
        $namespace = $namespaceMatches[1] ?? '';

        // Extract class name
        preg_match('/class\s+(\w+)/', $content, $classMatches);
        $className = $classMatches[1] ?? '';

        if ($namespace && $className) {
            return $namespace.'\\'.$className;
        }

        return null;
    }

    /**
     * Check if the reflection class represents an Eloquent model.
     */
    protected function isEloquentModel(ReflectionClass $reflection): bool
    {
        return $reflection->isSubclassOf(Model::class) && ! $reflection->isAbstract();
    }
}
