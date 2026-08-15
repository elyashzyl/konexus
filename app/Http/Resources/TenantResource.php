<?php

namespace App\Http\Resources;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tenant */
class TenantResource extends JsonResource
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
            'school_profile_id' => $this->school_profile_id,
            'code' => $this->code,
            'name' => $this->name,
            'status' => $this->status,
            'settings' => $this->settings,
            'school_profile' => $this->whenLoaded('schoolProfile', fn () => $this->schoolProfile ? [
                'id' => $this->schoolProfile->id,
                'name' => $this->schoolProfile->name,
            ] : null),
            'subscription_count' => $this->whenCounted('subscriptions'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}