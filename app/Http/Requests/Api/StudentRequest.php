<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
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
        $id = $this->route('id');

        return [
            'student_number' => ['nullable', 'string', 'max:50', Rule::unique('students', 'student_number')->withoutTrashed()->ignore($id)],
            'lrn' => ['nullable', 'digits:12', Rule::unique('students', 'lrn')->withoutTrashed()->ignore($id)],
            'school_student_id' => ['nullable', 'string', 'max:50', Rule::unique('students', 'school_student_id')->withoutTrashed()->ignore($id)],
            'rfid_number' => ['nullable', 'string', 'max:50', Rule::unique('students', 'rfid_number')->withoutTrashed()->ignore($id)],
            'qr_code' => ['nullable', 'string', 'max:50', Rule::unique('students', 'qr_code')->withoutTrashed()->ignore($id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('students', 'email')->withoutTrashed()->ignore($id)],

            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'extension_name' => ['nullable', 'string', 'max:20'],
            'nickname' => ['nullable', 'string', 'max:100'],
            'gender' => ['required', 'in:male,female'],
            'birth_date' => ['required', 'date', 'before:today'],
            'place_of_birth' => ['nullable', 'string', 'max:255'],
            'civil_status' => ['nullable', 'string', 'max:50'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'citizenship' => ['nullable', 'string', 'max:100'],
            'religion' => ['nullable', 'string', 'max:100'],
            'ethnicity' => ['nullable', 'string', 'max:100'],
            'mother_tongue' => ['nullable', 'string', 'max:100'],
            'blood_type' => ['nullable', 'string', 'max:5'],
            'status' => ['nullable', 'string', 'max:50'],

            'mobile_number' => ['nullable', 'string', 'max:30'],
            'telephone_number' => ['nullable', 'string', 'max:30'],

            'current_address' => ['nullable', 'string', 'max:500'],
            'current_province' => ['nullable', 'string', 'max:100'],
            'current_city' => ['nullable', 'string', 'max:100'],
            'current_municipality' => ['nullable', 'string', 'max:100'],
            'current_barangay' => ['nullable', 'string', 'max:100'],
            'current_zip_code' => ['nullable', 'string', 'max:10'],
            'permanent_address' => ['nullable', 'string', 'max:500'],
            'permanent_province' => ['nullable', 'string', 'max:100'],
            'permanent_city' => ['nullable', 'string', 'max:100'],
            'permanent_municipality' => ['nullable', 'string', 'max:100'],
            'permanent_barangay' => ['nullable', 'string', 'max:100'],
            'permanent_zip_code' => ['nullable', 'string', 'max:10'],

            'height' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'medical_conditions' => ['nullable', 'string', 'max:2000'],
            'food_allergies' => ['nullable', 'string', 'max:2000'],
            'medicine_allergies' => ['nullable', 'string', 'max:2000'],
            'preferred_hospital' => ['nullable', 'string', 'max:255'],
            'medical_notes' => ['nullable', 'string', 'max:2000'],
            'emergency_medical_notes' => ['nullable', 'string', 'max:2000'],

            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'emergency_contact_mobile' => ['nullable', 'string', 'max:30'],
            'emergency_contact_telephone' => ['nullable', 'string', 'max:30'],
            'emergency_contact_address' => ['nullable', 'string', 'max:500'],

            'parent_ids' => ['nullable', 'array'],
            'parent_ids.*' => ['integer', 'exists:parents,id'],
            'guardian_ids' => ['nullable', 'array'],
            'guardian_ids.*' => ['integer', 'exists:guardians,id'],

            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
