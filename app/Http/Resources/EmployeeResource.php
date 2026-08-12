<?php

namespace App\Http\Resources;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Employee */
class EmployeeResource extends JsonResource
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
            'employee_number' => $this->employee_number,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'extension_name' => $this->extension_name,
            'name' => $this->full_name,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date?->toDateString(),
            'mobile_number' => $this->mobile_number,
            'telephone_number' => $this->telephone_number,
            'email' => $this->email,
            'address' => $this->address,
            'employment_type' => $this->employment_type,
            'department_id' => $this->department_id,
            'department' => $this->whenLoaded('department', fn () => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ] : null),
            'position' => $this->position,
            'hiring_type' => $this->hiring_type,
            'date_hired' => $this->date_hired?->toDateString(),
            'status' => $this->status,
            'teacher' => new TeacherResource($this->whenLoaded('teacher')),
            'staff' => new StaffResource($this->whenLoaded('staff')),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
