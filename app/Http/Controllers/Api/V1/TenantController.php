<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\ActionReasonRequest;
use App\Http\Requests\Api\TenantRequest;
use App\Http\Resources\TenantResource;
use App\Models\Tenant;
use App\Services\TenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends CrudController
{
    protected string $modelClass = Tenant::class;

    protected string $resourceClass = TenantResource::class;

    public function __construct(TenantService $service)
    {
        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return TenantRequest::class;
    }

    /**
     * The human readable label of the resource.
     */
    protected function resourceLabel(): string
    {
        return 'Tenant';
    }

    /**
     * Suspend a tenant and its active subscriptions.
     */
    public function suspend(ActionReasonRequest $request, int $id): JsonResponse
    {
        $tenant = $this->service->find($id);

        $this->authorize('suspend', $tenant);

        return $this->success(
            new TenantResource($this->service->suspend($tenant, $request->input('reason'))),
            'Tenant suspended.'
        );
    }

    /**
     * Reactivate a suspended tenant.
     */
    public function resume(ActionReasonRequest $request, int $id): JsonResponse
    {
        $tenant = $this->service->find($id);

        $this->authorize('resume', $tenant);

        return $this->success(
            new TenantResource($this->service->resume($tenant, $request->input('reason'))),
            'Tenant reactivated.'
        );
    }
}