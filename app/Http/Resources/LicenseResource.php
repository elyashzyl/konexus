<?php

namespace App\Http\Resources;

use App\Models\License;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin License */
class LicenseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $reveal = $request->boolean('reveal');

        return [
            'id' => $this->id,
            'license_key' => $reveal ? $this->license_key : $this->maskedKey(),
            'masked_key' => $this->maskedKey(),
            'revealed' => $reveal,
            'tenant_id' => $this->tenant_id,
            'plan_id' => $this->plan_id,
            'issued_date' => $this->issued_date?->toDateString(),
            'start_date' => $this->start_date?->toDateString(),
            'expiration_date' => $this->expiration_date?->toDateString(),
            'status' => $this->status,
            'max_users' => $this->max_users,
            'max_students' => $this->max_students,
            'max_branches' => $this->max_branches,
            'max_storage_mb' => $this->max_storage_mb,
            'features' => $this->features,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'tenant' => $this->whenLoaded('tenant', fn () => $this->tenant ? [
                'id' => $this->tenant->id,
                'name' => $this->tenant->name,
                'code' => $this->tenant->code,
            ] : null),
            'plan' => $this->whenLoaded('plan', fn () => $this->plan ? [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'code' => $this->plan->code,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}