<?php

namespace App\Http\Resources;

use App\Models\SchoolProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SchoolProfile */
class SchoolProfileResource extends JsonResource
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
            'short_name' => $this->short_name,
            'school_id' => $this->school_id,
            'region' => $this->region,
            'division' => $this->division,
            'district' => $this->district,
            'address' => $this->address,
            'contact_number' => $this->contact_number,
            'email' => $this->email,
            'website' => $this->website,
            'motto' => $this->motto,
            'logo_path' => $this->logo_path,
            'principal_name' => $this->principal_name,
            'is_primary' => $this->is_primary,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
