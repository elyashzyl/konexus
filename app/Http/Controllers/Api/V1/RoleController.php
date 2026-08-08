<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RoleEnum;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;

class RoleController extends ApiController
{
    /**
     * List all roles available in the system.
     */
    public function index(): JsonResponse
    {
        return $this->success(
            RoleResource::collection(Role::orderBy('id')->get()),
            'Roles retrieved.',
        );
    }

    /**
     * Return the default system role catalog (metadata only).
     */
    public function catalog(): JsonResponse
    {
        return $this->success(
            RoleEnum::toSeedData(),
            'Role catalog retrieved.',
        );
    }
}
