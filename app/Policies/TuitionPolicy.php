<?php

namespace App\Policies;

class TuitionPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'tuition.view-any';

    protected string $viewPermission = 'tuition.view';

    protected string $createPermission = 'tuition.create';

    protected string $updatePermission = 'tuition.update';

    protected string $deletePermission = 'tuition.delete';

    protected string $restorePermission = 'tuition.restore';

    protected string $forceDeletePermission = 'tuition.force-delete';
}