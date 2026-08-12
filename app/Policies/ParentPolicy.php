<?php

namespace App\Policies;

class ParentPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'parent.view-any';

    protected string $viewPermission = 'parent.view';

    protected string $createPermission = 'parent.create';

    protected string $updatePermission = 'parent.update';

    protected string $deletePermission = 'parent.delete';

    protected string $restorePermission = 'parent.restore';

    protected string $forceDeletePermission = 'parent.force-delete';
}
