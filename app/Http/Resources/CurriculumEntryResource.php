<?php

namespace App\Http\Resources;

use App\Models\CurriculumEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CurriculumEntry */
class CurriculumEntryResource extends JsonResource
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
            'subject_id' => $this->subject_id,
            'subject' => $this->whenLoaded('subject', fn () => $this->subject ? [
                'id' => $this->subject->id,
                'name' => $this->subject->name,
                'code' => $this->subject->code,
            ] : null),
            'subject_type' => $this->subject_type,
            'units' => (float) $this->units,
            'is_required' => $this->is_required,
            'display_order' => $this->display_order,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'academic_year' => $this->whenLoaded('academicYear', fn () => $this->academicYear ? [
                'id' => $this->academicYear->id,
                'name' => $this->academicYear->name,
            ] : null),
            'academic_term' => $this->whenLoaded('academicTerm', fn () => $this->academicTerm ? [
                'id' => $this->academicTerm->id,
                'name' => $this->academicTerm->name,
            ] : null),
            'grade_level' => $this->whenLoaded('gradeLevel', fn () => $this->gradeLevel ? [
                'id' => $this->gradeLevel->id,
                'name' => $this->gradeLevel->name,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}