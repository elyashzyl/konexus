<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\User;

class AcademicClassStudentPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'academic-class.view-any';

    protected string $viewPermission = 'academic-class.view';

    protected string $createPermission = 'academic-class.member-manage';

    protected string $updatePermission = 'academic-class.member-manage';

    protected string $deletePermission = 'academic-class.member-manage';

    protected string $restorePermission = 'academic-class.restore';

    protected string $forceDeletePermission = 'academic-class.force-delete';
}