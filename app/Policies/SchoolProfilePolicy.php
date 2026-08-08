<?php

namespace App\Policies;

class SchoolProfilePolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'school.view-any';

    protected string $viewPermission = 'school.view';

    protected string $createPermission = 'school.create';

    protected string $updatePermission = 'school.update';

    protected string $deletePermission = 'school.delete';

    protected string $restorePermission = 'school.restore';

    protected string $forceDeletePermission = 'school.force-delete';
}
