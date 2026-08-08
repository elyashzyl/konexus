<?php

namespace App\Http\Requests\Api;

use App\Enums\EducationLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GradeLevelRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100', Rule::unique('grade_levels', 'name')->withoutTrashed()->ignore($this->route('id'))],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('grade_levels', 'code')->withoutTrashed()->ignore($this->route('id'))],
            'short_name' => ['nullable', 'string', 'max:50'],
            'education_level' => ['required', Rule::in(array_column(EducationLevel::toSeedData(), 'value'))],
            'sequence' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
