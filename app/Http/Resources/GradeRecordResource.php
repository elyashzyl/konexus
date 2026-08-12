<?php

namespace App\Http\Resources;

use App\Models\GradeRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GradeRecord */
class GradeRecordResource extends JsonResource
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
            'student_id' => $this->student_id,
            'academic_year_id' => $this->academic_year_id,
            'academic_term_id' => $this->academic_term_id,
            'grade_level_id' => $this->grade_level_id,
            'section_id' => $this->section_id,
            'subject_id' => $this->subject_id,
            'subject_offering_id' => $this->subject_offering_id,
            'teacher_id' => $this->teacher_id,
            'raw_grade' => $this->raw_grade === null ? null : (float) $this->raw_grade,
            'final_grade' => $this->final_grade === null ? null : (float) $this->final_grade,
            'remarks' => $this->remarks,
            'status' => $this->status,
            'status_label' => $this->status ? \App\Enums\GradeStatus::tryFrom($this->status)?->label() ?? ucfirst($this->status) : null,
            'is_editable' => $this->isEditable(),
            'submitted_by' => $this->submitted_by,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'published_at' => $this->published_at?->toISOString(),
            'is_active' => $this->is_active,
            'student' => $this->whenLoaded('student', fn () => $this->student ? [
                'id' => $this->student->id,
                'student_number' => $this->student->student_number,
                'name' => $this->student->full_name,
            ] : null),
            'subject' => $this->whenLoaded('subject', fn () => $this->subject ? [
                'id' => $this->subject->id,
                'name' => $this->subject->name,
                'code' => $this->subject->code,
            ] : null),
            'section' => $this->whenLoaded('section', fn () => $this->section ? [
                'id' => $this->section->id,
                'name' => $this->section->name,
            ] : null),
            'teacher' => $this->whenLoaded('teacher', fn () => $this->teacher ? [
                'id' => $this->teacher->id,
                'name' => $this->teacher->employee?->full_name,
            ] : null),
            'subject_offering' => $this->whenLoaded('subjectOffering', fn () => $this->subjectOffering ? [
                'id' => $this->subjectOffering->id,
                'display_name' => $this->subjectOffering->display_name,
            ] : null),
            'corrections' => $this->whenLoaded('corrections', fn () => $this->corrections->map(fn ($correction) => [
                'id' => $correction->id,
                'status' => $correction->status,
                'current_grade' => $correction->current_grade === null ? null : (float) $correction->current_grade,
                'proposed_grade' => $correction->proposed_grade === null ? null : (float) $correction->proposed_grade,
                'reason' => $correction->reason,
                'created_at' => $correction->created_at?->toISOString(),
            ])->values()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}