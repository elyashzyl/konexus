<?php

namespace App\Policies;

class StaffPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'staff.view-any';

    protected string $viewPermission = 'staff.view';

    protected string $createPermission = 'staff.create';

    protected string $updatePermission = 'staff.update';

    protected string $deletePermission = 'staff.delete';

    protected string $restorePermission = 'staff.restore';

    protected string $forceDeletePermission = 'staff.force-delete';
}
