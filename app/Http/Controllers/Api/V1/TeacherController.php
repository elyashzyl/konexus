<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\TeacherRequest;
use App\Http\Resources\TeacherResource;
use App\Models\Teacher;
use App\Services\TeacherService;

class TeacherController extends PeopleCrudController
{
    public function __construct(TeacherService $service)
    {
        $this->modelClass = Teacher::class;
        $this->resourceClass = TeacherResource::class;

        parent::__construct($service);
    }

    /**
     * The FormRequest class used to validate store/update payloads.
     */
    protected function requestClass(): string
    {
        return TeacherRequest::class;
    }

    /**
     * The human readable label of the resource (used in response messages).
     */
    protected function resourceLabel(): string
    {
        return 'Teacher';
    }

    /**
     * The CSV columns exported for the teachers module.
     *
     * @return array<string, string>
     */
    protected function exportColumns(): array
    {
        return [
            'Employee Number' => 'employee_number',
            'PRC Number' => 'prc_number',
            'License Expiration' => 'license_expiration',
            'Major' => 'major',
            'Minor' => 'minor',
            'Specialization' => 'specialization',
            'Academic Load' => 'academic_load',
        ];
    }
}
