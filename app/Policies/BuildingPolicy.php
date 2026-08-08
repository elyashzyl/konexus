<?php

namespace App\Policies;

class BuildingPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'building.view-any';

    protected string $viewPermission = 'building.view';

    protected string $createPermission = 'building.create';

    protected string $updatePermission = 'building.update';

    protected string $deletePermission = 'building.delete';

    protected string $restorePermission = 'building.restore';

    protected string $forceDeletePermission = 'building.force-delete';
}
