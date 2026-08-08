<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\CampusRequest;
use App\Http\Resources\CampusResource;
use App\Models\Campus;
use App\Services\CampusService;

class CampusController extends CrudController
{
    public function __construct(CampusService $service)
    {
        $this->modelClass = Campus::class;
        $this->resourceClass = CampusResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return CampusRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Campus';
    }
}
