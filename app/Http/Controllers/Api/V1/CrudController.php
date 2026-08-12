<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\IndexRequest;
use App\Services\CrudService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Redirector;

/**
 * Base controller exposing the standard CRUD + restore + force-delete
 * endpoints shared by every Phase 2 module.
 */
abstract class CrudController extends ApiController
{
    use AuthorizesRequests, ValidatesRequests;

    protected CrudService $service;

    /**
     * The model class authorized against.
     *
     * @var class-string<Model>
     */
    protected string $modelClass;

    /**
     * The API resource used to serialize records.
     *
     * @var class-string<JsonResource>
     */
    protected string $resourceClass;

    public function __construct(CrudService $service)
    {
        $this->service = $service;
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     *
     * @return class-string<FormRequest>
     */
    abstract protected function requestClass(): string;

    /**
     * The human readable label of the resource (used in response messages).
     */
    abstract protected function resourceLabel(): string;

    /**
     * Display a paginated list of records.
     */
    public function index(IndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', $this->modelClass);

        return $this->paginatedResource($this->service->index($request), "{$this->resourceLabel()} list retrieved.");
    }

    /**
     * Store a newly created record.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', $this->modelClass);

        $model = $this->service->create($this->resolveFormRequest($request)->validated());

        return $this->success(new $this->resourceClass($model), "{$this->resourceLabel()} created.", 201);
    }

    /**
     * Display a single record.
     */
    public function show(int $id): JsonResponse
    {
        $model = $this->service->find($id);

        $this->authorize('view', $model);

        return $this->success(new $this->resourceClass($model), "{$this->resourceLabel()} retrieved.");
    }

    /**
     * Update an existing record.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $model = $this->service->find($id);

        $this->authorize('update', $model);

        $model = $this->service->update($model, $this->resolveFormRequest($request)->validated());

        return $this->success(new $this->resourceClass($model), "{$this->resourceLabel()} updated.");
    }

    /**
     * Soft-delete a record.
     */
    public function destroy(int $id): JsonResponse
    {
        $model = $this->service->find($id);

        $this->authorize('delete', $model);

        $this->service->delete($model);

        return $this->success(null, "{$this->resourceLabel()} deleted.");
    }

    /**
     * Restore a soft-deleted record.
     */
    public function restore(int $id): JsonResponse
    {
        $model = $this->service->trashed($id);

        $this->authorize('restore', $model);

        $model = $this->service->restore($model);

        return $this->success(new $this->resourceClass($model), "{$this->resourceLabel()} restored.");
    }

    /**
     * Permanently delete a soft-deleted record.
     */
    public function forceDestroy(int $id): JsonResponse
    {
        $model = $this->service->trashed($id);

        $this->authorize('forceDelete', $model);

        $this->service->forceDelete($model);

        return $this->success(null, "{$this->resourceLabel()} permanently deleted.");
    }

    /**
     * Resolve and validate the module-specific form request from the incoming
     * request so module-specific rules always run.
     */
    protected function resolveFormRequest(Request $request): FormRequest
    {
        /** @var class-string<FormRequest> $class */
        $class = $this->requestClass();

        $formRequest = $class::createFrom($request);
        $formRequest->headers = $request->headers;
        $formRequest->setContainer(app());
        $formRequest->setRedirector(app(Redirector::class));
        $formRequest->validateResolved();

        return $formRequest;
    }

    /**
     * Return a paginated response with the items serialized as resources.
     */
    protected function paginatedResource(LengthAwarePaginator $paginator, string $message = 'OK'): JsonResponse
    {
        return $this->success([
            'items' => $this->resourceClass::collection($paginator->items()),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ], $message);
    }
}
