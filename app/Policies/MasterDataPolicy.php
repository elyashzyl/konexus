<?php

namespace App\Policies;

class MasterDataPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'master-data.view-any';

    protected string $viewPermission = 'master-data.view';

    protected string $createPermission = 'master-data.create';

    protected string $updatePermission = 'master-data.update';

    protected string $deletePermission = 'master-data.delete';

    protected string $restorePermission = 'master-data.restore';

    protected string $forceDeletePermission = 'master-data.force-delete';
}
