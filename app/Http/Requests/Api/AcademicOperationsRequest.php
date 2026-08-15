<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicOperationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return match ($this->route()?->getName()) {
            'api.v1.academic-operations.programs.store' => [
                'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
                'name' => ['required', 'string', 'max:120'],
                'code' => ['required', 'string', 'max:50'],
                'framework' => ['required', 'string', Rule::in(['matatag', 'k12-2016', 'strengthened-shs', 'local'])],
                'calendar_type' => ['required', Rule::in(['quarterly', 'semester'])],
                'grade_level_ids' => ['required', 'array', 'min:1'],
                'grade_level_ids.*' => ['integer', 'exists:grade_levels,id'],
                'clusters' => ['nullable', 'array'],
                'clusters.*' => ['string', 'max:100'],
                'compliance_status' => ['required', Rule::in(['deped-aligned', 'local-adaptation'])],
                'local_adaptation_reason' => ['nullable', 'string', 'max:1000'],
            ],
            'api.v1.academic-operations.periods.store' => [
                'name' => ['required', 'string', 'max:120'], 'code' => ['required', 'string', 'max:50'], 'sequence' => ['required', 'integer', 'min:1'],
                'start_date' => ['required', 'date'], 'end_date' => ['required', 'date'],
            ],
            'api.v1.academic-operations.attendance-sessions.store' => [
                'academic_class_id' => ['required', 'integer', 'exists:academic_classes,id'], 'attendance_date' => ['required', 'date'],
            ],
            'api.v1.academic-operations.attendance-sessions.records' => [
                'records' => ['required', 'array', 'min:1'], 'records.*.student_id' => ['required', 'integer', 'exists:students,id'],
                'records.*.status' => ['required', Rule::in(['present', 'absent', 'late', 'excused', 'school-activity'])],
                'records.*.minutes_late' => ['nullable', 'integer', 'min:0', 'max:1440'], 'records.*.remarks' => ['nullable', 'string', 'max:1000'],
            ],
            'api.v1.academic-operations.assessments.store' => [
                'subject_offering_id' => ['required', 'integer', 'exists:subject_offerings,id'], 'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
                'component' => ['required', 'string', 'max:50'], 'title' => ['required', 'string', 'max:255'], 'max_score' => ['required', 'numeric', 'gt:0'], 'display_order' => ['nullable', 'integer', 'min:0'],
            ],
            'api.v1.academic-operations.assessments.scores' => [
                'scores' => ['required', 'array', 'min:1'], 'scores.*.student_subject_enrollment_id' => ['required', 'integer', 'exists:student_subject_enrollments,id'],
                'scores.*.score' => ['nullable', 'numeric', 'min:0'],
            ],
            'api.v1.academic-operations.promotions.decide' => [
                'decision' => ['nullable', Rule::in(['promoted', 'conditionally-promoted', 'retained', 'transferred-out', 'incomplete'])],
                'override_reason' => ['nullable', 'string', 'max:1000'],
            ],
            default => [],
        };
    }
}
