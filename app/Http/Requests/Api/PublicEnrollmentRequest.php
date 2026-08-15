<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the Part 1 online enrollment application payload.
 */
class PublicEnrollmentRequest extends FormRequest
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
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
            'department' => ['required', 'string', Rule::in(['pre-school', 'grade-school', 'junior-high', 'senior-high'])],
            'strand' => ['nullable', 'string', 'max:100', Rule::requiredIf($this->input('department') === 'senior-high')],
            'status' => ['required', 'string', Rule::in(['continuing', 'returning', 'transferee'])],
            'incoming_level' => ['required', 'string', 'max:50'],
            'track' => ['required', 'string', Rule::in(['english', 'chinese', 'integrated'])],
            'email' => ['required', 'email', 'max:255'],
            'mobile_number' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9][0-9 ()-]{7,18}$/'],
        ];
    }
}