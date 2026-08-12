<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\ParentRequest;
use App\Http\Resources\ParentResource;
use App\Models\ParentGuardian;
use App\Services\ParentService;

class ParentController extends PeopleCrudController
{
    public function __construct(ParentService $service)
    {
        $this->modelClass = ParentGuardian::class;
        $this->resourceClass = ParentResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return ParentRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Parent';
    }

    /**
     * The CSV columns exported for the parents module.
     *
     * @return array<string, string>
     */
    protected function exportColumns(): array
    {
        return [
            'Last Name' => 'last_name',
            'First Name' => 'first_name',
            'Middle Name' => 'middle_name',
            'Relationship' => 'relationship',
            'Email' => 'email',
            'Mobile' => 'mobile_number',
            'Occupation' => 'occupation',
            'Status' => 'status',
        ];
    }
}
