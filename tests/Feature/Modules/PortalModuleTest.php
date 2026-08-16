<?php

namespace Tests\Feature\Modules;

use App\Enums\EnrollmentStatus;
use App\Enums\RoleEnum;
use App\Events\EnrollmentStatusChanged;
use App\Models\Employee;
use App\Models\Enrollment;
use App\Models\ParentGuardian;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    // ─────────────────────────────────────────
    // Student Portal
    // ─────────────────────────────────────────

    public function test_student_portal_requires_authentication(): void
    {
        $this->getJson('/api/v1/portal/student/dashboard')->assertStatus(401);
        $this->getJson('/api/v1/portal/parent/dashboard')->assertStatus(401);
        $this->getJson('/api/v1/portal/teacher/dashboard')->assertStatus(401);
    }

    public function test_student_portal_without_profile_returns_null_profile(): void
    {
        $user = $this->userWithRole(RoleEnum::STUDENT->roleName());

        $this->actingAs($user)
            ->getJson('/api/v1/portal/student/dashboard')
            ->assertOk()
            ->assertJsonPath('data.profile', null);
    }

    public function test_student_portal_returns_own_profile(): void
    {
        $user = $this->userWithRole(RoleEnum::STUDENT->roleName());
        $student = Student::factory()->create(['user_id' => $user->id, 'first_name' => 'Ana', 'last_name' => 'Diaz']);

        $this->actingAs($user)
            ->getJson('/api/v1/portal/student/dashboard')
            ->assertOk()
            ->assertJsonPath('data.profile.first_name', 'Ana')
            ->assertJsonPath('data.profile.last_name', 'Diaz');

        $this->actingAs($user)
            ->getJson('/api/v1/portal/student/schedule')
            ->assertOk();

        $this->actingAs($user)
            ->getJson('/api/v1/portal/student/grades')
            ->assertOk()
            ->assertJsonStructure(['data' => ['records', 'general_average']]);

        $this->actingAs($user)
            ->getJson('/api/v1/portal/student/attendance')
            ->assertOk()
            ->assertJsonStructure(['data' => ['summary']]);
    }

    // ─────────────────────────────────────────
    // Parent Portal
    // ─────────────────────────────────────────

    public function test_parent_portal_only_exposes_linked_children(): void
    {
        $user = $this->userWithRole(RoleEnum::PARENT->roleName());
        $parent = ParentGuardian::factory()->create(['user_id' => $user->id]);
        $ownChild = Student::factory()->create(['first_name' => 'Mia', 'last_name' => 'Cruz']);
        $otherChild = Student::factory()->create(['first_name' => 'Zoe', 'last_name' => 'Reyes']);
        $parent->students()->attach($ownChild);

        $this->actingAs($user)
            ->getJson('/api/v1/portal/parent/children')
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.first_name', 'Mia')
            ->assertJsonPath('data.items.0.last_name', 'Cruz');

        // A non-linked child must not be reachable.
        $this->actingAs($user)
            ->getJson("/api/v1/portal/parent/children/{$otherChild->id}")
            ->assertStatus(404);

        $this->actingAs($user)
            ->getJson("/api/v1/portal/parent/children/{$ownChild->id}/grades")
            ->assertOk();
    }

    // ─────────────────────────────────────────
    // Teacher Portal
    // ─────────────────────────────────────────

    public function test_teacher_portal_without_profile_returns_404(): void
    {
        $user = $this->userWithRole(RoleEnum::TEACHER->roleName());

        $this->actingAs($user)
            ->getJson('/api/v1/portal/teacher/dashboard')
            ->assertStatus(404);
    }

    public function test_teacher_portal_returns_own_dashboard(): void
    {
        $user = $this->userWithRole(RoleEnum::TEACHER->roleName());
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        Teacher::factory()->create(['employee_id' => $employee->id]);

        $this->actingAs($user)
            ->getJson('/api/v1/portal/teacher/dashboard')
            ->assertOk()
            ->assertJsonStructure(['data' => ['teacher', 'stats']]);

        $this->actingAs($user)
            ->getJson('/api/v1/portal/teacher/assignments')
            ->assertOk();
    }

    public function test_administrator_can_preview_teacher_portal_without_profile(): void
    {
        $user = $this->userWithRole(RoleEnum::SCHOOL_ADMINISTRATOR->roleName());

        $this->actingAs($user)
            ->getJson('/api/v1/portal/teacher/dashboard')
            ->assertOk()
            ->assertJsonPath('data.stats.assignments', 0)
            ->assertJsonPath('data.teacher.id', null);

        $this->actingAs($user)
            ->getJson('/api/v1/portal/teacher/assignments')
            ->assertOk()
            ->assertJsonPath('data.items', []);
    }

    // ─────────────────────────────────────────
    // Notification hooks
    // ─────────────────────────────────────────

    public function test_enrollment_status_change_notifies_student_and_parent(): void
    {
        $studentUser = $this->userWithRole(RoleEnum::STUDENT->roleName());
        $student = Student::factory()->create(['user_id' => $studentUser->id]);

        $parentUser = $this->userWithRole(RoleEnum::PARENT->roleName());
        $parent = ParentGuardian::factory()->create(['user_id' => $parentUser->id]);
        $parent->students()->attach($student);

        $enrollment = Enrollment::factory()->create([
            'student_id' => $student->id,
            'status' => EnrollmentStatus::DRAFT->value,
            'enrollment_number' => 'ENR-2026-0001',
        ]);

        $enrollment->status = EnrollmentStatus::FOR_VERIFICATION->value;
        $enrollment->save();

        EnrollmentStatusChanged::dispatch($enrollment, EnrollmentStatus::DRAFT->value);

        $this->assertDatabaseHas('notifications', ['notifiable_id' => $studentUser->id]);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $parentUser->id]);
    }
}
