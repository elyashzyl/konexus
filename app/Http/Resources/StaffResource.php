<?php

namespace App\Http\Resources;

use App\Models\Campus;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Staff */
class StaffResource extends JsonResource
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
                'department' => $this->employee->relationLoaded('department') && $this->employee->department ? [
                    'id' => $this->employee->department->id,
                    'name' => $this->employee->department->name,
                ] : null,
                'campuses' => $this->whenLoaded('employee.campuses', fn () => $this->employee->campuses->map(fn (Campus $campus) => [
                    'id' => $campus->id,
                    'name' => $campus->name,
                ])),
            ] : null),
            'support_area' => $this->support_area,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
