<?php

namespace Tests\Feature\Modules;

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\SchoolProfile;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TuitionModuleTest extends TestCase
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

    private function school(string $name): SchoolProfile
    {
        return SchoolProfile::factory()->create(['name' => $name, 'is_active' => true]);
    }

    private function schoolAdmin(SchoolProfile $school): User
    {
        $user = User::factory()->create(['school_profile_id' => $school->id]);
        $user->assignRole(RoleEnum::SCHOOL_ADMINISTRATOR->roleName());

        return $user;
    }

    public function test_module_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/tuitions')->assertStatus(401);
        $this->postJson('/api/v1/tuitions', [])->assertStatus(401);
    }

    public function test_full_crud_lifecycle_with_computed_totals(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $student = Student::factory()->create();
        $year = AcademicYear::factory()->create();

        // Create – totals are computed from the fee breakdown.
        $store = $this->postJson('/api/v1/tuitions', [
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'tuition_fee' => 10000,
            'misc_fee' => 2000,
            'other_fees' => 500,
            'discount' => 500,
            'amount_paid' => 5000,
        ])->assertCreated()->assertJsonPath('success', true);

        $id = $store->json('data.id');

        $store
            ->assertJsonPath('data.total', 12000)
            ->assertJsonPath('data.balance', 7000)
            ->assertJsonPath('data.status', 'partial')
            ->assertJsonPath('data.student.id', $student->id)
            ->assertJsonPath('data.academic_year.id', $year->id);

        $this->assertStringStartsWith('TUIT-', (string) $store->json('data.reference_number'));

        // Show
        $this->getJson("/api/v1/tuitions/{$id}")
            ->assertOk()
            ->assertJsonPath('data.student.name', $student->full_name);

        // Update – paying the full amount clears the balance.
        $this->putJson("/api/v1/tuitions/{$id}", ['amount_paid' => 15000])
            ->assertOk()
            ->assertJsonPath('data.balance', 0)
            ->assertJsonPath('data.status', 'paid');

        // Soft delete
        $this->deleteJson("/api/v1/tuitions/{$id}")->assertOk();
        $this->assertSoftDeleted('tuitions', ['id' => $id]);

        // Restore
        $this->postJson("/api/v1/tuitions/{$id}/restore")->assertOk();
        $this->assertDatabaseHas('tuitions', ['id' => $id, 'deleted_at' => null]);

        // Force delete
        $this->deleteJson("/api/v1/tuitions/{$id}")->assertOk();
        $this->deleteJson("/api/v1/tuitions/{$id}/force")->assertOk();
        $this->assertDatabaseMissing('tuitions', ['id' => $id]);
    }

    public function test_store_validates_the_payload(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $this->postJson('/api/v1/tuitions', ['tuition_fee' => 1000])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['student_id', 'academic_year_id']]);
    }

    public function test_school_administrator_can_manage_own_tuitions(): void
    {
        $school = $this->school('Alpha School');
        $admin = $this->schoolAdmin($school);

        $this->actingAs($admin, 'sanctum');

        $student = Student::factory()->create();
        $year = AcademicYear::factory()->create();

        $this->postJson('/api/v1/tuitions', [
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'tuition_fee' => 10000,
        ])->assertCreated();

        $this->assertDatabaseHas('tuitions', [
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'school_profile_id' => $school->id,
            'total' => 10000,
            'status' => 'unpaid',
        ]);
    }

    public function test_tuitions_are_scoped_to_the_school(): void
    {
        $schoolA = $this->school('Alpha School');
        $schoolB = $this->school('Beta School');

        $this->actingAs($this->schoolAdmin($schoolA), 'sanctum');

        $studentA = Student::factory()->create();
        $yearA = AcademicYear::factory()->create();

        $created = $this->postJson('/api/v1/tuitions', [
            'student_id' => $studentA->id,
            'academic_year_id' => $yearA->id,
            'tuition_fee' => 10000,
        ])->assertCreated();
        $tuitionId = $created->json('data.id');

        // Admin A sees only their own tuition.
        $this->actingAs($this->schoolAdmin($schoolA), 'sanctum')
            ->getJson('/api/v1/tuitions')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.id', $tuitionId);

        // Admin B sees nothing and cannot read A's tuition.
        $this->actingAs($this->schoolAdmin($schoolB), 'sanctum')
            ->getJson('/api/v1/tuitions')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 0);

        $this->actingAs($this->schoolAdmin($schoolB), 'sanctum')
            ->getJson("/api/v1/tuitions/{$tuitionId}")
            ->assertNotFound();
    }

    public function test_plain_user_cannot_access_tuitions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/tuitions')
            ->assertStatus(403);
    }
}