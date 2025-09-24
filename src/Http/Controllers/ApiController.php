<?php

declare(strict_types=1);

namespace Volcanic\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Volcanic\Attributes\API;
use Volcanic\Services\ApiQueryService;

class ApiController extends Controller
{
    public function __construct(
        protected ApiQueryService $queryService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        $query = $this->queryService->buildQuery($modelClass, $apiConfig, $request);

        if ($apiConfig->paginated) {
            $data = $query->paginate($apiConfig->perPage);
        } else {
            $data = $query->get();
        }

        return response()->json([
            'data' => $this->transformData($data, $apiConfig),
            'meta' => $this->getMeta($data, $apiConfig),
        ]);
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request $request): JsonResponse
    {
        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        $validator = $this->validateRequest($request, $apiConfig, 'store');

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $model = new $modelClass;
        $model->fill($validator->validated());
        $model->save();

        return response()->json([
            'message' => 'Resource created successfully',
            'data' => $this->transformData($model, $apiConfig),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        $model = $this->findModel($modelClass, $id, $apiConfig);

        if (! $model) {
            return response()->json([
                'message' => 'Resource not found',
            ], 404);
        }

        return response()->json([
            'data' => $this->transformData($model, $apiConfig),
        ]);
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        $model = $this->findModel($modelClass, $id, $apiConfig);

        if (! $model) {
            return response()->json([
                'message' => 'Resource not found',
            ], 404);
        }

        $validator = $this->validateRequest($request, $apiConfig, 'update', $model);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $model->fill($validator->validated());
        $model->save();

        return response()->json([
            'message' => 'Resource updated successfully',
            'data' => $this->transformData($model, $apiConfig),
        ]);
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        $model = $this->findModel($modelClass, $id, $apiConfig);

        if (! $model) {
            return response()->json([
                'message' => 'Resource not found',
            ], 404);
        }

        if ($apiConfig->softDeletes) {
            $model->delete();
        } else {
            if (method_exists($model, 'forceDelete')) {
                $model->forceDelete();
            } else {
                $model->delete();
            }
        }

        return response()->json([
            'message' => 'Resource deleted successfully',
        ], 204);
    }

    /**
     * Restore the specified soft deleted resource.
     */
    public function restore(Request $request, string $id): JsonResponse
    {
        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        if (! $apiConfig->softDeletes) {
            return response()->json([
                'message' => 'Soft deletes are not enabled for this resource',
            ], 400);
        }

        $model = $this->findTrashedModel($modelClass, $id);

        if (! $model) {
            return response()->json([
                'message' => 'Trashed resource not found',
            ], 404);
        }

        if (method_exists($model, 'restore')) {
            // @phpstan-ignore-next-line
            $model->restore();
        } else {
            return response()->json([
                'message' => 'Model does not support soft deletes',
            ], 400);
        }

        return response()->json([
            'message' => 'Resource restored successfully',
            'data' => $this->transformData($model, $apiConfig),
        ]);
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(Request $request, string $id): JsonResponse
    {
        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        if (! $apiConfig->softDeletes) {
            return response()->json([
                'message' => 'Soft deletes are not enabled for this resource',
            ], 400);
        }

        $model = $this->findModel($modelClass, $id, $apiConfig);

        if (! $model) {
            return response()->json([
                'message' => 'Resource not found',
            ], 404);
        }

        if (method_exists($model, 'forceDelete')) {
            // @phpstan-ignore-next-line
            $model->forceDelete();
        } else {
            $model->delete();
        }

        return response()->json([
            'message' => 'Resource permanently deleted',
        ], 204);
    }

    /**
     * Find a model instance.
     */
    protected function findModel(string $modelClass, string $id, API $apiConfig): ?Model
    {
        $query = $modelClass::query();

        if ($apiConfig->softDeletes) {
            $model = new $modelClass;
            if (method_exists($model, 'withTrashed')) {
                // @phpstan-ignore-next-line
                $query = $query->withTrashed();
            }
        }

        return $query->find($id);
    }

    /**
     * Find a trashed model instance (soft deleted).
     */
    protected function findTrashedModel(string $modelClass, string $id): ?Model
    {
        $query = $modelClass::query();
        $model = new $modelClass;

        if (method_exists($model, 'onlyTrashed')) {
            // @phpstan-ignore-next-line
            $query = $query->onlyTrashed();
        } else {
            // If the model doesn't support soft deletes, return null
            return null;
        }

        return $query->find($id);
    }

    /**
     * Validate the request data.
     */
    protected function validateRequest(Request $request, API $apiConfig, string $operation, ?Model $model = null): \Illuminate\Contracts\Validation\Validator
    {
        $rules = $apiConfig->getValidationRules();
        $operationRules = $rules[$operation] ?? $rules['default'] ?? [];

        return Validator::make($request->all(), $operationRules);
    }

    /**
     * Transform the data based on the API configuration.
     */
    protected function transformData($data, API $apiConfig): mixed
    {
        if ($apiConfig->transformer && class_exists($apiConfig->transformer)) {
            $transformer = new $apiConfig->transformer;

            return $transformer->transform($data);
        }

        // Apply field visibility
        $visibility = $apiConfig->getFieldVisibility();

        if ($data instanceof LengthAwarePaginator) {
            $items = $data->getCollection()->map(function ($item) use ($visibility) {
                return $this->applyFieldVisibility($item, $visibility);
            });

            $data->setCollection($items);

            return $data;
        }

        if ($data instanceof Collection) {
            return $data->map(function ($item) use ($visibility) {
                return $this->applyFieldVisibility($item, $visibility);
            });
        }

        return $this->applyFieldVisibility($data, $visibility);
    }

    /**
     * Apply field visibility rules.
     */
    protected function applyFieldVisibility($item, array $visibility): mixed
    {
        if (! $item instanceof Model) {
            return $item;
        }

        if (! empty($visibility['visible'])) {
            $item->makeVisible($visibility['visible']);
        }

        if (! empty($visibility['hidden'])) {
            $item->makeHidden($visibility['hidden']);
        }

        return $item;
    }

    /**
     * Get metadata for the response.
     */
    protected function getMeta($data, API $apiConfig): array
    {
        $meta = [];

        if ($data instanceof LengthAwarePaginator) {
            $meta['pagination'] = [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'last_page' => $data->lastPage(),
            ];
        }

        return $meta;
    }
}
