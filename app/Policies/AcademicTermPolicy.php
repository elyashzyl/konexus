<?php

namespace App\Policies;

class AcademicTermPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'academic-term.view-any';

    protected string $viewPermission = 'academic-term.view';

    protected string $createPermission = 'academic-term.create';

    protected string $updatePermission = 'academic-term.update';

    protected string $deletePermission = 'academic-term.delete';

    protected string $restorePermission = 'academic-term.restore';

    protected string $forceDeletePermission = 'academic-term.force-delete';
}
