<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class GradeCorrectionActionRequest extends FormRequest
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
            'approval_remarks' => ['nullable', 'string', 'max:1000'],
            'approved_by' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}