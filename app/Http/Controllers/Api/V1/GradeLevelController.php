<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\GradeLevelRequest;
use App\Http\Resources\GradeLevelResource;
use App\Models\GradeLevel;
use App\Services\GradeLevelService;

class GradeLevelController extends CrudController
{
    public function __construct(GradeLevelService $service)
    {
        $this->modelClass = GradeLevel::class;
        $this->resourceClass = GradeLevelResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return GradeLevelRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Grade Level';
    }
}
