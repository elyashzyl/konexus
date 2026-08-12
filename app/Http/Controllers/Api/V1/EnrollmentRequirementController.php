<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\EnrollmentRequirementRequest;
use App\Http\Resources\EnrollmentRequirementResource;
use App\Models\EnrollmentRequirement;
use App\Services\EnrollmentRequirementService;

class EnrollmentRequirementController extends CrudController
{
    public function __construct(EnrollmentRequirementService $service)
    {
        $this->modelClass = EnrollmentRequirement::class;
        $this->resourceClass = EnrollmentRequirementResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return EnrollmentRequirementRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Enrollment Requirement';
    }
}