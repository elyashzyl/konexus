<?php

namespace App\Http\Resources;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Student */
class StudentSearchResource extends JsonResource
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
            'student_number' => $this->student_number,
            'lrn' => $this->lrn,
            'name' => $this->full_name,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date?->toDateString(),
            'age' => $this->age,
            'email' => $this->email,
            'mobile_number' => $this->mobile_number,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'latest_enrollment' => $this->whenLoaded('latestEnrollment', fn () => $this->latestEnrollment ? [
                'id' => $this->latestEnrollment->id,
                'academic_year' => $this->latestEnrollment->academicYear?->name,
                'grade_level' => $this->latestEnrollment->gradeLevel?->name,
                'section' => $this->latestEnrollment->section?->name,
                'campus' => $this->latestEnrollment->campus?->name,
                'status' => $this->latestEnrollment->status,
                'status_label' => $this->latestEnrollment->display_status_label,
            ] : null),
        ];
    }
}