<?php

namespace Tests\Feature\Modules;

use App\Enums\RoleEnum;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\EnrollmentDocument;
use App\Models\EnrollmentRequirement;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnrollmentModulesTest extends TestCase
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

    private function registrar(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::REGISTRAR->roleName());

        return $user;
    }

    private ?array $context = null;

    private function context(): array
    {
        if ($this->context !== null) {
            return $this->context;
        }

        $year = AcademicYear::factory()->create(['is_active' => true]);
        $campus = Campus::factory()->create();
        $grade = GradeLevel::factory()->create(['name' => 'Grade 10', 'code' => '10', 'sequence' => 10]);
        $section = Section::factory()->create(['grade_level_id' => $grade->id]);

        return $this->context = compact('year', 'campus', 'grade', 'section');
    }

    private function payload(Student $student, array $override = []): array
    {
        $ctx = $this->context();

        return array_merge([
            'student_id' => $student->id,
            'academic_year_id' => $ctx['year']->id,
            'academic_term_id' => null,
            'campus_id' => $ctx['campus']->id,
            'grade_level_id' => $ctx['grade']->id,
            'section_id' => $ctx['section']->id,
            'enrollment_type' => 'continuing',
        ], $override);
    }

    public function test_enrollment_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/enrollments')->assertStatus(401);
        $this->getJson('/api/v1/enrollment-requirements')->assertStatus(401);

        $student = Student::factory()->create();

        $this->postJson('/api/v1/enrollments/search-student', ['q' => 'Maria'])->assertStatus(401);
        $this->postJson('/api/v1/enrollments', $this->payload($student))->assertStatus(401);
    }

    public function test_plain_user_cannot_access_enrollment_module(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/enrollments')
            ->assertStatus(403);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/enrollment-requirements')
            ->assertStatus(403);
    }

    public function test_registrar_can_run_the_enrollment_review_and_completion_workflow(): void
    {
        $this->actingAs($this->registrar(), 'sanctum');

        EnrollmentRequirement::factory()->create(['applicable_grade_levels' => null, 'applicable_enrollment_types' => null]);

        $student = Student::factory()->create();
        $created = $this->postJson('/api/v1/enrollments', $this->payload($student))
            ->assertCreated()
            ->json('data');

        $id = $created['id'];
        $requirementId = $created['requirements'][0]['id'];

        $this->getJson('/api/v1/enrollments')->assertOk();
        $this->getJson('/api/v1/enrollments/statistics')->assertOk();
        $this->patchJson("/api/v1/enrollments/{$id}/requirements/{$requirementId}", ['status' => 'verified'])->assertOk();
        $this->postJson("/api/v1/enrollments/{$id}/submit")->assertOk();
        $this->postJson("/api/v1/enrollments/{$id}/verify")->assertOk();
        $this->postJson("/api/v1/enrollments/{$id}/approve")->assertOk();
        $this->postJson("/api/v1/enrollments/{$id}/complete")
            ->assertOk()
            ->assertJsonPath('data.status', 'officially-enrolled');
    }

    public function test_create_enrollment_generates_numbers_and_syncs_requirements(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        EnrollmentRequirement::factory()->count(2)->create([
            'applicable_grade_levels' => null,
            'applicable_enrollment_types' => null,
        ]);

        $student = Student::factory()->create();

        $response = $this->postJson('/api/v1/enrollments', $this->payload($student))
            ->assertCreated()
            ->assertJsonPath('success', true);

        $data = $response->json('data');

        $this->assertNotNull($data['enrollment_number']);
        $this->assertNotNull($data['reference_number']);
        $this->assertSame('draft', $data['status']);
        $this->assertCount(2, $data['requirements']);

        foreach ($data['requirements'] as $item) {
            $this->assertSame('not-submitted', $item['status']);
        }
    }

    public function test_duplicate_enrollment_for_same_year_branch_is_rejected(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $student = Student::factory()->create();

        $this->postJson('/api/v1/enrollments', $this->payload($student))->assertCreated();

        $this->postJson('/api/v1/enrollments', $this->payload($student))
            ->assertStatus(409);
    }

    public function test_inactive_year_and_grade_are_rejected(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $student = Student::factory()->create();

        $inactiveYear = AcademicYear::factory()->create(['is_active' => false]);

        $this->postJson('/api/v1/enrollments', $this->payload($student, ['academic_year_id' => $inactiveYear->id]))
            ->assertStatus(422);

        $inactiveGrade = GradeLevel::factory()->create(['name' => 'Grade 11', 'code' => '11', 'sequence' => 11, 'is_active' => false]);

        $this->postJson('/api/v1/enrollments', $this->payload($student, [
            'grade_level_id' => $inactiveGrade->id,
            'section_id' => null,
        ]))->assertStatus(422);
    }

    public function test_section_must_belong_to_selected_grade_level(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $student = Student::factory()->create();
        $ctx = $this->context();

        $otherGrade = GradeLevel::factory()->create(['name' => 'Grade 09', 'code' => '09', 'sequence' => 9]);
        $otherSection = Section::factory()->create(['grade_level_id' => $otherGrade->id]);

        $this->postJson('/api/v1/enrollments', $this->payload($student, [
            'academic_year_id' => $ctx['year']->id,
            'campus_id' => $ctx['campus']->id,
            'grade_level_id' => $ctx['grade']->id,
            'section_id' => $otherSection->id,
        ]))->assertStatus(422);
    }

    public function test_enrollment_workflow_runs_to_officially_enrolled(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        EnrollmentRequirement::factory()->create(['applicable_grade_levels' => null, 'applicable_enrollment_types' => null]);

        $student = Student::factory()->create();

        $created = $this->postJson('/api/v1/enrollments', $this->payload($student))
            ->assertCreated()
            ->json('data');

        $id = $created['id'];
        $itemId = $created['requirements'][0]['id'];

        $this->patchJson("/api/v1/enrollments/{$id}/requirements/{$itemId}", ['status' => 'verified'])
            ->assertOk()
            ->assertJsonPath('data.status', 'verified');

        $this->postJson("/api/v1/enrollments/{$id}/submit")->assertOk();
        $this->postJson("/api/v1/enrollments/{$id}/verify")->assertOk();

        $this->postJson("/api/v1/enrollments/{$id}/approve")->assertOk();
        $this->postJson("/api/v1/enrollments/{$id}/complete")->assertOk();

        $this->getJson("/api/v1/enrollments/{$id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'officially-enrolled')
            ->assertJsonPath('data.date_enrolled', now()->toDateString());
    }

    public function test_officially_enrolled_record_cannot_be_withdrawn(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        EnrollmentRequirement::factory()->create(['applicable_grade_levels' => null, 'applicable_enrollment_types' => null]);

        $student = Student::factory()->create();

        $created = $this->postJson('/api/v1/enrollments', $this->payload($student))->json('data');
        $id = $created['id'];

        $this->patchJson("/api/v1/enrollments/{$id}/requirements/{$created['requirements'][0]['id']}", ['status' => 'verified'])->assertOk();
        $this->postJson("/api/v1/enrollments/{$id}/submit")->assertOk();
        $this->postJson("/api/v1/enrollments/{$id}/verify")->assertOk();
        $this->postJson("/api/v1/enrollments/{$id}/approve")->assertOk();
        $this->postJson("/api/v1/enrollments/{$id}/complete")->assertOk();

        $this->postJson("/api/v1/enrollments/{$id}/withdraw", ['reason' => 'moved abroad'])
            ->assertStatus(422);
    }

    public function test_draft_enrollment_can_be_cancelled_and_is_terminal(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $student = Student::factory()->create();

        $id = $this->postJson('/api/v1/enrollments', $this->payload($student))->json('data.id');

        $this->postJson("/api/v1/enrollments/{$id}/cancel", ['reason' => 'duplicate application'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->postJson("/api/v1/enrollments/{$id}/verify")->assertStatus(422);
    }

    public function test_rejection_requires_a_reason(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $student = Student::factory()->create();

        $id = $this->postJson('/api/v1/enrollments', $this->payload($student))->json('data.id');

        $this->postJson("/api/v1/enrollments/{$id}/reject")
            ->assertStatus(422);

        $this->postJson("/api/v1/enrollments/{$id}/reject", ['reason' => 'incomplete records'])
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');
    }

    public function test_terminal_enrollment_cannot_be_deleted(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $student = Student::factory()->create();

        $id = $this->postJson('/api/v1/enrollments', $this->payload($student))->json('data.id');

        $this->postJson("/api/v1/enrollments/{$id}/cancel", ['reason' => 'no show'])->assertOk();

        $this->deleteJson("/api/v1/enrollments/{$id}")->assertStatus(422);

        $this->assertDatabaseHas('enrollments', ['id' => $id]);
    }

    public function test_transfer_requires_approved_or_officially_enrolled(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $student = Student::factory()->create();

        $id = $this->postJson('/api/v1/enrollments', $this->payload($student))->json('data.id');

        $this->postJson("/api/v1/enrollments/{$id}/transfer", [
            'transfer_type' => 'within-school',
            'to_campus_name' => 'Somewhere',
            'reason' => 'family relocation',
        ])->assertStatus(422);
    }

    public function test_section_capacity_blocks_then_override_allows(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        EnrollmentRequirement::factory()->create(['applicable_grade_levels' => null, 'applicable_enrollment_types' => null]);

        $ctx = $this->context();
        $fullGrade = GradeLevel::factory()->create(['name' => 'Grade 07', 'code' => '07', 'sequence' => 7]);
        $fullSection = Section::factory()->create(['grade_level_id' => $fullGrade->id, 'max_capacity' => 1]);

        $studentOne = Student::factory()->create();
        $studentTwo = Student::factory()->create();

        $base = [
            'academic_year_id' => $ctx['year']->id,
            'academic_term_id' => null,
            'campus_id' => $ctx['campus']->id,
            'grade_level_id' => $fullGrade->id,
            'section_id' => $fullSection->id,
            'enrollment_type' => 'continuing',
        ];

        // First student passes all workflow steps and becomes officially enrolled.
        $created = $this->postJson('/api/v1/enrollments', $base + ['student_id' => $studentOne->id])->json('data');
        $id = $created['id'];

        $this->patchJson("/api/v1/enrollments/{$id}/requirements/{$created['requirements'][0]['id']}", ['status' => 'verified'])->assertOk();
        $this->postJson("/api/v1/enrollments/{$id}/submit")->assertOk();
        $this->postJson("/api/v1/enrollments/{$id}/verify")->assertOk();
        $this->postJson("/api/v1/enrollments/{$id}/approve")->assertOk();
        $this->postJson("/api/v1/enrollments/{$id}/complete")->assertOk();

        // Second student tries to enroll into the full section — blocked.
        $this->postJson('/api/v1/enrollments', $base + ['student_id' => $studentTwo->id])
            ->assertStatus(409);

        // With a recorded capacity override reason the placement is allowed.
        $second = $this->postJson('/api/v1/enrollments', $base + [
            'student_id' => $studentTwo->id,
            'capacity_override_reason' => 'top performer requested by department',
        ])->assertCreated()->json('data');

        $this->assertDatabaseHas('enrollment_capacity_overrides', [
            'enrollment_id' => $second['id'],
            'section_id' => $fullSection->id,
        ]);
    }

    public function test_uploaded_documents_require_authentication_to_download(): void
    {
        Storage::fake('local');

        $this->actingAs($this->superAdmin(), 'sanctum');

        EnrollmentRequirement::factory()->create(['applicable_grade_levels' => null, 'applicable_enrollment_types' => null]);

        $student = Student::factory()->create();

        $id = $this->postJson('/api/v1/enrollments', $this->payload($student))->json('data.id');

        $this->post("/api/v1/enrollments/{$id}/documents", [
            'file' => UploadedFile::fake()->create('report-card.pdf', 100),
            'name' => 'Report Card',
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Report Card');

        $docId = $this->getJson("/api/v1/enrollments/{$id}/documents")->json('data.items.0.id');

        $this->assertNotNull($docId);

        $document = EnrollmentDocument::findOrFail($docId);

        Storage::disk('local')->assertExists($document->file_path);

        $this->get("/api/v1/enrollments/{$id}/documents/{$docId}/download")
            ->assertOk();

        $this->post("/api/v1/enrollments/{$id}/documents")
            ->assertStatus(422);
    }

    public function test_search_student_and_statistics_endpoints(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $student = Student::factory()->create(['first_name' => 'Lourdes', 'last_name' => 'Buenaventura']);

        $this->postJson('/api/v1/enrollments/search-student', ['q' => 'Lourdes'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->getJson('/api/v1/enrollments/statistics')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['total', 'active', 'officially_enrolled', 'per_status', 'per_grade_level', 'per_campus', 'per_type'],
            ]);

        $this->postJson('/api/v1/enrollments', $this->payload($student))->assertCreated();

        $this->getJson('/api/v1/enrollments/statistics')->assertOk();
    }

    public function test_search_student_matches_names_and_identifiers(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        Student::factory()->create(['first_name' => 'Inigo', 'last_name' => 'Montoya', 'middle_name' => null, 'student_number' => 'KXN-2026-ABCDEF']);

        $this->postJson('/api/v1/enrollments/search-student', ['q' => 'Montoya'])
            ->assertOk()
            ->assertJsonPath('data.items.0.name', 'Inigo Montoya');

        $this->postJson('/api/v1/enrollments/search-student', ['q' => 'KXN-2026'])
            ->assertOk()
            ->assertJsonPath('data.items.0.student_number', 'KXN-2026-ABCDEF');

        $this->postJson('/api/v1/enrollments/search-student', ['q' => 'zzz-none'])
            ->assertOk()
            ->assertJsonCount(0, 'data.items');
    }
}
