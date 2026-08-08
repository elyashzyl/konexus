<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\RoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Services\RoomService;

class RoomController extends CrudController
{
    public function __construct(RoomService $service)
    {
        $this->modelClass = Room::class;
        $this->resourceClass = RoomResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return RoomRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Room';
    }
}
