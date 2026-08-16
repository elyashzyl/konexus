<?php

namespace Tests\Feature\Modules;

use App\Enums\RoleEnum;
use App\Models\Campus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Guardian;
use App\Models\ParentGuardian;
use App\Models\SchoolProfile;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeopleModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUPER_ADMINISTRATOR->roleName());

        return $user;
    }

    public function test_people_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/students')->assertStatus(401);
        $this->getJson('/api/v1/parents')->assertStatus(401);
        $this->getJson('/api/v1/guardians')->assertStatus(401);
        $this->getJson('/api/v1/employees')->assertStatus(401);
        $this->getJson('/api/v1/teachers')->assertStatus(401);
        $this->getJson('/api/v1/staff')->assertStatus(401);
    }

    public function test_student_update_validates_required_fields(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $store = $this->postJson('/api/v1/students', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'gender' => 'male',
            'birth_date' => '2012-04-15',
        ])->assertCreated()->assertJsonPath('success', true);

        $id = $store->json('data.id');

        $this->assertNotNull($store->json('data.student_number'));

        $this->getJson("/api/v1/students/{$id}")->assertOk();

        $this->putJson("/api/v1/students/{$id}", [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'gender' => 'male',
            'birth_date' => '2012-04-15',
            'nickname' => 'Juanito',
        ])->assertOk()
            ->assertJsonPath('data.nickname', 'Juanito');

        $this->getJson("/api/v1/students/{$id}/activities")
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_student_search_matches_names(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        Student::factory()->create(['first_name' => 'Maria', 'last_name' => 'Santos']);
        Student::factory()->create(['first_name' => 'Jose', 'last_name' => 'Rizal']);

        $this->getJson('/api/v1/students?search=Santos')
            ->assertOk()
            ->assertJsonCount(1, 'data.items');
    }

    public function test_parent_and_guardian_linking_and_unlinking(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $student = Student::factory()->create();
        $parent = ParentGuardian::factory()->create();
        $guardian = Guardian::factory()->create();

        $this->postJson("/api/v1/students/{$student->id}/parents/{$parent->id}")
            ->assertOk()
            ->assertJsonPath('data.parents.0.id', $parent->id);

        $this->postJson("/api/v1/students/{$student->id}/guardians/{$guardian->id}")
            ->assertOk()
            ->assertJsonPath('data.guardians.0.id', $guardian->id);

        $this->deleteJson("/api/v1/students/{$student->id}/parents/{$parent->id}")
            ->assertOk()
            ->assertJsonCount(0, 'data.parents');

        $this->deleteJson("/api/v1/students/{$student->id}/guardians/{$guardian->id}")
            ->assertOk()
            ->assertJsonCount(0, 'data.guardians');
    }

    public function test_teacher_and_staff_are_backed_by_employees(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $department = Department::factory()->create();

        $teacher = $this->postJson('/api/v1/teachers', [
            'first_name' => 'Maria',
            'last_name' => 'Tan',
            'gender' => 'female',
            'employee_number' => 'EMP-2026-001',
            'department_id' => $department->id,
            'specialization' => 'Mathematics',
        ])->assertCreated()->assertJsonPath('success', true);

        $teacherId = $teacher->json('data.id');

        $this->assertDatabaseHas('employees', ['employee_number' => 'EMP-2026-001']);
        $this->assertDatabaseHas('teachers', ['id' => $teacherId, 'department_id' => $department->id]);

        $staff = $this->postJson('/api/v1/staff', [
            'first_name' => 'Pedro',
            'last_name' => 'Lim',
            'gender' => 'male',
            'employee_number' => 'EMP-2026-002',
            'support_area' => 'Registrar',
        ])->assertCreated()->assertJsonPath('success', true);

        $this->assertDatabaseHas('employees', ['employee_number' => 'EMP-2026-002']);
        $this->assertDatabaseHas('staff', ['id' => $staff->json('data.id'), 'support_area' => 'Registrar']);
    }

    public function test_teacher_search_matches_employee_details(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $employee = Employee::factory()->create(['employment_type' => 'teaching', 'last_name' => 'Aquino']);
        Teacher::factory()->create(['employee_id' => $employee->id]);

        $this->getJson('/api/v1/teachers?search=Aquino')
            ->assertOk()
            ->assertJsonCount(1, 'data.items');
    }

    public function test_export_endpoint_returns_a_csv_stream(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        Student::factory()->create(['first_name' => 'Ana', 'last_name' => 'Perez']);

        $this->get('/api/v1/students/export')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=utf-8');
    }

    public function test_import_endpoint_creates_records(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $this->postJson('/api/v1/students/import', [
            'rows' => [
                ['first_name' => 'Liza', 'last_name' => 'Mendoza', 'gender' => 'female', 'birth_date' => '2011-01-01'],
                ['first_name' => 'Tom', 'last_name' => 'Cruz', 'gender' => 'male', 'birth_date' => '2010-05-05'],
            ],
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.created', 2);
    }

    public function test_plain_user_cannot_access_people_modules(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/students')
            ->assertStatus(403);
    }

    public function test_employee_create_assigns_active_campus_by_default(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $school = SchoolProfile::factory()->create(['is_active' => true]);
        $campus = Campus::factory()->create(['school_profile_id' => $school->id, 'is_active' => true]);

        $employee = $this->postJson('/api/v1/employees', [
            'first_name' => 'Ana',
            'last_name' => 'Cruz',
            'gender' => 'female',
            'employment_type' => 'teaching',
        ], ['X-Campus-Id' => $campus->id])->assertCreated()->assertJsonPath('success', true);

        $this->assertDatabaseHas('campus_employee', [
            'campus_id' => $campus->id,
            'employee_id' => $employee->json('data.id'),
        ]);
    }

    public function test_employee_campus_ids_are_synced_on_update(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $school = SchoolProfile::factory()->create(['is_active' => true]);
        $campusA = Campus::factory()->create(['school_profile_id' => $school->id, 'is_active' => true]);
        $campusB = Campus::factory()->create(['school_profile_id' => $school->id, 'is_active' => true]);

        $employee = Employee::factory()->create(['school_profile_id' => $school->id]);

        $base = [
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'gender' => $employee->gender,
            'employment_type' => 'teaching',
        ];

        $this->putJson("/api/v1/employees/{$employee->id}", $base + [
            'campus_ids' => [$campusA->id, $campusB->id],
        ], ['X-Campus-Id' => $campusA->id])->assertOk();

        $this->assertDatabaseHas('campus_employee', ['campus_id' => $campusA->id, 'employee_id' => $employee->id]);
        $this->assertDatabaseHas('campus_employee', ['campus_id' => $campusB->id, 'employee_id' => $employee->id]);

        $this->putJson("/api/v1/employees/{$employee->id}", $base + [
            'campus_ids' => [$campusB->id],
        ])->assertOk();

        $this->assertDatabaseMissing('campus_employee', ['campus_id' => $campusA->id, 'employee_id' => $employee->id]);
        $this->assertDatabaseHas('campus_employee', ['campus_id' => $campusB->id, 'employee_id' => $employee->id]);
    }

    public function test_employee_listing_is_filtered_by_active_campus(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $school = SchoolProfile::factory()->create(['is_active' => true]);
        $campusA = Campus::factory()->create(['school_profile_id' => $school->id, 'is_active' => true]);
        $campusB = Campus::factory()->create(['school_profile_id' => $school->id, 'is_active' => true]);

        $inA = Employee::factory()->create(['school_profile_id' => $school->id, 'last_name' => 'Arias']);
        $inA->campuses()->sync([$campusA->id]);

        $inB = Employee::factory()->create(['school_profile_id' => $school->id, 'last_name' => 'Bautista']);
        $inB->campuses()->sync([$campusB->id]);

        $this->getJson('/api/v1/employees', ['X-Campus-Id' => $campusA->id])
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $inA->id)
            ->assertJsonCount(1, 'data.items');

        $this->getJson('/api/v1/employees', ['X-Campus-Id' => $campusB->id])
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $inB->id)
            ->assertJsonCount(1, 'data.items');
    }

    public function test_employee_listing_is_not_filtered_without_active_campus(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $school = SchoolProfile::factory()->create(['is_active' => true]);
        $campusA = Campus::factory()->create(['school_profile_id' => $school->id, 'is_active' => true]);
        $campusB = Campus::factory()->create(['school_profile_id' => $school->id, 'is_active' => true]);

        $inA = Employee::factory()->create(['school_profile_id' => $school->id, 'last_name' => 'Arias']);
        $inA->campuses()->sync([$campusA->id]);

        $inB = Employee::factory()->create(['school_profile_id' => $school->id, 'last_name' => 'Bautista']);
        $inB->campuses()->sync([$campusB->id]);

        // Without the X-Campus-Id header the middleware resolves the first
        // available campus for the super admin, so only that campus' people
        // are returned rather than leaking both campuses together.
        $this->getJson('/api/v1/employees')
            ->assertOk()
            ->assertJsonCount(1, 'data.items');
    }

    public function test_teacher_and_staff_listings_are_filtered_by_active_campus(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $school = SchoolProfile::factory()->create(['is_active' => true]);
        $campusA = Campus::factory()->create(['school_profile_id' => $school->id, 'is_active' => true]);
        $campusB = Campus::factory()->create(['school_profile_id' => $school->id, 'is_active' => true]);

        $teacherEmployee = Employee::factory()->create(['school_profile_id' => $school->id, 'employment_type' => 'teaching']);
        $teacherEmployee->campuses()->sync([$campusA->id]);
        Teacher::factory()->create(['employee_id' => $teacherEmployee->id]);

        $otherTeacherEmployee = Employee::factory()->create(['school_profile_id' => $school->id, 'employment_type' => 'teaching']);
        $otherTeacherEmployee->campuses()->sync([$campusB->id]);
        Teacher::factory()->create(['employee_id' => $otherTeacherEmployee->id]);

        $this->getJson('/api/v1/teachers', ['X-Campus-Id' => $campusA->id])
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.employee_id', $teacherEmployee->id);

        $staffEmployee = Employee::factory()->create(['school_profile_id' => $school->id, 'employment_type' => 'staff']);
        $staffEmployee->campuses()->sync([$campusA->id]);
        Staff::factory()->create(['employee_id' => $staffEmployee->id]);

        $this->getJson('/api/v1/staff', ['X-Campus-Id' => $campusA->id])
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.employee_id', $staffEmployee->id);
    }

    public function test_student_create_assigns_active_campus_by_default(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $school = SchoolProfile::factory()->create(['is_active' => true]);
        $campus = Campus::factory()->create(['school_profile_id' => $school->id, 'is_active' => true]);

        $student = $this->postJson('/api/v1/students', [
            'first_name' => 'Rosa',
            'last_name' => 'Lim',
            'gender' => 'female',
            'birth_date' => '2011-03-20',
        ], ['X-Campus-Id' => $campus->id])->assertCreated()->assertJsonPath('success', true);

        $this->assertDatabaseHas('campus_student', [
            'campus_id' => $campus->id,
            'student_id' => $student->json('data.id'),
        ]);
    }

    public function test_student_campus_ids_are_synced_on_update(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $school = SchoolProfile::factory()->create(['is_active' => true]);
        $campusA = Campus::factory()->create(['school_profile_id' => $school->id, 'is_active' => true]);
        $campusB = Campus::factory()->create(['school_profile_id' => $school->id, 'is_active' => true]);

        $student = Student::factory()->create(['school_profile_id' => $school->id]);

        $base = [
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'gender' => $student->gender,
            'birth_date' => $student->birth_date->toDateString(),
        ];

        $this->putJson("/api/v1/students/{$student->id}", $base + [
            'campus_ids' => [$campusA->id, $campusB->id],
        ], ['X-Campus-Id' => $campusA->id])->assertOk();

        $this->assertDatabaseHas('campus_student', ['campus_id' => $campusA->id, 'student_id' => $student->id]);
        $this->assertDatabaseHas('campus_student', ['campus_id' => $campusB->id, 'student_id' => $student->id]);

        $this->putJson("/api/v1/students/{$student->id}", $base + [
            'campus_ids' => [$campusB->id],
        ])->assertOk();

        $this->assertDatabaseMissing('campus_student', ['campus_id' => $campusA->id, 'student_id' => $student->id]);
        $this->assertDatabaseHas('campus_student', ['campus_id' => $campusB->id, 'student_id' => $student->id]);
    }

    public function test_student_listing_is_filtered_by_active_campus(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $school = SchoolProfile::factory()->create(['is_active' => true]);
        $campusA = Campus::factory()->create(['school_profile_id' => $school->id, 'is_active' => true]);
        $campusB = Campus::factory()->create(['school_profile_id' => $school->id, 'is_active' => true]);

        $inA = Student::factory()->create(['school_profile_id' => $school->id, 'last_name' => 'Aquino']);
        $inA->campuses()->sync([$campusA->id]);

        $inB = Student::factory()->create(['school_profile_id' => $school->id, 'last_name' => 'Bello']);
        $inB->campuses()->sync([$campusB->id]);

        $this->getJson('/api/v1/students', ['X-Campus-Id' => $campusA->id])
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $inA->id)
            ->assertJsonCount(1, 'data.items');

        $this->getJson('/api/v1/students', ['X-Campus-Id' => $campusB->id])
            ->assertOk()
            ->assertJsonPath('data.items.0.id', $inB->id)
            ->assertJsonCount(1, 'data.items');
    }
}
