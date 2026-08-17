<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Student;
use App\Models\User;

class StudentPolicy extends BasePolicy
{
    protected string $viewAnyPermission = 'student.view-any';

    protected string $viewPermission = 'student.view';

    protected string $createPermission = 'student.create';

    protected string $updatePermission = 'student.update';

    protected string $deletePermission = 'student.delete';

    protected string $restorePermission = 'student.restore';

    protected string $forceDeletePermission = 'student.force-delete';

    public function viewAny(User $user): bool
    {
        return $this->isRegistrar($user) || $this->isPrincipal($user) || parent::viewAny($user);
    }

    public function view(User $user, mixed $model): bool
    {
        return $this->isRegistrar($user) || $this->isPrincipal($user) || parent::view($user, $model);
    }

    /**
     * Review the personal, family, medical and history tabs of a student profile.
     */
    public function viewProfile(User $user, Student $student): bool
    {
        return $this->isRegistrar($user) || parent::viewProfile($user, $student);
    }

    /**
     * View the enrollment and academic history of a student.
     */
    public function viewHistory(User $user, Student $student): bool
    {
        return $this->isRegistrar($user) || parent::viewHistory($user, $student);
    }

    private function isRegistrar(User $user): bool
    {
        return $user->hasRole(RoleEnum::REGISTRAR->roleName());
    }

    private function isPrincipal(User $user): bool
    {
        return $user->hasRole(RoleEnum::PRINCIPAL->roleName());
    }

    /**
     * View the medical record of a student (clinic / registrar only).
     */
    public function viewMedical(User $user, Student $student): bool
    {
        return $this->hasPermission($user, 'student.medical-view');
    }

    /**
     * Update the medical record of a student.
     */
    public function updateMedical(User $user, Student $student): bool
    {
        return $this->hasPermission($user, 'student.medical-update');
    }

    /**
     * View the family composition (parents & guardians) of a student.
     */
    public function viewFamily(User $user, Student $student): bool
    {
        return $this->hasPermission($user, 'student.family-view');
    }

    /**
     * Link/unlink parents and guardians of a student.
     */
    public function updateFamily(User $user, Student $student): bool
    {
        return $this->hasPermission($user, 'student.family-update');
    }

    /**
     * View the aggregated activity timeline of a student.
     */
    public function viewTimeline(User $user, Student $student): bool
    {
        return $this->hasPermission($user, 'student.timeline-view');
    }

    /**
     * List the documents belonging to a student.
     */
    public function viewDocuments(User $user, Student $student): bool
    {
        return $this->hasPermission($user, 'student.documents-view');
    }

    /**
     * Upload a document against a student.
     */
    public function uploadDocuments(User $user, Student $student): bool
    {
        return $this->hasPermission($user, 'student.documents-upload');
    }

    /**
     * Delete a document belonging to a student.
     */
    public function deleteDocuments(User $user, Student $student): bool
    {
        return $this->hasPermission($user, 'student.documents-delete');
    }

    /**
     * Change the status of a student (with reason + activity log).
     */
    public function updateStatus(User $user, Student $student): bool
    {
        return $this->hasPermission($user, 'student.status-update');
    }

    /**
     * Generate / view the secure QR identity of a student.
     */
    public function viewQr(User $user, Student $student): bool
    {
        return $this->hasPermission($user, 'student.qr');
    }

    /**
     * Export the student list as CSV.
     */
    public function export(User $user): bool
    {
        return $this->hasPermission($user, 'student.export');
    }

    /**
     * Bulk import students from a CSV / rows payload.
     */
    public function import(User $user): bool
    {
        return $this->hasPermission($user, 'student.import');
    }
}