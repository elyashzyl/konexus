<?php

namespace App\Policies;

use App\Models\Tenant;

class TenantPolicy extends PlatformPolicy
{
    protected string $viewAnyPermission = 'platform.tenants.viewAny';

    protected string $viewPermission = 'platform.tenants.view';

    protected string $createPermission = 'platform.tenants.create';

    protected string $updatePermission = 'platform.tenants.update';

    protected string $deletePermission = 'platform.tenants.delete';

    protected string $restorePermission = 'platform.tenants.restore';

    protected string $forceDeletePermission = 'platform.tenants.forceDelete';

    public function suspend(\App\Models\User $user, Tenant $tenant): bool
    {
        return $this->hasPermission($user, 'platform.tenants.manage');
    }

    public function resume(\App\Models\User $user, Tenant $tenant): bool
    {
        return $this->hasPermission($user, 'platform.tenants.manage');
    }
}