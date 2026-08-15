<?php

namespace App\Policies;

class SubscriptionSettingPolicy extends PlatformPolicy
{
    protected string $viewAnyPermission = 'platform.settings.viewAny';

    protected string $viewPermission = 'platform.settings.view';

    protected string $createPermission = 'platform.settings.create';

    protected string $updatePermission = 'platform.settings.update';

    protected string $deletePermission = 'platform.settings.delete';

    protected string $restorePermission = 'platform.settings.restore';

    protected string $forceDeletePermission = 'platform.settings.forceDelete';
}