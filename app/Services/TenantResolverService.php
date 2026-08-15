<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Exceptions\ApiException;
use App\Models\Campus;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;

/**
 * Resolves the tenant (school/organization) associated with the current
 * context. Platform administrators operate on explicit tenants; school users
 * resolve through their person records so subscription feature checks and the
 * read-only school subscription page can scope by school.
 */
class TenantResolverService
{
    /**
     * Whether the user belongs to the platform administration.
     */
    public function isPlatformAdmin(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole(RoleEnum::SUPER_ADMINISTRATOR->roleName())
            || $user->hasRole(RoleEnum::PLATFORM_ADMINISTRATOR->roleName());
    }

    /**
     * Resolve the tenant of the authenticated user.
     *
     * When an explicit tenant id is provided (platform admins) it is honoured.
     * Otherwise the tenant is derived from the user's person records, walking
     * student enrollment -> campus -> school profile -> tenant.
     */
    public function resolveForUser(?User $user, ?int $explicitTenantId = null): ?Tenant
    {
        if ($explicitTenantId !== null) {
            return Tenant::query()->find($explicitTenantId);
        }

        if ($user === null || $this->isPlatformAdmin($user)) {
            return null;
        }

        $schoolProfileId = $this->schoolProfileIdForUser($user);

        if ($schoolProfileId === null) {
            return null;
        }

        return Tenant::query()
            ->where('school_profile_id', $schoolProfileId)
            ->first();
    }

    /**
     * Resolve the tenant for a platform administrator, failing when the user
     * is not authorized to manage tenants.
     */
    public function requireTenant(User $user, int $tenantId): Tenant
    {
        if (! $this->isPlatformAdmin($user)) {
            throw ApiException::forbidden('Only platform administrators can manage tenants.');
        }

        $tenant = Tenant::query()->find($tenantId);

        if (! $tenant) {
            throw ApiException::notFound('Tenant not found.');
        }

        return $tenant;
    }

    /**
     * The school profile id of the user.
     *
     * Users are anchored to a school via `users.school_profile_id`. For legacy
     * accounts created before the anchor existed, fall back to the person
     * record chain (student enrollment -> campus -> school profile).
     */
    private function schoolProfileIdForUser(User $user): ?int
    {
        if ($user->school_profile_id !== null) {
            return $user->school_profile_id;
        }

        // Legacy fallback: students resolve through their most recent enrollment's campus.
        $student = Student::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if ($student) {
            $campusId = Enrollment::query()
                ->where('student_id', $student->id)
                ->latest('id')
                ->value('campus_id');

            if ($campusId) {
                return Campus::query()->whereKey($campusId)->value('school_profile_id');
            }
        }

        return null;
    }
}