<?php

namespace App\Policies;

class CampusPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'campus.view-any';

    protected string $viewPermission = 'campus.view';

    protected string $createPermission = 'campus.create';

    protected string $updatePermission = 'campus.update';

    protected string $deletePermission = 'campus.delete';

    protected string $restorePermission = 'campus.restore';

    protected string $forceDeletePermission = 'campus.force-delete';
}
