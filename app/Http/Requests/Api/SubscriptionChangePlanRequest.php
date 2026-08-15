<?php

namespace App\Http\Requests\Api;

use App\Enums\Platform\BillingCycle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionChangePlanRequest extends FormRequest
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
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'billing_cycle' => ['sometimes', Rule::enum(BillingCycle::class)],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}