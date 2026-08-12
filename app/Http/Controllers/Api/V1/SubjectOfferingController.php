<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\SubjectOfferingRequest;
use App\Http\Resources\SubjectOfferingResource;
use App\Models\SubjectOffering;
use App\Services\SubjectOfferingService;

class SubjectOfferingController extends CrudController
{
    public function __construct(SubjectOfferingService $service)
    {
        $this->modelClass = SubjectOffering::class;
        $this->resourceClass = SubjectOfferingResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return SubjectOfferingRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Subject offering';
    }
}