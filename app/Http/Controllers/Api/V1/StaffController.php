<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\StaffRequest;
use App\Http\Resources\StaffResource;
use App\Models\Staff;
use App\Services\StaffService;

class StaffController extends PeopleCrudController
{
    public function __construct(StaffService $service)
    {
        $this->modelClass = Staff::class;
        $this->resourceClass = StaffResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return StaffRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Staff';
    }

    /**
     * The CSV columns exported for the staff module.
     *
     * @return array<string, string>
     */
    protected function exportColumns(): array
    {
        return [
            'Employee Number' => 'employee_number',
            'Support Area' => 'support_area',
        ];
    }
}
