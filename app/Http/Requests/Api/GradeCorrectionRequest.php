<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class GradeCorrectionRequest extends FormRequest
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
            'grade_record_id' => ['required', 'integer', 'exists:grade_records,id'],
            'proposed_grade' => ['required', 'numeric'],
            'reason' => ['required', 'string', 'max:1000'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}