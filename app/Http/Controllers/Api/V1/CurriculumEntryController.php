<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\CurriculumEntryRequest;
use App\Http\Resources\CurriculumEntryResource;
use App\Models\CurriculumEntry;
use App\Services\CurriculumEntryService;

class CurriculumEntryController extends CrudController
{
    public function __construct(CurriculumEntryService $service)
    {
        $this->modelClass = CurriculumEntry::class;
        $this->resourceClass = CurriculumEntryResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return CurriculumEntryRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Curriculum entry';
    }
}