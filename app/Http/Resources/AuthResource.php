<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Payload returned after a successful authentication exchange
 * (login / register). Includes the bearer token and the authenticated user.
 */
class AuthResource extends JsonResource
{
    /**
     * @param  array{token: string, user: User}  $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource['user'];

        return [
            'token' => $this->resource['token'],
            'token_type' => 'Bearer',
            'expires_in' => config('sanctum.expiration') * 60,
            'user' => new UserResource($user),
        ];
    }
}
