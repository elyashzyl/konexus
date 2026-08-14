<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
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
            'email' => $this->email,
            'avatar' => $this->avatar,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'is_active' => $this->is_active,
            'school_profile_id' => $this->school_profile_id,
            'school' => $this->whenLoaded('schoolProfile', fn () => $this->schoolProfile ? [
                'id' => $this->schoolProfile->id,
                'name' => $this->schoolProfile->name,
                'short_name' => $this->schoolProfile->short_name,
            ] : null),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
