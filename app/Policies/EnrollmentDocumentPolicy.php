<?php

namespace App\Policies;

use App\Models\User;

class EnrollmentDocumentPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'enrollment-document.view-any';

    protected string $viewPermission = 'enrollment-document.view';

    protected string $createPermission = 'enrollment-document.create';

    protected string $updatePermission = 'enrollment-document.update';

    protected string $deletePermission = 'enrollment-document.delete';

    protected string $restorePermission = 'enrollment-document.restore';

    protected string $forceDeletePermission = 'enrollment-document.force-delete';

    public function download(User $user, mixed $model): bool
    {
        return $this->authorize($user, 'enrollment-document.view') || $this->authorize($user, 'enrollment.documents-view');
    }
}