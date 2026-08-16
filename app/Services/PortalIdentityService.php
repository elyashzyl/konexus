<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Employee;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;

/**
 * Resolves the person record behind a portal user account.
 *
 * Part 8 – Portals. A User is linked to at most one Student, Parent or
 * Employee/Teacher record. When no explicit `user_id` link exists (legacy
 * data), the account is matched by email. The resolved person drives every
 * permission-aware portal query so users only ever see their own records.
 */
class PortalIdentityService
{
    /**
     * The Parent record backing the authenticated user, if any.
     */
    public function parent(?User $user): ?ParentGuardian
    {
        if ($user === null) {
            return null;
        }

        return ParentGuardian::query()
            ->with('students')
            ->where(function ($q) use ($user): void {
                $q->where('user_id', $user->id);
                if (filled($user->email)) {
                    $q->orWhere('email', $user->email);
                }
            })
            ->first();
    }

    /**
     * The Student record backing the authenticated user, if any.
     */
    public function student(?User $user): ?Student
    {
        if ($user === null) {
            return null;
        }

        return Student::query()
            ->with('schoolProfile')
            ->where(function ($q) use ($user): void {
                $q->where('user_id', $user->id);
                if (filled($user->email)) {
                    $q->orWhere('email', $user->email);
                }
            })
            ->first();
    }

    /**
     * The Employee record backing the authenticated user, if any.
     */
    public function employee(?User $user): ?Employee
    {
        if ($user === null) {
            return null;
        }

        return Employee::query()
            ->where(function ($q) use ($user): void {
                $q->where('user_id', $user->id);
                if (filled($user->email)) {
                    $q->orWhere('email', $user->email);
                }
            })
            ->first();
    }

    /**
     * The Teacher profile backing the authenticated user, if any.
     */
    public function teacher(?User $user): ?Teacher
    {
        $employee = $this->employee($user);

        if ($employee === null) {
            return null;
        }

        return Teacher::query()
            ->with(['employee.schoolProfile', 'employee.campuses', 'department', 'advisoryClass'])
            ->where('employee_id', $employee->id)
            ->first();
    }

    /**
     * The audience signature of the authenticated user, used to decide which
     * targeted announcements they may see.
     *
     * @return array<string, mixed>
     */
    public function audienceSignature(?User $user): array
    {
        if ($user === null) {
            return ['role' => null, 'grade_level_id' => null, 'section_id' => null, 'campus_id' => null];
        }

        $role = $user->roles()->value('name');
        $gradeLevelId = null;
        $sectionId = null;
        $campusId = null;

        if ($role === 'parent') {
            $parent = $this->parent($user);
            $enrollment = $parent?->students()
                ->with('activeEnrollment.gradeLevel', 'activeEnrollment.section', 'activeEnrollment.campus')
                ->get()
                ->pluck('activeEnrollment')
                ->filter()
                ->first();
            $gradeLevelId = $enrollment?->grade_level_id;
            $sectionId = $enrollment?->section_id;
            $campusId = $enrollment?->campus_id;
        } elseif ($role === 'student') {
            $student = $this->student($user);
            $enrollment = $student?->activeEnrollment()->with('gradeLevel', 'section', 'campus')->first();
            $gradeLevelId = $enrollment?->grade_level_id;
            $sectionId = $enrollment?->section_id;
            $campusId = $enrollment?->campus_id;
        } elseif ($role === 'teacher' || $role === 'adviser') {
            $teacher = $this->teacher($user);
            $campusId = $teacher !== null
                ? \App\Models\TeacherAssignment::query()->where('teacher_id', $teacher->id)->value('campus_id')
                : null;
        }

        return [
            'role' => $role,
            'grade_level_id' => $gradeLevelId,
            'section_id' => $sectionId,
            'campus_id' => $campusId,
        ];
    }

    /**
     * The active enrollment of a student, if any.
     */
    public function activeEnrollment(Student $student): ?\App\Models\Enrollment
    {
        return $student->activeEnrollment()
            ->with(['gradeLevel', 'section', 'campus.schoolProfile', 'academicYear', 'academicTerm'])
            ->first();
    }
}