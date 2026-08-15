<?php

namespace App\Http\Requests\Api;

use App\Enums\Platform\InvoiceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
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
            'subscription_id' => ['required', 'integer', 'exists:subscriptions,id'],
            'billing_period_start' => ['sometimes', 'date'],
            'billing_period_end' => ['sometimes', 'nullable', 'date', 'after_or_equal:billing_period_start'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'discount' => ['sometimes', 'numeric', 'min:0'],
            'tax' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'status' => ['sometimes', Rule::enum(InvoiceStatus::class)],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}