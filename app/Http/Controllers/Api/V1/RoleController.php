<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RoleEnum;
use App\Exceptions\ApiException;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    /**
     * Update a role's label, description or active state.
     *
     * Only super administrators may modify system roles. A role that is still
     * in use (has assigned users) cannot be deactivated.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $role = Role::query()->findOrFail($id);

        if (! $request->user()?->hasRole(RoleEnum::SUPER_ADMINISTRATOR->roleName())) {
            throw ApiException::forbidden('Only super administrators may modify roles.');
        }

        $validated = $request->validate([
            'label' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['is_active']) && ! (bool) $validated['is_active']) {
            $this->assertDeactivatable($role);
        }

        $role->update($validated);

        return $this->success(RoleResource::make($role)->resolve(), 'Role updated.');
    }

    /**
     * Toggle the active state of a role.
     */
    public function toggleActive(Request $request, int $id): JsonResponse
    {
        $role = Role::query()->findOrFail($id);

        if (! $request->user()?->hasRole(RoleEnum::SUPER_ADMINISTRATOR->roleName())) {
            throw ApiException::forbidden('Only super administrators may modify roles.');
        }

        if ((bool) $role->is_active) {
            $this->assertDeactivatable($role);
        }

        $role->update(['is_active' => ! (bool) $role->is_active]);

        return $this->success(RoleResource::make($role)->resolve(), 'Role status updated.');
    }

    /**
     * Prevent deactivating a role that is still assigned to users.
     */
    private function assertDeactivatable(Role $role): void
    {
        $assigned = $role->users()->count();

        if ($assigned > 0) {
            throw ApiException::unprocessable(
                'This role cannot be deactivated because it is assigned to users.',
            );
        }
    }
}
