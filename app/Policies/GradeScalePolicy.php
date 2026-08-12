<?php

namespace App\Policies;

class GradeScalePolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'grade-scale.view-any';

    protected string $viewPermission = 'grade-scale.view';

    protected string $createPermission = 'grade-scale.create';

    protected string $updatePermission = 'grade-scale.update';

    protected string $deletePermission = 'grade-scale.delete';

    protected string $restorePermission = 'grade-scale.restore';

    protected string $forceDeletePermission = 'grade-scale.force-delete';
}