<?php

namespace App\Policies;

class EmployeePolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'employee.view-any';

    protected string $viewPermission = 'employee.view';

    protected string $createPermission = 'employee.create';

    protected string $updatePermission = 'employee.update';

    protected string $deletePermission = 'employee.delete';

    protected string $restorePermission = 'employee.restore';

    protected string $forceDeletePermission = 'employee.force-delete';
}
