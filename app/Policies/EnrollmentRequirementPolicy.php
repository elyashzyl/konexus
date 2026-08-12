<?php

namespace App\Policies;

class EnrollmentRequirementPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'enrollment-requirement.view-any';

    protected string $viewPermission = 'enrollment-requirement.view';

    protected string $createPermission = 'enrollment-requirement.create';

    protected string $updatePermission = 'enrollment-requirement.update';

    protected string $deletePermission = 'enrollment-requirement.delete';

    protected string $restorePermission = 'enrollment-requirement.restore';

    protected string $forceDeletePermission = 'enrollment-requirement.force-delete';
}