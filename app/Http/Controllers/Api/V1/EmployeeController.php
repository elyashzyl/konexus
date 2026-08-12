<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\EmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Services\EmployeeService;

class EmployeeController extends PeopleCrudController
{
    public function __construct(EmployeeService $service)
    {
        $this->modelClass = Employee::class;
        $this->resourceClass = EmployeeResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return EmployeeRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Employee';
    }

    /**
     * The CSV columns exported for the employees module.
     *
     * @return array<string, string>
     */
    protected function exportColumns(): array
    {
        return [
            'Employee Number' => 'employee_number',
            'Last Name' => 'last_name',
            'First Name' => 'first_name',
            'Middle Name' => 'middle_name',
            'Gender' => 'gender',
            'Employment Type' => 'employment_type',
            'Department' => 'department',
            'Position' => 'position',
            'Date Hired' => 'date_hired',
            'Status' => 'status',
        ];
    }
}
