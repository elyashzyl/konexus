<?php

namespace App\Policies;

class SubscriptionPlanPolicy extends PlatformPolicy
{
    protected string $viewAnyPermission = 'platform.plans.viewAny';

    protected string $viewPermission = 'platform.plans.view';

    protected string $createPermission = 'platform.plans.create';

    protected string $updatePermission = 'platform.plans.update';

    protected string $deletePermission = 'platform.plans.delete';

    protected string $restorePermission = 'platform.plans.restore';

    protected string $forceDeletePermission = 'platform.plans.forceDelete';
}