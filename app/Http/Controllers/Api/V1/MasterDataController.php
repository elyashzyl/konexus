<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\MasterDataRequest;
use App\Http\Resources\MasterDataResource;
use App\Models\MasterData;
use App\Services\MasterDataService;

class MasterDataController extends CrudController
{
    public function __construct(MasterDataService $service)
    {
        $this->modelClass = MasterData::class;
        $this->resourceClass = MasterDataResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return MasterDataRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Master Data';
    }
}
