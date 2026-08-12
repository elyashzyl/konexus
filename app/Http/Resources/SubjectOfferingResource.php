<?php

namespace App\Http\Resources;

use App\Models\SubjectOffering;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SubjectOffering */
class SubjectOfferingResource extends JsonResource
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
            'academic_year_id' => $this->academic_year_id,
            'academic_term_id' => $this->academic_term_id,
            'campus_id' => $this->campus_id,
            'grade_level_id' => $this->grade_level_id,
            'section_id' => $this->section_id,
            'subject_id' => $this->subject_id,
            'teacher_id' => $this->teacher_id,
            'department_id' => $this->department_id,
            'room_id' => $this->room_id,
            'units' => (float) $this->units,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'display_name' => $this->display_name,
            'subject' => $this->whenLoaded('subject', fn () => $this->subject ? [
                'id' => $this->subject->id,
                'name' => $this->subject->name,
                'code' => $this->subject->code,
            ] : null),
            'section' => $this->whenLoaded('section', fn () => $this->section ? [
                'id' => $this->section->id,
                'name' => $this->section->name,
            ] : null),
            'grade_level' => $this->whenLoaded('gradeLevel', fn () => $this->gradeLevel ? [
                'id' => $this->gradeLevel->id,
                'name' => $this->gradeLevel->name,
            ] : null),
            'teacher' => $this->whenLoaded('teacher', fn () => $this->teacher ? [
                'id' => $this->teacher->id,
                'name' => $this->teacher->employee?->full_name ?? ($this->teacher->employee?->first_name ?? null),
            ] : null),
            'department' => $this->whenLoaded('department', fn () => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ] : null),
            'room' => $this->whenLoaded('room', fn () => $this->room ? [
                'id' => $this->room->id,
                'name' => $this->room->name,
                'code' => $this->room->code,
            ] : null),
            'academic_year' => $this->whenLoaded('academicYear', fn () => $this->academicYear ? [
                'id' => $this->academicYear->id,
                'name' => $this->academicYear->name,
            ] : null),
            'academic_term' => $this->whenLoaded('academicTerm', fn () => $this->academicTerm ? [
                'id' => $this->academicTerm->id,
                'name' => $this->academicTerm->name,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}