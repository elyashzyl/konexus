<?php

namespace App\Http\Requests\Api;

use App\Enums\Platform\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
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
            'invoice_id' => ['required', 'integer', 'exists:subscription_invoices,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['sometimes', 'date'],
            'payment_method' => ['sometimes', Rule::enum(PaymentMethod::class)],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'in:pending,completed,failed'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}