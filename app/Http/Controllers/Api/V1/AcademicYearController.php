<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\AcademicYearRequest;
use App\Http\Resources\AcademicYearResource;
use App\Models\AcademicYear;
use App\Services\AcademicYearService;

class AcademicYearController extends CrudController
{
    public function __construct(AcademicYearService $service)
    {
        $this->modelClass = AcademicYear::class;
        $this->resourceClass = AcademicYearResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return AcademicYearRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Academic Year';
    }
}
