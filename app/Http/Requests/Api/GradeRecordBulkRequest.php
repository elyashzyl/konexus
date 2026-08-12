<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class GradeRecordBulkRequest extends FormRequest
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
            'rows' => ['required', 'array', 'min:1', 'max:500'],
            'rows.*.student_id' => ['required', 'distinct', 'integer', 'exists:students,id'],
            'rows.*.raw_grade' => ['nullable', 'numeric'],
            'rows.*.remarks' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Custom attribute names for error messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'rows.*.student_id' => 'student',
            'rows.*.raw_grade' => 'raw grade',
        ];
    }
}