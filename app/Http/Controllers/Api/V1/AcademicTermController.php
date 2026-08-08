<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\AcademicTermRequest;
use App\Http\Resources\AcademicTermResource;
use App\Models\AcademicTerm;
use App\Services\AcademicTermService;

class AcademicTermController extends CrudController
{
    public function __construct(AcademicTermService $service)
    {
        $this->modelClass = AcademicTerm::class;
        $this->resourceClass = AcademicTermResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return AcademicTermRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Academic Term';
    }
}
