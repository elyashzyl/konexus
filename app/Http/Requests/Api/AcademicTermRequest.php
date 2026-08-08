<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcademicTermRequest extends FormRequest
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
        $yearId = $this->input('academic_year_id');
        $ignore = $this->route('id');

        return [
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:100', Rule::unique('academic_terms', 'name')->where('academic_year_id', $yearId)->withoutTrashed()->ignore($ignore)],
            'code' => ['nullable', 'string', 'max:50'],
            'sequence' => ['sometimes', 'integer', 'min:1', Rule::unique('academic_terms', 'sequence')->where('academic_year_id', $yearId)->withoutTrashed()->ignore($ignore)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
