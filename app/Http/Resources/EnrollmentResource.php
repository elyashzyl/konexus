<?php

namespace App\Http\Resources;

use App\Enums\EnrollmentType;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Enrollment */
class EnrollmentResource extends JsonResource
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
            'enrollment_number' => $this->enrollment_number,
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'status_label' => $this->display_status_label,
            'enrollment_type' => $this->enrollment_type,
            'enrollment_type_label' => EnrollmentType::tryFrom($this->enrollment_type)?->label() ?? ucfirst((string) $this->enrollment_type),
            'enrollment_date' => $this->enrollment_date?->toDateString(),
            'date_enrolled' => $this->date_enrolled?->toDateString(),

            'student_id' => $this->student_id,
            'academic_year_id' => $this->academic_year_id,
            'academic_term_id' => $this->academic_term_id,
            'campus_id' => $this->campus_id,
            'grade_level_id' => $this->grade_level_id,
            'section_id' => $this->section_id,

            'student' => $this->whenLoaded('student', fn () => $this->student ? new StudentSearchResource($this->student) : null),
            'academic_year' => $this->whenLoaded('academicYear', fn () => $this->academicYear ? [
                'id' => $this->academicYear->id,
                'name' => $this->academicYear->name,
                'code' => $this->academicYear->code,
            ] : null),
            'academic_term' => $this->whenLoaded('academicTerm', fn () => $this->academicTerm ? [
                'id' => $this->academicTerm->id,
                'name' => $this->academicTerm->name,
            ] : null),
            'campus' => $this->whenLoaded('campus', fn () => $this->campus ? [
                'id' => $this->campus->id,
                'name' => $this->campus->name,
                'code' => $this->campus->code,
            ] : null),
            'grade_level' => $this->whenLoaded('gradeLevel', fn () => $this->gradeLevel ? [
                'id' => $this->gradeLevel->id,
                'name' => $this->gradeLevel->name,
                'code' => $this->gradeLevel->code,
                'education_level' => $this->gradeLevel->education_level,
            ] : null),
            'section' => $this->whenLoaded('section', fn () => $this->section ? [
                'id' => $this->section->id,
                'name' => $this->section->name,
                'max_capacity' => $this->section->max_capacity,
            ] : null),

            'requirements' => $this->whenLoaded('requirementItems', fn () => $this->requirementItems->values()->map(fn ($item) => new EnrollmentRequirementItemResource($item))),
            'requirements_met' => $this->allRequirementsSatisfied(),
            'documents' => $this->whenLoaded('documents', fn () => $this->documents->map(fn ($doc) => new EnrollmentDocumentResource($doc))),

            'transfer_date' => $this->transfer_date?->toDateString(),
            'transfer_type' => $this->transfer_type,
            'transfer_destination' => $this->transfer_destination,
            'transfer_destination_school' => $this->transfer_destination_school,
            'transfer_reason' => $this->transfer_reason,
            'transfer_remarks' => $this->transfer_remarks,

            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'initial_payment_status' => $this->initial_payment_status,
            'department' => $this->department,
            'incoming_level' => $this->incoming_level,
            'down_payment' => $this->down_payment,
            'payment_schedule_date' => $this->payment_schedule_date?->toDateString(),
            'payment_schedule_details' => $this->payment_schedule_details,

            'approved_by' => $this->whenLoaded('approvedBy', fn () => $this->approvedBy?->name ?? null),
            'approved_at' => $this->approved_at?->toISOString(),
            'principal_approved_by' => $this->whenLoaded('principalApprovedBy', fn () => $this->principalApprovedBy?->name ?? null),
            'principal_approved_at' => $this->principal_approved_at?->toISOString(),
            'registrar_reviewed_by' => $this->whenLoaded('registrarReviewedBy', fn () => $this->registrarReviewedBy?->name ?? null),
            'registrar_reviewed_at' => $this->registrar_reviewed_at?->toISOString(),
            'payment_recorded_by' => $this->whenLoaded('paymentRecordedBy', fn () => $this->paymentRecordedBy?->name ?? null),
            'payment_recorded_at' => $this->payment_recorded_at?->toISOString(),
            'final_checked_by' => $this->whenLoaded('finalCheckedBy', fn () => $this->finalCheckedBy?->name ?? null),
            'final_checked_at' => $this->final_checked_at?->toISOString(),
            'rejected_by' => $this->whenLoaded('rejectedBy', fn () => $this->rejectedBy?->name ?? null),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'rejection_reason' => $this->rejection_reason,
            'withdrawn_at' => $this->withdrawn_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancellation_reason' => $this->cancellation_reason,

            'notes' => $this->notes,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}