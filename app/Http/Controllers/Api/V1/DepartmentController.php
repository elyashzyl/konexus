<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\DepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Services\DepartmentService;

class DepartmentController extends CrudController
{
    public function __construct(DepartmentService $service)
    {
        $this->modelClass = Department::class;
        $this->resourceClass = DepartmentResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return DepartmentRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Department';
    }
}
