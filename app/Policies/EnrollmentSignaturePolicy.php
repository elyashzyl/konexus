<?php

namespace App\Policies;

class EnrollmentSignaturePolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'enrollment-signature.view-any';

    protected string $viewPermission = 'enrollment-signature.view';

    protected string $createPermission = 'enrollment-signature.create';

    protected string $updatePermission = 'enrollment-signature.update';

    protected string $deletePermission = 'enrollment-signature.delete';

    protected string $restorePermission = 'enrollment-signature.restore';

    protected string $forceDeletePermission = 'enrollment-signature.force-delete';
}