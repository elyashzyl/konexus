<?php

namespace App\Http\Resources;

use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Campus */
class CampusResource extends JsonResource
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
            'school_profile' => $this->whenLoaded('schoolProfile', fn () => $this->schoolProfile ? [
                'id' => $this->schoolProfile->id,
                'name' => $this->schoolProfile->name,
            ] : null),
            'name' => $this->name,
            'code' => $this->code,
            'address' => $this->address,
            'contact_number' => $this->contact_number,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
