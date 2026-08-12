<?php

namespace App\Policies;

class AcademicClassPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'academic-class.view-any';

    protected string $viewPermission = 'academic-class.view';

    protected string $createPermission = 'academic-class.create';

    protected string $updatePermission = 'academic-class.update';

    protected string $deletePermission = 'academic-class.delete';

    protected string $restorePermission = 'academic-class.restore';

    protected string $forceDeletePermission = 'academic-class.force-delete';
}