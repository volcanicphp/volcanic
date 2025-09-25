<?php

declare(strict_types=1);

namespace Volcanic\Http\Controllers;

use Illuminate\Contracts\Validation\Validator as ValidatorObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use LogicException;
use Volcanic\Attributes\ApiResource;
use Volcanic\Services\ApiQueryService;

class ApiController extends Controller
{
    public function __construct(protected ApiQueryService $queryService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): ResourceCollection
    {
        $this->forceJsonResponse($request);

        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        $this->authorizeRequest('viewAny', $modelClass);

        $query = $this->queryService->buildQuery($modelClass, $apiConfig, $request);

        $data = $apiConfig->paginate
            ? $query->paginate($apiConfig->getPerPage())
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
        $this->forceJsonResponse($request);

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
        $this->forceJsonResponse($request);

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
        $this->forceJsonResponse($request);

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
        $this->forceJsonResponse($request);

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
        $this->forceJsonResponse($request);

        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        if (! $apiConfig->isSoftDeletesEnabled()) {
            return new JsonResponse([
                'message' => __('Soft deletes are not enabled for this resource'),
            ], 400);
        }

        $model = $this->findTrashedModel($modelClass, $id);

        $this->authorizeRequest('restore', $model);

        if (method_exists($model, 'restore')) {
            $model->restore();
        } else {
            return new JsonResponse([
                'message' => __('Model does not support soft deletes'),
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
        $this->forceJsonResponse($request);

        $modelClass = $request->route()->defaults['model'];
        $apiConfig = $request->route()->defaults['api_config'];

        if (! $apiConfig->isSoftDeletesEnabled()) {
            return new JsonResponse([
                'message' => __('Soft deletes are not enabled for this resource'),
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
    protected function findModel(string $modelClass, string $id, ApiResource $apiConfig): Model
    {
        try {
            $query = $modelClass::query();

            if ($apiConfig->isSoftDeletesEnabled() && method_exists($modelClass, 'withTrashed')) {
                $query = $query->withTrashed();
            }

            return $query->findOrFail($id);
        } catch (QueryException $e) {
            // Check if the error is related to invalid data type conversion
            // This handles cases where PostgreSQL can't convert 'statuss' to bigint, etc.
            if ($this->isInvalidIdTypeException($e)) {
                abort(404);
            }

            // Re-throw other database exceptions (connection errors, etc.)
            throw $e;
        }
    }

    /**
     * Find a trashed model instance (soft deleted).
     */
    protected function findTrashedModel(string $modelClass, string $id): Model
    {
        try {
            $query = $modelClass::query();

            if (method_exists($modelClass, 'onlyTrashed')) {
                $query = $query->onlyTrashed();
            }

            return $query->findOrFail($id);
        } catch (QueryException $e) {
            // Check if the error is related to invalid data type conversion
            if ($this->isInvalidIdTypeException($e)) {
                abort(404);
            }

            // Re-throw other database exceptions
            throw $e;
        }
    }

    /**
     * Validate the request data.
     */
    protected function validateRequest(Request $request, ApiResource $apiConfig, string $operation): ValidatorObject|FormRequest
    {
        $rules = $apiConfig->getValidationRulesForOperation($operation);

        if (is_string($rules)) {
            return $this->validateWithFormRequest($rules);
        }

        return Validator::make($request->all(), $rules);
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

    /**
     * Force JSON responses for API endpoints.
     */
    protected function forceJsonResponse(Request $request): void
    {
        $request->headers->set('Accept', 'application/json');
    }

    /**
     * Check if the QueryException is caused by invalid ID type conversion.
     */
    protected function isInvalidIdTypeException(QueryException $e): bool
    {
        $errorMessage = strtolower($e->getMessage());

        // PostgreSQL: Invalid text representation errors (22P02)
        if (Str::contains($errorMessage, 'invalid input syntax for type')) {
            return true;
        }

        // MySQL: Incorrect integer value errors
        if (Str::contains($errorMessage, 'incorrect integer value') ||
            Str::contains($errorMessage, 'invalid input syntax')) {
            return true;
        }

        // SQLite: No error typically, but catch any type conversion issues
        if (Str::contains($errorMessage, 'datatype mismatch')) {
            return true;
        }
        // SQL Server: Conversion failed errors
        if (Str::contains($errorMessage, 'conversion failed')) {
            return true;
        }

        return Str::contains($errorMessage, 'invalid cast specification');
    }
}
