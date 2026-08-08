<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\AnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Services\AnnouncementService;

class AnnouncementController extends CrudController
{
    public function __construct(AnnouncementService $service)
    {
        $this->modelClass = Announcement::class;
        $this->resourceClass = AnnouncementResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return AnnouncementRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Announcement';
    }
}
