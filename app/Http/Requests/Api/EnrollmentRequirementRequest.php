<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnrollmentRequirementRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('enrollment_requirements', 'code')->withoutTrashed()->ignore($this->route('id'))],
            'description' => ['nullable', 'string', 'max:500'],
            'is_required' => ['sometimes', 'boolean'],
            'type' => ['nullable', 'string', 'max:80'],
            'applicable_grade_levels' => ['nullable', 'array'],
            'applicable_enrollment_types' => ['nullable', 'array'],
            'applicable_academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'applicable_campus_ids' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}