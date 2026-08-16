<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\Concerns\ValidatesCatalogCampus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
{
    use ValidatesCatalogCampus;

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
            ...$this->catalogCampusRules(),
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('subjects', 'code')->withoutTrashed()->ignore($this->route('id'))],
            'description' => ['nullable', 'string', 'max:1000'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'grade_level_id' => ['nullable', 'integer', 'exists:grade_levels,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
