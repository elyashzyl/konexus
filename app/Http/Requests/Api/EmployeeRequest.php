<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
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
            'employee_number' => ['nullable', 'string', 'max:50', Rule::unique('employees', 'employee_number')->withoutTrashed()->ignore($id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->withoutTrashed()->ignore($id)],

            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'extension_name' => ['nullable', 'string', 'max:20'],
            'gender' => ['required', 'in:male,female'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'mobile_number' => ['nullable', 'string', 'max:30'],
            'telephone_number' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'employment_type' => ['required', 'in:teaching,staff'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position' => ['nullable', 'string', 'max:255'],
            'hiring_type' => ['nullable', 'string', 'max:255'],
            'date_hired' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
            'campus_ids' => ['sometimes', 'array'],
            'campus_ids.*' => ['integer', 'exists:campuses,id'],
        ];
    }
}
