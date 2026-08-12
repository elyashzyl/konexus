<?php

namespace App\Http\Requests\Api;

use App\Enums\RequirementItemStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnrollmentRequirementItemRequest extends FormRequest
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
            'status' => ['required', 'string', Rule::in(array_column(RequirementItemStatus::cases(), 'value'))],
            'remarks' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('verified')) {
            $this->merge(['status' => $this->boolean('verified') ? RequirementItemStatus::VERIFIED->value : RequirementItemStatus::REJECTED->value]);
        }
    }
}