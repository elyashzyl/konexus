<?php

namespace App\Policies;

class SubjectOfferingPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'subject-offering.view-any';

    protected string $viewPermission = 'subject-offering.view';

    protected string $createPermission = 'subject-offering.create';

    protected string $updatePermission = 'subject-offering.update';

    protected string $deletePermission = 'subject-offering.delete';

    protected string $restorePermission = 'subject-offering.restore';

    protected string $forceDeletePermission = 'subject-offering.force-delete';
}