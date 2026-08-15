<?php

namespace App\Http\Resources;

use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SubscriptionPayment */
class SubscriptionPaymentResource extends JsonResource
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
            'invoice_id' => $this->invoice_id,
            'subscription_id' => $this->subscription_id,
            'tenant_id' => $this->tenant_id,
            'amount' => (float) $this->amount,
            'payment_date' => $this->payment_date?->toDateString(),
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number,
            'status' => $this->status,
            'recorded_by' => $this->recorded_by,
            'notes' => $this->notes,
            'invoice' => $this->whenLoaded('invoice', fn () => $this->invoice ? [
                'id' => $this->invoice->id,
                'invoice_number' => $this->invoice->invoice_number,
            ] : null),
            'tenant' => $this->whenLoaded('tenant', fn () => $this->tenant ? [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}