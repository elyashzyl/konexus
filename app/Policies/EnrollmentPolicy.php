<?php

namespace App\Policies;

use App\Enums\RoleEnum;
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

    public function viewAny(User $user): bool
    {
        return $this->isRegistrar($user) || $this->isPrincipal($user) || $this->isFinanceOfficer($user) || parent::viewAny($user);
    }

    public function view(User $user, mixed $model): bool
    {
        return $this->isRegistrar($user) || $this->isPrincipal($user) || $this->isFinanceOfficer($user) || parent::view($user, $model);
    }

    public function create(User $user): bool
    {
        return $this->isRegistrar($user) || parent::create($user);
    }

    public function update(User $user, mixed $model): bool
    {
        return $this->isRegistrar($user) || parent::update($user, $model);
    }

    public function delete(User $user, mixed $model): bool
    {
        return $this->isRegistrar($user) || parent::delete($user, $model);
    }

    public function verify(User $user, mixed $model): bool
    {
        return $this->isRegistrar($user) || $this->authorize($user, 'enrollment.verify');
    }

    public function principalApprove(User $user, mixed $model): bool
    {
        return $this->isPrincipal($user) || $this->authorize($user, 'enrollment.principal-approve');
    }

    public function registrarReview(User $user, mixed $model): bool
    {
        return $this->isRegistrar($user) || $this->authorize($user, 'enrollment.registrar-review');
    }

    public function recordPayment(User $user, mixed $model): bool
    {
        return $this->isFinanceOfficer($user) || $this->authorize($user, 'enrollment.record-payment');
    }

    public function finalCheck(User $user, mixed $model): bool
    {
        return $this->isRegistrar($user) || $this->authorize($user, 'enrollment.final-check');
    }

    public function approve(User $user, mixed $model): bool
    {
        return $this->isRegistrar($user) || $this->authorize($user, 'enrollment.approve');
    }

    public function reject(User $user, mixed $model): bool
    {
        return $this->isRegistrar($user) || $this->isPrincipal($user) || $this->isFinanceOfficer($user) || $this->authorize($user, 'enrollment.reject');
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
        return $this->isRegistrar($user) || $this->authorize($user, 'enrollment.complete');
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
        return $this->isRegistrar($user) || $this->authorize($user, 'enrollment.print');
    }

    public function viewRequirements(User $user, mixed $model): bool
    {
        return $this->isRegistrar($user) || $this->authorize($user, 'enrollment.requirements-view');
    }

    public function manageRequirements(User $user, mixed $model): bool
    {
        return $this->isRegistrar($user) || $this->authorize($user, 'enrollment.requirements-manage');
    }

    public function viewDocuments(User $user, mixed $model): bool
    {
        return $this->isRegistrar($user) || $this->authorize($user, 'enrollment.documents-view');
    }

    public function uploadDocuments(User $user, mixed $model): bool
    {
        return $this->isRegistrar($user) || $this->authorize($user, 'enrollment.documents-upload');
    }

    public function deleteDocuments(User $user, mixed $model): bool
    {
        return $this->isRegistrar($user) || $this->authorize($user, 'enrollment.documents-delete');
    }

    public function viewSignatures(User $user, mixed $model): bool
    {
        return $this->isRegistrar($user) || $this->authorize($user, 'enrollment.signatures-view');
    }

    public function sign(User $user, mixed $model): bool
    {
        return $this->isRegistrar($user) || $this->authorize($user, 'enrollment.signatures-sign');
    }

    private function isRegistrar(User $user): bool
    {
        return $user->hasRole(RoleEnum::REGISTRAR->roleName());
    }

    private function isPrincipal(User $user): bool
    {
        return $user->hasRole(RoleEnum::PRINCIPAL->roleName());
    }

    private function isFinanceOfficer(User $user): bool
    {
        return $user->hasRole(RoleEnum::FINANCE_OFFICER->roleName());
    }
}
