<?php

namespace App\Http\Requests\Api;

use App\Enums\EnrollmentType;
use App\Models\MasterData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnrollmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'curriculum_program_id' => ['nullable', 'integer', 'exists:curriculum_programs,id'],
            'academic_term_id' => ['nullable', 'integer', 'exists:academic_terms,id'],
            'campus_id' => ['required', 'integer', 'exists:campuses,id'],
            'grade_level_id' => ['required', 'integer', 'exists:grade_levels,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'program_cluster' => ['nullable', 'string', 'max:100'],
            'elective_selections' => ['nullable', 'array'],
            'elective_selections.*' => ['integer', 'exists:subjects,id'],
            'enrollment_type' => ['required', 'string', Rule::in($this->enrollmentTypeCodes())],
            'enrollment_date' => ['nullable', 'date'],
            'payment_status' => ['nullable', 'string', 'max:50'],
            'down_payment' => ['nullable', 'numeric', 'min:0'],
            'payment_schedule_date' => ['nullable', 'date'],
            'payment_schedule_details' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'capacity_override_reason' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * The configured enrollment type codes (from master data, with defaults).
     *
     * @return list<string>
     */
    protected function enrollmentTypeCodes(): array
    {
        $codes = MasterData::query()
            ->where('type', 'enrollment-type')
            ->where('is_active', true)
            ->pluck('code')
            ->filter()
            ->values()
            ->all();

        return $codes !== [] ? $codes : EnrollmentType::values();
    }
}
