<?php

namespace App\Http\Resources;

use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SubscriptionPlan */
class SubscriptionPlanResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'billing_cycle' => $this->billing_cycle,
            'monthly_price' => (float) $this->monthly_price,
            'annual_price' => (float) $this->annual_price,
            'trial_days' => $this->trial_days,
            'max_students' => $this->max_students,
            'max_staff' => $this->max_staff,
            'max_branches' => $this->max_branches,
            'max_users' => $this->max_users,
            'max_storage_mb' => $this->max_storage_mb,
            'is_active' => $this->is_active,
            'display_order' => $this->display_order,
            'features' => $this->when($this->relationLoaded('planFeatures'), fn () => $this->planFeatures->pluck('feature_code')->values()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}