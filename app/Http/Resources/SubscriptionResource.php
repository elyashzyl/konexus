<?php

namespace App\Http\Resources;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Subscription */
class SubscriptionResource extends JsonResource
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
            'subscription_code' => $this->subscription_code,
            'tenant_id' => $this->tenant_id,
            'plan_id' => $this->plan_id,
            'status' => $this->status,
            'start_date' => $this->start_date?->toDateString(),
            'expiration_date' => $this->expiration_date?->toDateString(),
            'trial_started_at' => $this->trial_started_at?->toDateString(),
            'trial_ends_at' => $this->trial_ends_at?->toDateString(),
            'trial_status' => $this->trial_status,
            'billing_cycle' => $this->billing_cycle,
            'amount' => (float) $this->amount,
            'auto_renewal' => $this->auto_renewal,
            'grace_days' => $this->grace_days,
            'grace_ends_at' => $this->grace_ends_at?->toDateString(),
            'expiration_behavior' => $this->expiration_behavior,
            'last_renewed_at' => $this->last_renewed_at?->toDateString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancel_reason' => $this->cancel_reason,
            'suspended_at' => $this->suspended_at?->toISOString(),
            'suspend_reason' => $this->suspend_reason,
            'expected_resume_at' => $this->expected_resume_at?->toDateString(),
            'resumed_at' => $this->resumed_at?->toISOString(),
            'notes' => $this->notes,
            'days_remaining' => $this->daysRemaining(),
            'allows_access' => $this->allowsAccess(),
            'tenant' => $this->whenLoaded('tenant', fn () => $this->tenant ? [
                'id' => $this->tenant->id,
                'code' => $this->tenant->code,
                'name' => $this->tenant->name,
                'status' => $this->tenant->status,
            ] : null),
            'plan' => $this->whenLoaded('plan', fn () => $this->plan ? [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'code' => $this->plan->code,
                'billing_cycle' => $this->plan->billing_cycle,
            ] : null),
            'features' => $this->when($this->relationLoaded('features'), fn () => $this->features->map(fn ($feature) => [
                'feature_code' => $feature->feature_code,
                'is_enabled' => $feature->is_enabled,
            ])->values()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}