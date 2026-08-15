<?php

namespace App\Http\Resources;

use App\Models\SubscriptionUsage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SubscriptionUsage */
class SubscriptionUsageResource extends JsonResource
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
            'period_year' => $this->period_year,
            'period_month' => $this->period_month,
            'students_count' => $this->students_count,
            'users_count' => $this->users_count,
            'staff_count' => $this->staff_count,
            'branches_count' => $this->branches_count,
            'storage_mb' => $this->storage_mb,
            'documents_count' => $this->documents_count,
            'database_size_mb' => $this->database_size_mb,
            'captured_at' => $this->captured_at?->toISOString(),
            'tenant' => $this->whenLoaded('tenant', fn () => $this->tenant ? [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
                'code' => $this->tenant->code,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}