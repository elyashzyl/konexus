<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaffRequest extends FormRequest
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
            'employee_id' => ['nullable', 'exists:employees,id'],
            'employee_number' => ['nullable', 'string', 'max:50', Rule::unique('employees', 'employee_number')->withoutTrashed()],
            'first_name' => ['nullable', 'required_without:employee_id', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'required_without:employee_id', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female'],
            'mobile_number' => ['nullable', 'string', 'max:30'],
            'telephone_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'support_area' => ['nullable', 'string', 'max:100'],
        ];
    }
}
