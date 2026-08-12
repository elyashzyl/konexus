<?php

namespace App\Http\Requests\Api;

use App\Enums\TransferType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnrollmentTransferRequest extends FormRequest
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
            'transfer_type' => ['required', 'string', Rule::in(array_column(TransferType::cases(), 'value'))],
            'transfer_destination' => ['nullable', 'string', 'max:255'],
            'transfer_destination_school' => ['nullable', 'string', 'max:255'],
            'transfer_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'to_campus_id' => ['nullable', 'integer', 'exists:campuses,id'],
            'to_grade_level_id' => ['nullable', 'integer', 'exists:grade_levels,id'],
            'to_section_id' => ['nullable', 'integer', 'exists:sections,id'],
        ];
    }
}