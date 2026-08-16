<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\Concerns\ValidatesCatalogCampus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SectionRequest extends FormRequest
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
            'grade_level_id' => ['required', 'integer', 'exists:grade_levels,id'],
            'name' => ['required', 'string', 'max:100', Rule::unique('sections', 'name')->where('grade_level_id', $this->input('grade_level_id'))->withoutTrashed()->ignore($this->route('id'))],
            'code' => ['nullable', 'string', 'max:50'],
            'adviser_id' => ['nullable', 'integer', 'exists:users,id'],
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            'max_capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
