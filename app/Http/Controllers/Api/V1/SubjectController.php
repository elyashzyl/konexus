<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\SubjectRequest;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use App\Services\SubjectService;

class SubjectController extends CrudController
{
    public function __construct(SubjectService $service)
    {
        $this->modelClass = Subject::class;
        $this->resourceClass = SubjectResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return SubjectRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Subject';
    }
}
