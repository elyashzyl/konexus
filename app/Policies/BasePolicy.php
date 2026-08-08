<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\User;

/**
 * Base policy for every Phase 2 module.
 *
 * Super Administrators always pass. School Administrators pass for standard
 * CRUD actions (a future phase will attach granular permissions). Restore and
 * force-delete are reserved for Super Administrators and users holding the
 * matching permission.
 */
abstract class BasePolicy
{
    /**
     * The permission name for each action of this module.
     */
    protected string $viewAnyPermission;

    protected string $viewPermission;

    protected string $createPermission;

    protected string $updatePermission;

    protected string $deletePermission;

    protected string $restorePermission;

    protected string $forceDeletePermission;

    public function viewAny(User $user): bool
    {
        return $this->authorize($user, $this->viewAnyPermission);
    }

    public function view(User $user, mixed $model): bool
    {
        return $this->authorize($user, $this->viewPermission);
    }

    public function create(User $user): bool
    {
        return $this->authorize($user, $this->createPermission);
    }

    public function update(User $user, mixed $model): bool
    {
        return $this->authorize($user, $this->updatePermission);
    }

    public function delete(User $user, mixed $model): bool
    {
        return $this->authorize($user, $this->deletePermission);
    }

    public function restore(User $user, mixed $model): bool
    {
        return $this->authorize($user, $this->restorePermission, true);
    }

    public function forceDelete(User $user, mixed $model): bool
    {
        return $this->authorize($user, $this->forceDeletePermission, true);
    }

    /**
     * Determine whether the user passes for the given permission.
     */
    protected function authorize(User $user, string $permission, bool $restricted = false): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMINISTRATOR->roleName())) {
            return true;
        }

        if ($restricted) {
            return false;
        }

        if ($user->hasRole(RoleEnum::SCHOOL_ADMINISTRATOR->roleName())) {
            return true;
        }

        return $user->can($permission);
    }
}
