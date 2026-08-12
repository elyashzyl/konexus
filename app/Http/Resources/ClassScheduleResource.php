<?php

namespace App\Http\Resources;

use App\Models\ClassSchedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ClassSchedule */
class ClassScheduleResource extends JsonResource
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
            'subject_offering_id' => $this->subject_offering_id,
            'subject_id' => $this->subject_id,
            'teacher_id' => $this->teacher_id,
            'room_id' => $this->room_id,
            'day' => $this->day,
            'day_label' => $this->day ? ucfirst($this->day) : null,
            'start_time' => $this->start_time?->format('H:i'),
            'end_time' => $this->end_time?->format('H:i'),
            'time_range' => $this->time_range,
            'conflict_override' => $this->conflict_override,
            'conflict_reason' => $this->conflict_reason,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'section' => $this->whenLoaded('section', fn () => $this->section ? [
                'id' => $this->section->id,
                'name' => $this->section->name,
            ] : null),
            'subject' => $this->whenLoaded('subject', fn () => $this->subject ? [
                'id' => $this->subject->id,
                'name' => $this->subject->name,
                'code' => $this->subject->code,
            ] : null),
            'teacher' => $this->whenLoaded('teacher', fn () => $this->teacher ? [
                'id' => $this->teacher->id,
                'name' => $this->teacher->employee?->full_name,
            ] : null),
            'room' => $this->whenLoaded('room', fn () => $this->room ? [
                'id' => $this->room->id,
                'name' => $this->room->name,
                'code' => $this->room->code,
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