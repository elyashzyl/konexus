<?php

namespace App\Policies;

class TeacherPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'teacher.view-any';

    protected string $viewPermission = 'teacher.view';

    protected string $createPermission = 'teacher.create';

    protected string $updatePermission = 'teacher.update';

    protected string $deletePermission = 'teacher.delete';

    protected string $restorePermission = 'teacher.restore';

    protected string $forceDeletePermission = 'teacher.force-delete';
}
