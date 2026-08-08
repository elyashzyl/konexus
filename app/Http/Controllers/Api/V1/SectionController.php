<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\SectionRequest;
use App\Http\Resources\SectionResource;
use App\Models\Section;
use App\Services\SectionService;

class SectionController extends CrudController
{
    public function __construct(SectionService $service)
    {
        $this->modelClass = Section::class;
        $this->resourceClass = SectionResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return SectionRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Section';
    }
}
