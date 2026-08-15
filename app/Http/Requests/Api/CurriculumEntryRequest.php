<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurriculumEntryRequest extends FormRequest
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
        $ignore = $this->route('id');

        return [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'curriculum_program_id' => ['nullable', 'integer', 'exists:curriculum_programs,id'],
            'academic_term_id' => ['nullable', 'integer', 'exists:academic_terms,id'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
            'grade_level_id' => ['required', 'integer', 'exists:grade_levels,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id', Rule::unique('curriculum_entries', 'subject_id')->where(fn ($query) => $query
                ->where('academic_year_id', $this->input('academic_year_id'))
                ->where('academic_term_id', $this->input('academic_term_id'))
                ->where('campus_id', $this->input('campus_id'))
                ->where('grade_level_id', $this->input('grade_level_id')))->withoutTrashed()->ignore($ignore)],
            'subject_type' => ['sometimes', 'string', 'in:core,applied,specialized,elective,academic-elective,techpro-elective,other', 'max:30'],
            'units' => ['required', 'numeric', 'min:0.25', 'max:20'],
            'weekly_minutes' => ['nullable', 'integer', 'min:1', 'max:3000'],
            'prerequisite_subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'eligible_clusters' => ['nullable', 'array'],
            'eligible_clusters.*' => ['string', 'max:100'],
            'assessment_policy' => ['nullable', 'array'],
            'assessment_policy.*' => ['numeric', 'min:0'],
            'is_required' => ['sometimes', 'boolean'],
            'display_order' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'string', 'in:draft,active,archived'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
