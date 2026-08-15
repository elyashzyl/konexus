<?php

namespace App\Policies;

class LicensePolicy extends PlatformPolicy
{
    protected string $viewAnyPermission = 'platform.licenses.viewAny';

    protected string $viewPermission = 'platform.licenses.view';

    protected string $createPermission = 'platform.licenses.create';

    protected string $updatePermission = 'platform.licenses.update';

    protected string $deletePermission = 'platform.licenses.delete';

    protected string $restorePermission = 'platform.licenses.restore';

    protected string $forceDeletePermission = 'platform.licenses.forceDelete';

    public function regenerate(\App\Models\User $user, \App\Models\License $license): bool
    {
        return $this->hasPermission($user, 'platform.licenses.manage');
    }

    public function revoke(\App\Models\User $user, \App\Models\License $license): bool
    {
        return $this->hasPermission($user, 'platform.licenses.manage');
    }

    public function reveal(\App\Models\User $user, \App\Models\License $license): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMINISTRATOR->roleName())
            || $user->hasRole(RoleEnum::PLATFORM_ADMINISTRATOR->roleName())
            || $user->can('platform.licenses.manage');
    }
}