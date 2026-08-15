<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the Part 2 online enrollment student information payload.
 */
class PublicEnrollmentStudentRequest extends FormRequest
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
            'school_student_id' => ['nullable', 'string', 'max:50'],
            'lrn' => ['nullable', 'digits:12'],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'extension_name' => ['nullable', 'string', 'max:20'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'citizenship' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', 'string', 'max:100'],
            'mobile_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'ethnicity' => ['nullable', 'string', 'max:100'],
            'is_indigenous' => ['nullable', 'boolean'],
            'mother_tongue' => ['nullable', 'string', 'max:100'],
            'telephone_number' => ['nullable', 'string', 'max:30'],
            'current_address' => ['nullable', 'string', 'max:500'],
            'current_province' => ['nullable', 'string', 'max:100'],
            'current_city' => ['nullable', 'string', 'max:100'],
            'current_barangay' => ['nullable', 'string', 'max:100'],
            'interests' => ['nullable', 'array'],
            'interests.*' => ['required', Rule::in(['academics', 'sports', 'service-and-sustainability', 'arts'])],
        ];
    }
}