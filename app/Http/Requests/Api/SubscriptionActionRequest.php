<?php

namespace App\Http\Requests\Api;

use App\Enums\Platform\BillingCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for lifecycle actions that accept an optional reason and, for
 * some transitions, a billing cycle / expected resume date.
 */
class SubscriptionActionRequest extends FormRequest
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
            'reason' => ['nullable', 'string', 'max:1000'],
            'billing_cycle' => ['sometimes', Rule::enum(BillingCycle::class)],
            'auto_renewal' => ['sometimes', 'boolean'],
            'expected_resume_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}