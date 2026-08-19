<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the Part 3 online enrollment family background payload.
 */
class PublicEnrollmentFamilyRequest extends FormRequest
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
        $parentName = ['nullable', 'string', 'max:255'];
        $parentPhone = ['nullable', 'string', 'max:30'];

        return [
            'family_monthly_income' => ['nullable', 'string', 'max:50'],
            'emergency_contact_type' => ['nullable', 'string', Rule::in(['parent', 'guardian', 'others'])],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_mobile' => ['nullable', 'string', 'max:30'],

            'father' => ['nullable', 'array'],
            'father.not_applicable' => ['nullable', 'boolean'],
            'father.first_name' => $parentName,
            'father.last_name' => $parentName,
            'father.middle_name' => $parentName,
            'father.mobile_number' => $parentPhone,
            'father.email' => ['nullable', 'email', 'max:255'],
            'father.occupation' => ['nullable', 'string', 'max:100'],
            'father.address' => ['nullable', 'string', 'max:500'],

            'mother' => ['nullable', 'array'],
            'mother.not_applicable' => ['nullable', 'boolean'],
            'mother.first_name' => $parentName,
            'mother.last_name' => $parentName,
            'mother.middle_name' => $parentName,
            'mother.maiden_name' => $parentName,
            'mother.mobile_number' => $parentPhone,
            'mother.email' => ['nullable', 'email', 'max:255'],
            'mother.occupation' => ['nullable', 'string', 'max:100'],
            'mother.address' => ['nullable', 'string', 'max:500'],

            'guardian' => ['nullable', 'array'],
            'guardian.first_name' => $parentName,
            'guardian.last_name' => $parentName,
            'guardian.middle_name' => $parentName,
            'guardian.mobile_number' => $parentPhone,
            'guardian.relationship' => ['nullable', 'string', 'max:100'],
            'guardian.address' => ['nullable', 'string', 'max:500'],
            'guardian.occupation' => ['nullable', 'string', 'max:100'],
        ];
    }
}