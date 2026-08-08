<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\BuildingRequest;
use App\Http\Resources\BuildingResource;
use App\Models\Building;
use App\Services\BuildingService;

class BuildingController extends CrudController
{
    public function __construct(BuildingService $service)
    {
        $this->modelClass = Building::class;
        $this->resourceClass = BuildingResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return BuildingRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Building';
    }
}
