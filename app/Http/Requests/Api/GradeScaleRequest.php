<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeScaleRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120', Rule::unique('grade_scales', 'name')->withoutTrashed()->ignore($ignore)],
            'code' => ['nullable', 'string', 'max:30'],
            'min_grade' => ['required', 'numeric'],
            'max_grade' => ['required', 'numeric', 'gt:min_grade'],
            'minimum_passing_grade' => ['required', 'numeric', 'between:min_grade,max_grade'],
            'decimal_precision' => ['sometimes', 'integer', 'min:0', 'max:4'],
            'rounding' => ['sometimes', 'string', 'in:standard,half-up,ceil,floor'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}