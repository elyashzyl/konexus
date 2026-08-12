<?php

namespace App\Http\Resources;

use App\Models\ParentGuardian;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ParentGuardian */
class ParentResource extends JsonResource
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
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'extension_name' => $this->extension_name,
            'name' => $this->full_name,
            'occupation' => $this->occupation,
            'employer' => $this->employer,
            'educational_attainment' => $this->educational_attainment,
            'mobile_number' => $this->mobile_number,
            'telephone_number' => $this->telephone_number,
            'email' => $this->email,
            'address' => $this->address,
            'relationship' => $this->relationship,
            'status' => $this->status,
            'students' => StudentResource::collection($this->whenLoaded('students')),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
