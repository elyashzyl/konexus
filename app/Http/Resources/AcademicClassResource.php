<?php

namespace App\Http\Resources;

use App\Models\AcademicClass;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AcademicClass */
class AcademicClassResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $showMembers = $request->boolean('members') || $this->relationLoaded('activeMembers');

        return [
            'id' => $this->id,
            'academic_year_id' => $this->academic_year_id,
            'academic_term_id' => $this->academic_term_id,
            'campus_id' => $this->campus_id,
            'grade_level_id' => $this->grade_level_id,
            'section_id' => $this->section_id,
            'adviser_teacher_id' => $this->adviser_teacher_id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'member_count' => $this->when($showMembers, fn () => count($this->activeMembers ?? [])),
            'members' => $this->when($showMembers, fn () => ($this->activeMembers ?? collect())->map(fn ($member) => [
                'id' => $member->id,
                'student_id' => $member->student_id,
                'source' => $member->source,
                'academic_status' => $member->academic_status,
                'student' => $member->relationLoaded('student') && $member->student ? [
                    'id' => $member->student->id,
                    'student_number' => $member->student->student_number,
                    'name' => $member->student->full_name ?? null,
                ] : null,
            ])->values()),
            'academic_year' => $this->whenLoaded('academicYear', fn () => $this->academicYear ? [
                'id' => $this->academicYear->id,
                'name' => $this->academicYear->name,
            ] : null),
            'academic_term' => $this->whenLoaded('academicTerm', fn () => $this->academicTerm ? [
                'id' => $this->academicTerm->id,
                'name' => $this->academicTerm->name,
            ] : null),
            'campus' => $this->whenLoaded('campus', fn () => $this->campus ? [
                'id' => $this->campus->id,
                'name' => $this->campus->name,
            ] : null),
            'grade_level' => $this->whenLoaded('gradeLevel', fn () => $this->gradeLevel ? [
                'id' => $this->gradeLevel->id,
                'name' => $this->gradeLevel->name,
            ] : null),
            'section' => $this->whenLoaded('section', fn () => $this->section ? [
                'id' => $this->section->id,
                'name' => $this->section->name,
            ] : null),
            'adviser' => $this->whenLoaded('adviser', fn () => $this->adviser ? [
                'id' => $this->adviser->id,
                'name' => $this->adviser->employee?->full_name,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}