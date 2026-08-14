<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Campus;
use App\Models\Employee;
use App\Models\Enrollment;
use App\Models\ParentGuardian;
use App\Models\SchoolProfile;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seed demo login accounts for every system role.
 *
 * Run after the people/registrar seeders. Every account uses the password
 * `password`, links the corresponding portal identity where available, and is
 * idempotent so it can be re-run safely.
 */
class DemoUsersSeeder extends Seeder
{
    private const PASSWORD = 'password';

    /**
     * Create (or refresh) one demo user per role.
     */
    public function run(): void
    {
        $staff = [
            RoleEnum::SCHOOL_ADMINISTRATOR->roleName() => 'school-admin',
            RoleEnum::PRINCIPAL->roleName() => 'principal',
            RoleEnum::REGISTRAR->roleName() => 'registrar',
            RoleEnum::FINANCE_OFFICER->roleName() => 'finance-officer',
            RoleEnum::GUIDANCE_COUNSELOR->roleName() => 'guidance-counselor',
            RoleEnum::SCHOOL_NURSE->roleName() => 'school-nurse',
            RoleEnum::LIBRARIAN->roleName() => 'librarian',
            RoleEnum::HR_OFFICER->roleName() => 'hr-officer',
            RoleEnum::INVENTORY_OFFICER->roleName() => 'inventory-officer',
        ];

        foreach ($staff as $roleName => $handle) {
            $this->makeUser(
                $roleName,
                ucwords(str_replace('-', ' ', $roleName)).' (Demo)',
                "demo.{$handle}@konexus.local",
            );
        }

        $this->makeTeacherAndAdviser();
        $this->makeStudents();
        $this->makeParents();
    }

    /**
     * The school to anchor newly created demo users to.
     */
    private function defaultSchoolId(): ?int
    {
        return SchoolProfile::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');
    }

    /**
     * The school id for a student, derived from its most recent enrollment.
     */
    private function schoolIdForStudent(Student $student): ?int
    {
        $campusId = Enrollment::query()
            ->where('student_id', $student->id)
            ->latest('id')
            ->value('campus_id');

        if ($campusId) {
            return Campus::query()->whereKey($campusId)->value('school_profile_id');
        }

        return $this->defaultSchoolId();
    }

    /**
     * A teacher user and an adviser user, each linked to a teaching Employee
     * that already owns a Teacher profile.
     */
    private function makeTeacherAndAdviser(): void
    {
        $employees = Employee::query()
            ->where('employment_type', 'teaching')
            ->whereDoesntHave('user')
            ->orderBy('id')
            ->take(2)
            ->get();

        if ($employees->count() < 2) {
            return;
        }

        $teacherUser = $this->makeUser(RoleEnum::TEACHER->roleName(), 'Teacher Demo', 'demo.teacher@konexus.local');
        $adviserUser = $this->makeUser(RoleEnum::ADVISER->roleName(), 'Adviser Demo', 'demo.adviser@konexus.local');

        $employees[0]->update(['user_id' => $teacherUser->id]);
        Teacher::query()->updateOrCreate(['employee_id' => $employees[0]->id]);

        $employees[1]->update(['user_id' => $adviserUser->id]);
        Teacher::query()->updateOrCreate(['employee_id' => $employees[1]->id]);
    }

    /**
     * Student users for the first few students, linked by user_id.
     */
    private function makeStudents(): void
    {
        Student::query()
            ->whereNull('user_id')
            ->orderBy('id')
            ->take(3)
            ->get()
            ->each(function (Student $student): void {
                $slug = str()->slug($student->full_name);

                $user = $this->makeUser(
                    RoleEnum::STUDENT->roleName(),
                    $student->full_name,
                    "demo.student.{$student->id}@konexus.local",
                    $this->schoolIdForStudent($student),
                );

                $student->update(['user_id' => $user->id]);
            });
    }

    /**
     * Parent users for the first few parents, linked by user_id.
     */
    private function makeParents(): void
    {
        ParentGuardian::query()
            ->whereNull('user_id')
            ->orderBy('id')
            ->take(2)
            ->get()
            ->each(function (ParentGuardian $parent): void {
                $user = $this->makeUser(
                    RoleEnum::PARENT->roleName(),
                    $parent->full_name,
                    "demo.parent.{$parent->id}@konexus.local",
                    $this->defaultSchoolId(),
                );

                $parent->update(['user_id' => $user->id]);
            });
    }

    /**
     * Create a user if missing, refresh its role, and reset its password.
     */
    private function makeUser(string $roleName, string $name, string $email, ?int $schoolProfileId = null): User
    {
        $schoolProfileId ??= $this->defaultSchoolId();

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make(self::PASSWORD),
                'is_active' => true,
                'email_verified_at' => now(),
                'school_profile_id' => $schoolProfileId,
            ],
        );

        if (! $user->hasRole($roleName)) {
            $user->syncRoles($roleName);
        }

        if ($user->school_profile_id !== $schoolProfileId) {
            $user->forceFill(['school_profile_id' => $schoolProfileId])->save();
        }

        $user->forceFill(['password' => Hash::make(self::PASSWORD), 'is_active' => true])->save();

        return $user;
    }
}
