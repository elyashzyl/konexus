<?php

namespace App\Policies;

use App\Models\User;

class EnrollmentPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'enrollment.view-any';

    protected string $viewPermission = 'enrollment.view';

    protected string $createPermission = 'enrollment.create';

    protected string $updatePermission = 'enrollment.update';

    protected string $deletePermission = 'enrollment.delete';

    protected string $restorePermission = 'enrollment.restore';

    protected string $forceDeletePermission = 'enrollment.force-delete';

    public function verify(User $user, mixed $model): bool
    {
        return $this->authorize($user, 'enrollment.verify');
    }

    public function approve(User $user, mixed $model): bool
    {
        return $this->authorize($user, 'enrollment.approve');
    }

    public function reject(User $user, mixed $model): bool
    {
        return $this->authorize($user, 'enrollment.reject');
    }

    public function withdraw(User $user, mixed $model): bool
    {
        return $this->authorize($user, 'enrollment.withdraw');
    }

    public function transfer(User $user, mixed $model): bool
    {
        return $this->authorize($user, 'enrollment.transfer');
    }

    public function complete(User $user, mixed $model): bool
    {
        return $this->authorize($user, 'enrollment.complete');
    }

    public function cancel(User $user, mixed $model): bool
    {
        return $this->authorize($user, 'enrollment.cancel');
    }

    public function overrideCapacity(User $user, mixed $model): bool
    {
        return $this->authorize($user, 'enrollment.override-capacity');
    }

    public function export(User $user): bool
    {
        return $this->authorize($user, 'enrollment.export');
    }

    public function print(User $user, mixed $model): bool
    {
        return $this->authorize($user, 'enrollment.print');
    }

    public function viewRequirements(User $user, mixed $model): bool
    {
        return $this->authorize($user, 'enrollment.requirements-view');
    }

    public function manageRequirements(User $user, mixed $model): bool
    {
        return $this->authorize($user, 'enrollment.requirements-manage');
    }

    public function viewDocuments(User $user, mixed $model): bool
    {
        return $this->authorize($user, 'enrollment.documents-view');
    }

    public function uploadDocuments(User $user, mixed $model): bool
    {
        return $this->authorize($user, 'enrollment.documents-upload');
    }

    public function deleteDocuments(User $user, mixed $model): bool
    {
        return $this->authorize($user, 'enrollment.documents-delete');
    }

    public function viewSignatures(User $user, mixed $model): bool
    {
        return $this->authorize($user, 'enrollment.signatures-view');
    }

    public function sign(User $user, mixed $model): bool
    {
        return $this->authorize($user, 'enrollment.signatures-sign');
    }
}