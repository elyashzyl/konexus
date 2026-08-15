<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\ActionReasonRequest;
use App\Http\Requests\Api\LicenseRequest;
use App\Http\Resources\LicenseResource;
use App\Models\License;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends CrudController
{
    protected string $modelClass = License::class;

    protected string $resourceClass = LicenseResource::class;

    public function __construct(LicenseService $service)
    {
        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return LicenseRequest::class;
    }

    /**
     * The human readable label of the resource.
     */
    protected function resourceLabel(): string
    {
        return 'License';
    }

    /**
     * Regenerate the license key, invalidating the previous one.
     */
    public function regenerate(ActionReasonRequest $request, int $id): JsonResponse
    {
        $license = $this->service->find($id);

        $this->authorize('regenerate', $license);

        return $this->success(
            new LicenseResource($this->service->regenerate($license)),
            'License key regenerated.'
        );
    }

    /**
     * Revoke a license.
     */
    public function revoke(ActionReasonRequest $request, int $id): JsonResponse
    {
        $license = $this->service->find($id);

        $this->authorize('revoke', $license);

        return $this->success(
            new LicenseResource($this->service->revoke($license, $request->validated())),
            'License revoked.'
        );
    }
}