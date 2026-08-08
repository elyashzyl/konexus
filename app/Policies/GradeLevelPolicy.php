<?php

namespace App\Policies;

class GradeLevelPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'grade-level.view-any';

    protected string $viewPermission = 'grade-level.view';

    protected string $createPermission = 'grade-level.create';

    protected string $updatePermission = 'grade-level.update';

    protected string $deletePermission = 'grade-level.delete';

    protected string $restorePermission = 'grade-level.restore';

    protected string $forceDeletePermission = 'grade-level.force-delete';
}
