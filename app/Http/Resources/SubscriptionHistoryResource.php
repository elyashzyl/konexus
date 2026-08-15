<?php

namespace App\Http\Resources;

use App\Models\SubscriptionHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SubscriptionHistory */
class SubscriptionHistoryResource extends JsonResource
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
            'tenant_id' => $this->tenant_id,
            'subscription_id' => $this->subscription_id,
            'action' => $this->action,
            'description' => $this->description,
            'old_value' => $this->old_value,
            'new_value' => $this->new_value,
            'reason' => $this->reason,
            'actor_id' => $this->actor_id,
            'ip_address' => $this->ip_address,
            'tenant' => $this->whenLoaded('tenant', fn () => $this->tenant ? [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
                'code' => $this->tenant->code,
            ] : null),
            'subscription' => $this->whenLoaded('subscription', fn () => $this->subscription ? [
                'id' => $this->subscription->id,
                'subscription_code' => $this->subscription->subscription_code,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}