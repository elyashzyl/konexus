<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicClassRequest extends FormRequest
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
            'academic_term_id' => ['nullable', 'integer', 'exists:academic_terms,id'],
            'campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
            'grade_level_id' => ['required', 'integer', 'exists:grade_levels,id'],
            'section_id' => ['required', 'integer', 'exists:sections,id', Rule::unique('academic_classes', 'section_id')->where(fn ($query) => $query
                ->where('academic_year_id', $this->input('academic_year_id'))
                ->where('academic_term_id', $this->input('academic_term_id'))
                ->where('campus_id', $this->input('campus_id')))->withoutTrashed()->ignore($ignore)],
            'adviser_teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'name' => ['nullable', 'string', 'max:120'],
            'status' => ['sometimes', 'string', 'in:draft,active,archived'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}