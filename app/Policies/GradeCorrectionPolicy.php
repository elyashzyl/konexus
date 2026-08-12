<?php

namespace App\Policies;

class GradeCorrectionPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'grade-correction.view-any';

    protected string $viewPermission = 'grade-correction.view';

    protected string $createPermission = 'grade-correction.create';

    protected string $updatePermission = 'grade-correction.update';

    protected string $deletePermission = 'grade-correction.delete';

    protected string $restorePermission = 'grade-correction.restore';

    protected string $forceDeletePermission = 'grade-correction.force-delete';
}