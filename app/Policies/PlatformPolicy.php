<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\User;

/**
 * Base policy for every Part 10 platform subscription module.
 *
 * Platform-level modules are reserved for the platform administration. Unlike
 * the school-level BasePolicy, School Administrators are NOT implicitly
 * granted these actions — a user must either hold the super-administrator or
 * platform-administrator role, or carry the exact granular permission.
 */
abstract class PlatformPolicy extends BasePolicy
{
    /**
     * Determine whether the user passes for the given permission.
     */
    protected function authorize(User $user, string $permission, bool $restricted = false): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMINISTRATOR->roleName())) {
            return true;
        }

        if ($user->hasRole(RoleEnum::PLATFORM_ADMINISTRATOR->roleName())) {
            return true;
        }

        return $user->can($permission);
    }
}