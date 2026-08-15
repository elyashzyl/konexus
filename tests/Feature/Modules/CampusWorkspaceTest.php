<?php

namespace Tests\Feature\Modules;

use App\Enums\EnrollmentStatus;
use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\SchoolProfile;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampusWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_workspace_selection_scopes_operational_enrollments_to_the_active_campus(): void
    {
        $school = SchoolProfile::factory()->create(['is_active' => true]);
        $campusA = Campus::factory()->create(['school_profile_id' => $school->id, 'name' => 'Alpha Campus', 'is_active' => true]);
        $campusB = Campus::factory()->create(['school_profile_id' => $school->id, 'name' => 'Beta Campus', 'is_active' => true]);
        $admin = User::factory()->create(['school_profile_id' => $school->id]);
        $admin->assignRole(RoleEnum::SCHOOL_ADMINISTRATOR->roleName());
        $year = AcademicYear::factory()->create(['school_profile_id' => $school->id]);
        $grade = GradeLevel::factory()->create(['school_profile_id' => $school->id]);

        Enrollment::factory()->create([
            'school_profile_id' => $school->id,
            'student_id' => Student::factory()->create(['school_profile_id' => $school->id])->id,
            'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id,
            'campus_id' => $campusA->id,
            'status' => EnrollmentStatus::DRAFT->value,
        ]);
        Enrollment::factory()->create([
            'school_profile_id' => $school->id,
            'student_id' => Student::factory()->create(['school_profile_id' => $school->id])->id,
            'academic_year_id' => $year->id,
            'grade_level_id' => $grade->id,
            'campus_id' => $campusB->id,
            'status' => EnrollmentStatus::DRAFT->value,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/workspaces')
            ->assertOk()
            ->assertJsonPath('data.active_campus.id', $campusA->id)
            ->assertJsonCount(2, 'data.campuses');

        $this->getJson('/api/v1/enrollments')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.campus.id', $campusA->id);

        $this->putJson('/api/v1/workspaces/active', ['campus_id' => $campusB->id])
            ->assertOk()
            ->assertJsonPath('data.active_campus.id', $campusB->id);

        $this->withHeader('X-Campus-Id', (string) $campusB->id)
            ->getJson('/api/v1/enrollments')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.campus.id', $campusB->id);
    }

    public function test_school_admin_can_create_a_campus_attached_to_their_school_profile(): void
    {
        $school = SchoolProfile::factory()->create(['is_active' => true]);
        $admin = User::factory()->create(['school_profile_id' => $school->id]);
        $admin->assignRole(RoleEnum::SCHOOL_ADMINISTRATOR->roleName());

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/campuses', [
                'school_profile_id' => $school->id,
                'name' => 'North Annex',
                'code' => 'NORTH',
                'is_active' => true,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('campuses', [
            'school_profile_id' => $school->id,
            'name' => 'North Annex',
            'code' => 'NORTH',
        ]);
    }

    public function test_workspace_header_cannot_select_a_campus_from_another_school(): void
    {
        $school = SchoolProfile::factory()->create(['is_active' => true]);
        $otherSchool = SchoolProfile::factory()->create(['is_active' => true]);
        Campus::factory()->create(['school_profile_id' => $school->id, 'is_active' => true]);
        $otherCampus = Campus::factory()->create(['school_profile_id' => $otherSchool->id, 'is_active' => true]);
        $admin = User::factory()->create(['school_profile_id' => $school->id]);
        $admin->assignRole(RoleEnum::SCHOOL_ADMINISTRATOR->roleName());

        $this->actingAs($admin, 'sanctum')
            ->withHeader('X-Campus-Id', (string) $otherCampus->id)
            ->getJson('/api/v1/workspaces')
            ->assertForbidden();
    }
}
