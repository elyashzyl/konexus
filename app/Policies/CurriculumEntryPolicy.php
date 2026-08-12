<?php

namespace App\Policies;

class CurriculumEntryPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'curriculum.view-any';

    protected string $viewPermission = 'curriculum.view';

    protected string $createPermission = 'curriculum.create';

    protected string $updatePermission = 'curriculum.update';

    protected string $deletePermission = 'curriculum.delete';

    protected string $restorePermission = 'curriculum.restore';

    protected string $forceDeletePermission = 'curriculum.force-delete';
}