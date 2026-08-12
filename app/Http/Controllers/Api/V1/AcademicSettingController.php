<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\AcademicSettingRequest;
use App\Http\Resources\AcademicSettingResource;
use App\Models\AcademicSetting;
use App\Services\AcademicSettingService;
use Illuminate\Http\JsonResponse;

class AcademicSettingController extends CrudController
{
    public function __construct(AcademicSettingService $service)
    {
        $this->modelClass = AcademicSetting::class;
        $this->resourceClass = AcademicSettingResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return AcademicSettingRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Academic setting';
    }

    /**
     * The settings grouped by group, ready for the configuration UI.
     */
    public function grouped(): JsonResponse
    {
        $this->authorize('viewAny', AcademicSetting::class);

        /** @var AcademicSettingService $service */
        $service = $this->service;

        return $this->success($service->grouped(), 'Academic settings retrieved.');
    }
}