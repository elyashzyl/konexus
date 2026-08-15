<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\User;

class SchoolProfilePolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'school.view-any';

    protected string $viewPermission = 'school.view';

    protected string $createPermission = 'school.create';

    protected string $updatePermission = 'school.update';

    protected string $deletePermission = 'school.delete';

    protected string $restorePermission = 'school.restore';

    protected string $forceDeletePermission = 'school.force-delete';

    /**
     * Only super administrators register school profiles.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMINISTRATOR->roleName());
    }

    /**
     * Super administrators may update any school; school administrators may
     * only update the school they are anchored to.
     */
    public function update(User $user, mixed $model): bool
    {
        if ($user->hasRole(RoleEnum::SUPER_ADMINISTRATOR->roleName())) {
            return true;
        }

        if ($user->hasRole(RoleEnum::SCHOOL_ADMINISTRATOR->roleName())) {
            return $model instanceof \App\Models\SchoolProfile
                && (int) $user->school_profile_id === (int) $model->getKey();
        }

        return false;
    }

    /**
     * Only super administrators delete school profiles.
     */
    public function delete(User $user, mixed $model): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMINISTRATOR->roleName());
    }
}
