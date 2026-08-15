<?php

namespace App\Http\Requests\Api;

use App\Enums\Platform\BillingCycle;
use App\Enums\Platform\SubscriptionFeature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriptionPlanRequest extends FormRequest
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
        $codes = array_column(SubscriptionFeature::toOptions(), 'value');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('subscription_plans', 'code')->withoutTrashed()->ignore($this->route('id'))],
            'description' => ['nullable', 'string', 'max:2000'],
            'billing_cycle' => ['sometimes', Rule::enum(BillingCycle::class)],
            'monthly_price' => ['sometimes', 'numeric', 'min:0'],
            'annual_price' => ['sometimes', 'numeric', 'min:0'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'max_students' => ['nullable', 'integer', 'min:0'],
            'max_staff' => ['nullable', 'integer', 'min:0'],
            'max_branches' => ['nullable', 'integer', 'min:0'],
            'max_users' => ['nullable', 'integer', 'min:0'],
            'max_storage_mb' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'display_order' => ['sometimes', 'integer', 'min:0'],
            'features' => ['sometimes', 'array'],
            'features.*' => ['required', 'string', Rule::in($codes)],
        ];
    }
}