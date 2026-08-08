<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\SchoolCalendarEventRequest;
use App\Http\Resources\SchoolCalendarEventResource;
use App\Models\SchoolCalendarEvent;
use App\Services\SchoolCalendarEventService;

class SchoolCalendarEventController extends CrudController
{
    public function __construct(SchoolCalendarEventService $service)
    {
        $this->modelClass = SchoolCalendarEvent::class;
        $this->resourceClass = SchoolCalendarEventResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return SchoolCalendarEventRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'School Calendar Event';
    }
}
