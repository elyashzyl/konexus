<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\GuardianRequest;
use App\Http\Resources\GuardianResource;
use App\Models\Guardian;
use App\Services\GuardianService;

class GuardianController extends PeopleCrudController
{
    public function __construct(GuardianService $service)
    {
        $this->modelClass = Guardian::class;
        $this->resourceClass = GuardianResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return GuardianRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Guardian';
    }

    /**
     * The CSV columns exported for the guardians module.
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
