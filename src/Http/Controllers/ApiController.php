<?php

declare(strict_types=1);

namespace Volcanic\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
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
    public function index(Request $request): JsonResponse|ResourceCollection|LengthAwarePaginator
    {
        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        $query = $this->queryService->buildQuery($modelClass, $apiConfig, $request);

        if ($apiConfig->paginated) {
            $data = $query->paginate($apiConfig->perPage);
        } else {
            $data = $query->get();
        }

        try {
            return $data->toResourceCollection();
        } catch (\LogicException) {
            return $data instanceof LengthAwarePaginator
                ? $data
                : new JsonResponse(['data' => $data]);
        }
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request $request): JsonResponse|JsonResource
    {
        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        $validator = $this->validateRequest($request, $apiConfig, 'store');

        $validator->validate();

        $model = $modelClass::create($validator->validated());

        try {
            return $model->toResource();
        } catch (\LogicException) {
            return new JsonResponse(['data' => $model], 201);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): JsonResponse|JsonResource
    {
        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        $model = $this->findModel($modelClass, $id, $apiConfig);

        if (! $model) {
            return new JsonResponse([
                'message' => 'Resource not found',
            ], 404);
        }

        try {
            return $model->toResource();
        } catch (\LogicException) {
            return new JsonResponse(['data' => $model]);
        }
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, string $id): JsonResponse|JsonResource
    {
        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        $model = $this->findModel($modelClass, $id, $apiConfig);

        if (! $model) {
            return new JsonResponse([
                'message' => 'Resource not found',
            ], 404);
        }

        $validator = $this->validateRequest($request, $apiConfig, 'update', $model);

        $validator->validate();

        $model->update($validator->validated());

        try {
            return $model->toResource();
        } catch (\LogicException) {
            return new JsonResponse(['data' => $model]);
        }
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
            return new JsonResponse([
                'message' => 'Resource not found',
            ], 404);
        }

        $model->delete();

        return new JsonResponse(status: 204);
    }

    /**
     * Restore the specified soft deleted resource.
     */
    public function restore(Request $request, string $id): JsonResponse|JsonResource
    {
        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        if (! $apiConfig->softDeletes) {
            return new JsonResponse([
                'message' => 'Soft deletes are not enabled for this resource',
            ], 400);
        }

        $model = $this->findTrashedModel($modelClass, $id);

        if (! $model) {
            return new JsonResponse([
                'message' => 'Trashed resource not found',
            ], 404);
        }

        if (method_exists($model, 'restore')) {
            $model->restore();
        } else {
            return new JsonResponse([
                'message' => 'Model does not support soft deletes',
            ], 400);
        }

        try {
            return $model->toResource();
        } catch (\LogicException) {
            return new JsonResponse(['data' => $model]);
        }
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(Request $request, string $id): JsonResponse
    {
        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        if (! $apiConfig->softDeletes) {
            return new JsonResponse([
                'message' => 'Soft deletes are not enabled for this resource',
            ], 400);
        }

        $model = $this->findModel($modelClass, $id, $apiConfig);

        if (! $model) {
            return new JsonResponse([
                'message' => 'Resource not found',
            ], 404);
        }

        $model->forceDelete();

        return new JsonResponse(status: 204);
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
