<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\TuitionRequest;
use App\Http\Resources\TuitionResource;
use App\Models\Tuition;
use App\Services\TuitionService;

class TuitionController extends CrudController
{
    public function __construct(TuitionService $service)
    {
        $this->modelClass = Tuition::class;
        $this->resourceClass = TuitionResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return TuitionRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Tuition';
    }
}