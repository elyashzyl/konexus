<?php

namespace App\Policies;

class GuardianPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'guardian.view-any';

    protected string $viewPermission = 'guardian.view';

    protected string $createPermission = 'guardian.create';

    protected string $updatePermission = 'guardian.update';

    protected string $deletePermission = 'guardian.delete';

    protected string $restorePermission = 'guardian.restore';

    protected string $forceDeletePermission = 'guardian.force-delete';
}
