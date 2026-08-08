<?php

namespace App\Policies;

class SubjectPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'subject.view-any';

    protected string $viewPermission = 'subject.view';

    protected string $createPermission = 'subject.create';

    protected string $updatePermission = 'subject.update';

    protected string $deletePermission = 'subject.delete';

    protected string $restorePermission = 'subject.restore';

    protected string $forceDeletePermission = 'subject.force-delete';
}
