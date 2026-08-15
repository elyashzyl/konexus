<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Tenant;
use App\Models\User;

class FeaturePolicy extends PlatformPolicy
{
    protected string $viewAnyPermission = 'platform.features.viewAny';

    protected string $viewPermission = 'platform.features.view';

    protected string $createPermission = 'platform.features.create';

    protected string $updatePermission = 'platform.features.update';

    protected string $deletePermission = 'platform.features.delete';

    protected string $restorePermission = 'platform.features.restore';

    protected string $forceDeletePermission = 'platform.features.forceDelete';

    public function toggle(User $user, Tenant $tenant): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMINISTRATOR->roleName())
            || $user->hasRole(RoleEnum::PLATFORM_ADMINISTRATOR->roleName())
            || $user->can('platform.subscriptions.manage');
    }
}