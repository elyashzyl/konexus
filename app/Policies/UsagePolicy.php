<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Tenant;
use App\Models\User;

class UsagePolicy extends PlatformPolicy
{
    protected string $viewAnyPermission = 'platform.usage.viewAny';

    protected string $viewPermission = 'platform.usage.view';

    protected string $createPermission = 'platform.usage.create';

    protected string $updatePermission = 'platform.usage.update';

    protected string $deletePermission = 'platform.usage.delete';

    protected string $restorePermission = 'platform.usage.restore';

    protected string $forceDeletePermission = 'platform.usage.forceDelete';

    public function snapshot(User $user, Tenant $tenant): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMINISTRATOR->roleName())
            || $user->hasRole(RoleEnum::PLATFORM_ADMINISTRATOR->roleName())
            || $user->can('platform.usage.create');
    }
}