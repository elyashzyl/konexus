<?php

namespace App\Policies;

class DepartmentPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'department.view-any';

    protected string $viewPermission = 'department.view';

    protected string $createPermission = 'department.create';

    protected string $updatePermission = 'department.update';

    protected string $deletePermission = 'department.delete';

    protected string $restorePermission = 'department.restore';

    protected string $forceDeletePermission = 'department.force-delete';
}
