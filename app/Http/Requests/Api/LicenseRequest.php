<?php

namespace App\Http\Requests\Api;

use App\Enums\Platform\LicenseStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LicenseRequest extends FormRequest
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
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'plan_id' => ['nullable', 'integer', 'exists:subscription_plans,id'],
            'license_key' => ['nullable', 'string', 'max:255'],
            'issued_date' => ['sometimes', 'date'],
            'start_date' => ['sometimes', 'date'],
            'expiration_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', Rule::enum(LicenseStatus::class)],
            'max_users' => ['nullable', 'integer', 'min:0'],
            'max_students' => ['nullable', 'integer', 'min:0'],
            'max_branches' => ['nullable', 'integer', 'min:0'],
            'max_storage_mb' => ['nullable', 'integer', 'min:0'],
            'features' => ['sometimes', 'array'],
            'features.*' => ['string'],
        ];
    }
}