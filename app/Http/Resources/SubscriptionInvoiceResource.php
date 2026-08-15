<?php

namespace App\Http\Resources;

use App\Models\SubscriptionInvoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SubscriptionInvoice */
class SubscriptionInvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'subscription_id' => $this->subscription_id,
            'tenant_id' => $this->tenant_id,
            'billing_period_start' => $this->billing_period_start?->toDateString(),
            'billing_period_end' => $this->billing_period_end?->toDateString(),
            'amount' => (float) $this->amount,
            'discount' => (float) $this->discount,
            'tax' => (float) $this->tax,
            'total' => (float) $this->total,
            'currency' => $this->currency,
            'status' => $this->status,
            'due_date' => $this->due_date?->toDateString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'payment_reference' => $this->payment_reference,
            'payment_method' => $this->payment_method,
            'notes' => $this->notes,
            'paid_amount' => $this->when($request->boolean('with_balance'), fn () => $this->paidAmount()),
            'balance' => $this->when($request->boolean('with_balance'), fn () => $this->balance()),
            'tenant' => $this->whenLoaded('tenant', fn () => $this->tenant ? [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
            ] : null),
            'subscription' => $this->whenLoaded('subscription', fn () => $this->subscription ? [
                'id' => $this->subscription->id,
                'subscription_code' => $this->subscription->subscription_code,
                'plan' => $this->subscription->relationLoaded('plan') && $this->subscription->plan ? [
                    'id' => $this->subscription->plan->id,
                    'name' => $this->subscription->plan->name,
                ] : null,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}