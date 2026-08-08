<?php

namespace App\Policies;

class AcademicYearPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'academic-year.view-any';

    protected string $viewPermission = 'academic-year.view';

    protected string $createPermission = 'academic-year.create';

    protected string $updatePermission = 'academic-year.update';

    protected string $deletePermission = 'academic-year.delete';

    protected string $restorePermission = 'academic-year.restore';

    protected string $forceDeletePermission = 'academic-year.force-delete';
}
