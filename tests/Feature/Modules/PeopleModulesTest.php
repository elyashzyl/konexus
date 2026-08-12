<?php

namespace Tests\Feature\Modules;

use App\Enums\RoleEnum;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Guardian;
use App\Models\ParentGuardian;
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
}
