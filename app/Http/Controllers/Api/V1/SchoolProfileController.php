<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\SchoolProfileRequest;
use App\Http\Resources\SchoolProfileResource;
use App\Models\SchoolProfile;
use App\Services\SchoolProfileService;

class SchoolProfileController extends CrudController
{
    public function __construct(SchoolProfileService $service)
    {
        $this->modelClass = SchoolProfile::class;
        $this->resourceClass = SchoolProfileResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return SchoolProfileRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'School Profile';
    }
}
