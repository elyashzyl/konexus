<?php

namespace App\Http\Resources;

use App\Models\GradeCorrection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GradeCorrection */
class GradeCorrectionResource extends JsonResource
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
            'grade_record_id' => $this->grade_record_id,
            'student_id' => $this->student_id,
            'subject_id' => $this->subject_id,
            'academic_term_id' => $this->academic_term_id,
            'current_grade' => $this->current_grade === null ? null : (float) $this->current_grade,
            'proposed_grade' => $this->proposed_grade === null ? null : (float) $this->proposed_grade,
            'reason' => $this->reason,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'requested_by' => $this->requested_by,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'approval_remarks' => $this->approval_remarks,
            'is_active' => $this->is_active,
            'grade_record' => $this->whenLoaded('gradeRecord', fn () => $this->gradeRecord ? [
                'id' => $this->gradeRecord->id,
                'final_grade' => $this->gradeRecord->final_grade === null ? null : (float) $this->gradeRecord->final_grade,
                'status' => $this->gradeRecord->status,
            ] : null),
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
            'requested_by_user' => $this->whenLoaded('requestedBy', fn () => $this->requestedBy ? [
                'id' => $this->requestedBy->id,
                'name' => $this->requestedBy->name,
            ] : null),
            'approved_by_user' => $this->whenLoaded('approvedBy', fn () => $this->approvedBy ? [
                'id' => $this->approvedBy->id,
                'name' => $this->approvedBy->name,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}