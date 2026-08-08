<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\SystemSettingRequest;
use App\Http\Resources\SystemSettingResource;
use App\Models\SystemSetting;
use App\Services\SystemSettingService;

class SystemSettingController extends CrudController
{
    public function __construct(SystemSettingService $service)
    {
        $this->modelClass = SystemSetting::class;
        $this->resourceClass = SystemSettingResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return SystemSettingRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'System Setting';
    }
}
