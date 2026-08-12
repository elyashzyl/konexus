<?php

namespace App\Http\Resources;

use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Teacher */
class TeacherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', fn () => $this->employee ? [
                'id' => $this->employee->id,
                'employee_number' => $this->employee->employee_number,
                'name' => $this->employee->full_name,
                'first_name' => $this->employee->first_name,
                'middle_name' => $this->employee->middle_name,
                'last_name' => $this->employee->last_name,
                'gender' => $this->employee->gender,
                'email' => $this->employee->email,
                'position' => $this->employee->position,
            ] : null),
            'prc_number' => $this->prc_number,
            'license_expiration' => $this->license_expiration?->toDateString(),
            'major' => $this->major,
            'minor' => $this->minor,
            'advisory_class_id' => $this->advisory_class_id,
            'advisory_class' => $this->whenLoaded('advisoryClass', fn () => $this->advisoryClass ? [
                'id' => $this->advisoryClass->id,
                'name' => $this->advisoryClass->name,
            ] : null),
            'department_id' => $this->department_id,
            'department' => $this->whenLoaded('department', fn () => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ] : null),
            'specialization' => $this->specialization,
            'academic_load' => $this->academic_load,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
