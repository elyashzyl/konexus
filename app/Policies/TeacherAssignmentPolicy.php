<?php

namespace App\Policies;

class TeacherAssignmentPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'teacher-assignment.view-any';

    protected string $viewPermission = 'teacher-assignment.view';

    protected string $createPermission = 'teacher-assignment.create';

    protected string $updatePermission = 'teacher-assignment.update';

    protected string $deletePermission = 'teacher-assignment.delete';

    protected string $restorePermission = 'teacher-assignment.restore';

    protected string $forceDeletePermission = 'teacher-assignment.force-delete';
}