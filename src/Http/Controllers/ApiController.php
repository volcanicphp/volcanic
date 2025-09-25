<?php

declare(strict_types=1);

namespace Volcanic\Http\Controllers;

use Illuminate\Contracts\Validation\Validator as ValidatorObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use LogicException;
use Volcanic\Attributes\API;
use Volcanic\Services\ApiQueryService;

class ApiController extends Controller
{
    public function __construct(protected ApiQueryService $queryService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): ResourceCollection
    {
        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        $this->authorizeRequest('viewAny', $modelClass);

        $query = $this->queryService->buildQuery($modelClass, $apiConfig, $request);

        $data = $apiConfig->paginate
            ? $query->paginate($apiConfig->perPage)
            : $query->get();

        try {
            return $data->toResourceCollection();
        } catch (LogicException) {
            return new ResourceCollection($data);
        }
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request $request): JsonResource
    {
        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        $this->authorizeRequest('create', $modelClass);

        $validator = $this->validateRequest($request, $apiConfig, 'store');

        $model = $modelClass::create($validator->validated());

        try {
            return $model->toResource();
        } catch (LogicException) {
            return new JsonResource($model);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): JsonResource
    {
        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        $model = $this->findModel($modelClass, $id, $apiConfig);

        $this->authorizeRequest('view', $model);

        try {
            return $model->toResource();
        } catch (LogicException) {
            return new JsonResource($model);
        }
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, string $id): JsonResource
    {
        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        $model = $this->findModel($modelClass, $id, $apiConfig);

        $this->authorizeRequest('update', $model);

        $validator = $this->validateRequest($request, $apiConfig, 'update');

        $model->update($validator->validated());

        try {
            return $model->toResource();
        } catch (LogicException) {
            return new JsonResource($model);
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

        $this->authorizeRequest('delete', $model);

        $model->delete();

        return new JsonResponse(status: 204);
    }

    /**
     * Restore the specified soft deleted resource.
     */
    public function restore(Request $request, string $id): JsonResource|JsonResponse
    {
        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        if (! $apiConfig->isSoftDeletesEnabled()) {
            return new JsonResponse([
                'message' => 'Soft deletes are not enabled for this resource',
            ], 400);
        }

        $model = $this->findTrashedModel($modelClass, $id);

        $this->authorizeRequest('restore', $model);

        if (method_exists($model, 'restore')) {
            $model->restore();
        } else {
            return new JsonResponse([
                'message' => 'Model does not support soft deletes',
            ], 400);
        }

        try {
            return $model->toResource();
        } catch (LogicException) {
            return new JsonResource($model);
        }
    }

    /**
     * Permanently delete the specified resource.
     */
    public function forceDelete(Request $request, string $id): JsonResponse
    {
        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        if (! $apiConfig->isSoftDeletesEnabled()) {
            return new JsonResponse([
                'message' => 'Soft deletes are not enabled for this resource',
            ], 400);
        }

        $model = $this->findModel($modelClass, $id, $apiConfig);

        $this->authorizeRequest('forceDelete', $model);

        $model->forceDelete();

        return new JsonResponse(status: 204);
    }

    /**
     * Find a model instance.
     */
    protected function findModel(string $modelClass, string $id, API $apiConfig): ?Model
    {
        $query = $modelClass::query();

        if ($apiConfig->isSoftDeletesEnabled() && method_exists($modelClass, 'withTrashed')) {
            $query = $query->withTrashed();
        }

        return $query->findOrFail($id);
    }

    /**
     * Find a trashed model instance (soft deleted).
     */
    protected function findTrashedModel(string $modelClass, string $id): ?Model
    {
        $query = $modelClass::query();

        if (method_exists($modelClass, 'onlyTrashed')) {
            $query = $query->onlyTrashed();
        }

        return $query->findOrFail($id);
    }

    /**
     * Validate the request data.
     */
    protected function validateRequest(Request $request, API $apiConfig, string $operation): ValidatorObject|FormRequest
    {
        $rules = $apiConfig->getValidationRulesForOperation($operation);

        if (is_string($rules)) {
            return $this->validateWithFormRequest($rules);
        }

        if (is_array($rules)) {
            return Validator::make($request->all(), $rules);
        }

        return Validator::make($request->all(), []);
    }

    /**
     * Validate request using a FormRequest class.
     */
    protected function validateWithFormRequest(string $formRequestClass): FormRequest
    {
        // Check if the FormRequest class exists
        if (! class_exists($formRequestClass)) {
            throw new LogicException("FormRequest class {$formRequestClass} does not exist.");
        }

        // Check if the class extends FormRequest
        if (! is_subclass_of($formRequestClass, FormRequest::class)) {
            throw new LogicException("Class {$formRequestClass} must extend ".FormRequest::class);
        }

        return resolve($formRequestClass);
    }

    /**
     * Authorize action only if a policy exists for the model.
     */
    protected function authorizeRequest(string $ability, string|Model $model): void
    {
        $policy = Gate::getPolicyFor($model);

        if (! $policy) {
            return;
        }

        if (! method_exists($policy, $ability)) {
            return;
        }

        Gate::authorize($ability, $model);
    }
}
