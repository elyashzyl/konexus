<?php

namespace App\Http\Resources;

use App\Models\AcademicClassStudent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AcademicClassStudent */
class AcademicClassStudentResource extends JsonResource
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
            'academic_class_id' => $this->academic_class_id,
            'student_id' => $this->student_id,
            'enrollment_id' => $this->enrollment_id,
            'source' => $this->source,
            'academic_status' => $this->academic_status,
            'remarks' => $this->remarks,
            'is_active' => $this->is_active,
            'student' => $this->whenLoaded('student', fn () => $this->student ? [
                'id' => $this->student->id,
                'student_number' => $this->student->student_number,
                'lrn' => $this->student->lrn,
                'name' => $this->student->full_name,
            ] : null),
            'enrollment' => $this->whenLoaded('enrollment', fn () => $this->enrollment ? [
                'id' => $this->enrollment->id,
                'status' => $this->enrollment->status,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}