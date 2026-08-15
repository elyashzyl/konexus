<?php

namespace App\Http\Requests\Api;

use App\Enums\Platform\BillingCycle;
use App\Enums\Platform\ExpirationBehavior;
use App\Enums\Platform\SubscriptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionRequest extends FormRequest
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
            'status' => ['sometimes', Rule::enum(SubscriptionStatus::class)],
            'start_date' => ['sometimes', 'date'],
            'expiration_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
            'billing_cycle' => ['sometimes', Rule::enum(BillingCycle::class)],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'auto_renewal' => ['sometimes', 'boolean'],
            'grace_days' => ['sometimes', 'integer', 'min:0', 'max:3650'],
            'expiration_behavior' => ['sometimes', Rule::enum(ExpirationBehavior::class)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}