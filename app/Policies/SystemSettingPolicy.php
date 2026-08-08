<?php

namespace App\Policies;

class SystemSettingPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'system-settings.view-any';

    protected string $viewPermission = 'system-settings.view';

    protected string $createPermission = 'system-settings.create';

    protected string $updatePermission = 'system-settings.update';

    protected string $deletePermission = 'system-settings.delete';

    protected string $restorePermission = 'system-settings.restore';

    protected string $forceDeletePermission = 'system-settings.force-delete';
}
