<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\RoleEnum;
use App\Exceptions\ApiException;
use App\Models\Campus;
use App\Models\Enrollment;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\Scopes\CampusScope;
use App\Models\Scopes\SchoolScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CampusWorkspaceService
{
    /**
     * @return Collection<int, Campus>
     */
    public function availableFor(User $user): Collection
    {
        $campusIds = $this->portalCampusIds($user);

        return Campus::query()
            ->withoutGlobalScope(SchoolScope::class)
            ->withoutGlobalScope(CampusScope::class)
            ->with('schoolProfile:id,name,short_name')
            ->where('is_active', true)
            ->when(! $this->isPlatformOperator($user), fn ($query) => $query->where('school_profile_id', $user->school_profile_id))
            ->when($campusIds !== null, fn ($query) => $query->whereIn('id', $campusIds))
            ->orderBy('name')
            ->get();
    }

    public function activeFor(User $user, ?int $requestedCampusId = null): ?Campus
    {
        $campuses = $this->availableFor($user);
        $campusId = $requestedCampusId ?? $user->active_campus_id;

        if ($campusId !== null) {
            $campus = $campuses->firstWhere('id', $campusId);

            if ($campus === null) {
                if ($requestedCampusId !== null) {
                    throw ApiException::forbidden('You do not have access to the selected campus workspace.');
                }

                $campusId = null;
            } else {
                return $campus;
            }
        }

        $campus = $campuses->first();

        if ($campus !== null && $user->active_campus_id !== $campus->id) {
            $user->forceFill(['active_campus_id' => $campus->id])->save();
        }

        return $campus;
    }

    public function select(User $user, int $campusId): Campus
    {
        $campus = $this->activeFor($user, $campusId);

        if ($campus === null) {
            throw ApiException::unprocessable('No active campus workspaces are available for this account.');
        }

        $user->forceFill(['active_campus_id' => $campus->id])->save();

        return $campus;
    }

    private function isPlatformOperator(User $user): bool
    {
        return $user->hasRole(RoleEnum::SUPER_ADMINISTRATOR->roleName())
            || $user->hasRole(RoleEnum::PLATFORM_ADMINISTRATOR->roleName());
    }

    /**
     * Portal users only receive the campuses connected to their own learning
     * or teaching records. Administrators retain their whole school context.
     *
     * @return list<int>|null
     */
    private function portalCampusIds(User $user): ?array
    {
        if ($user->hasRole(RoleEnum::STUDENT->roleName())) {
            $student = Student::query()
                ->where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->first();

            return $student === null ? [] : $this->activeEnrollmentCampusIds([$student->id]);
        }

        if ($user->hasRole(RoleEnum::PARENT->roleName())) {
            $parent = ParentGuardian::query()
                ->where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->first();

            return $parent === null ? [] : $this->activeEnrollmentCampusIds($parent->students()->pluck('students.id')->all());
        }

        if ($user->hasRole(RoleEnum::TEACHER->roleName()) || $user->hasRole(RoleEnum::ADVISER->roleName())) {
            $teacherId = Teacher::query()
                ->whereHas('employee', function ($query) use ($user): void {
                    $query->where('user_id', $user->id)->orWhere('email', $user->email);
                })
                ->value('id');

            if ($teacherId === null) {
                return [];
            }

            return TeacherAssignment::query()
                ->where('teacher_id', $teacherId)
                ->whereNotNull('campus_id')
                ->pluck('campus_id')
                ->map(fn ($campusId) => (int) $campusId)
                ->unique()
                ->values()
                ->all();
        }

        return null;
    }

    /**
     * @param  list<int>  $studentIds
     * @return list<int>
     */
    private function activeEnrollmentCampusIds(array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }

        return Enrollment::query()
            ->whereIn('student_id', $studentIds)
            ->whereIn('status', EnrollmentStatus::activeStatuses())
            ->whereNotNull('campus_id')
            ->pluck('campus_id')
            ->map(fn ($campusId) => (int) $campusId)
            ->unique()
            ->values()
            ->all();
    }
}
