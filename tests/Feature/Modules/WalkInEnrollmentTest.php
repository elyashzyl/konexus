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

class WalkInEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function registrar(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::REGISTRAR->roleName());

        return $user;
    }

    private function plainUser(): User
    {
        return User::factory()->create();
    }

    /**
     * @return array{school: SchoolProfile, campus: Campus, year: AcademicYear, level: GradeLevel}
     */
    private function enrollmentContext(): array
    {
        $school = SchoolProfile::factory()->create(['name' => 'Walk-in Enrollment School', 'is_active' => true]);

        $campus = Campus::factory()->create([
            'school_profile_id' => $school->id,
            'name' => 'Main Campus',
            'is_active' => true,
        ]);

        $year = AcademicYear::factory()->create(['school_profile_id' => $school->id]);

        $level = GradeLevel::factory()->create([
            'school_profile_id' => $school->id,
            'campus_id' => $campus->id,
            'education_level' => 'junior-high',
            'is_active' => true,
        ]);

        return compact('school', 'campus', 'year', 'level');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = [], ?array $context = null): array
    {
        $context ??= $this->enrollmentContext();

        return array_merge([
            'school_profile_id' => $context['school']->id,
            'campus_id' => $context['campus']->id,
            'academic_year_id' => $context['year']->id,
            'department' => 'junior-high',
            'strand' => null,
            'status' => 'new-student',
            'incoming_level' => $context['level']->name,
            'track' => 'chinese',
            'email' => 'walkin@example.com',
            'mobile_number' => '+63 900 000 0000',
        ], $overrides);
    }

    public function test_walk_in_routes_require_authentication(): void
    {
        $this->postJson('/api/v1/enrollments/apply')->assertStatus(401);
        $this->getJson('/api/v1/enrollments/1/application')->assertStatus(401);
        $this->putJson('/api/v1/enrollments/1/student', [])->assertStatus(401);
        $this->putJson('/api/v1/enrollments/1/family', [])->assertStatus(401);
        $this->putJson('/api/v1/enrollments/1/details', [])->assertStatus(401);
        $this->postJson('/api/v1/enrollments/1/signature', [])->assertStatus(401);
    }

    public function test_plain_user_cannot_use_the_walk_in_wizard(): void
    {
        $this->actingAs($this->plainUser(), 'sanctum')
            ->postJson('/api/v1/enrollments/apply', $this->validPayload())
            ->assertStatus(403);
    }

    public function test_registrar_creates_a_draft_application(): void
    {
        $context = $this->enrollmentContext();

        $this->actingAs($this->registrar(), 'sanctum')
            ->postJson('/api/v1/enrollments/apply', $this->validPayload([], $context))
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'draft');

        $this->assertDatabaseHas('enrollments', [
            'school_profile_id' => $context['school']->id,
            'campus_id' => $context['campus']->id,
            'grade_level_id' => $context['level']->id,
            'department' => 'junior-high',
            'status' => EnrollmentStatus::DRAFT->value,
            'enrollment_type' => 'new-student',
            'application_submitted_at' => null,
            'application_expires_at' => null,
        ]);
    }

    public function test_walk_in_accepts_every_enrollment_type(): void
    {
        $this->actingAs($this->registrar(), 'sanctum');

        foreach (['new-student', 'continuing', 'returning', 'transferee', 're-enrollee'] as $type) {
            $this->postJson('/api/v1/enrollments/apply', $this->validPayload(['status' => $type]))
                ->assertStatus(201)
                ->assertJsonPath('data.status', 'draft');
        }
    }

    public function test_draft_can_be_resumed(): void
    {
        $context = $this->enrollmentContext();
        $registrar = $this->registrar();

        $created = $this->actingAs($registrar, 'sanctum')
            ->postJson('/api/v1/enrollments/apply', $this->validPayload([], $context))
            ->assertStatus(201)
            ->json('data');

        $this->actingAs($registrar, 'sanctum')
            ->getJson("/api/v1/enrollments/{$created['id']}/application")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.application.reference_number', $created['reference_number'])
            ->assertJsonPath('data.application.enrollment_type', 'new-student');
    }

    public function test_registrar_can_complete_the_wizard_and_submit(): void
    {
        $context = $this->enrollmentContext();
        $registrar = $this->registrar();

        $created = $this->actingAs($registrar, 'sanctum')
            ->postJson('/api/v1/enrollments/apply', $this->validPayload([], $context))
            ->assertStatus(201)
            ->json('data');

        $id = $created['id'];

        $this->actingAs($registrar, 'sanctum')
            ->putJson("/api/v1/enrollments/{$id}/student", [
                'first_name' => 'Juan',
                'middle_name' => 'Santos',
                'last_name' => 'Dela Cruz',
                'birth_date' => '2012-04-15',
                'gender' => 'male',
                'citizenship' => 'Filipino',
            ])
            ->assertOk();

        $studentId = $this->actingAs($registrar, 'sanctum')
            ->getJson("/api/v1/enrollments/{$id}/application")
            ->assertOk()
            ->json('data.student.id');

        $this->assertNotNull($studentId);

        $this->actingAs($registrar, 'sanctum')
            ->putJson("/api/v1/enrollments/{$id}/family", [
                'father' => ['first_name' => 'Pedro', 'last_name' => 'Dela Cruz', 'mobile_number' => '09171234567'],
                'mother' => ['first_name' => 'Maria', 'last_name' => 'Dela Cruz'],
                'guardian' => ['first_name' => 'Lola', 'last_name' => 'Dela Cruz', 'relationship' => 'Grandmother'],
            ])
            ->assertOk();

        $this->actingAs($registrar, 'sanctum')
            ->putJson("/api/v1/enrollments/{$id}/details", [
                'siblings' => [
                    ['last_name' => 'Dela Cruz', 'first_name' => 'Ana', 'grade_level' => 'Grade 8'],
                ],
                'tuition_plan' => 'School Tuition Plan',
                'agreement' => [
                    'photo_consent' => true,
                    'registration_consent' => true,
                    'credentialing_consent' => true,
                    'rules_consent' => true,
                    'date_of_registration' => now()->toDateString(),
                    'initial_payment' => 10000,
                ],
            ])
            ->assertOk();

        $this->actingAs($registrar, 'sanctum')
            ->postJson("/api/v1/enrollments/{$id}/signature", [
                'role' => 'student',
                'signer_name' => 'Juan Dela Cruz',
                'signature_data' => 'data:image/png;base64,AAA',
            ])
            ->assertOk();

        $this->actingAs($registrar, 'sanctum')
            ->postJson("/api/v1/enrollments/{$id}/signature", [
                'role' => 'parent',
                'signer_name' => 'Pedro Dela Cruz',
                'signature_data' => 'data:image/png;base64,BBB',
            ])
            ->assertOk();

        $this->actingAs($registrar, 'sanctum')
            ->postJson("/api/v1/enrollments/{$id}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', EnrollmentStatus::FOR_PAYMENT->value);

        $this->assertDatabaseHas('enrollments', [
            'id' => $id,
            'status' => EnrollmentStatus::FOR_PAYMENT->value,
            'student_id' => $studentId,
        ]);
    }

    public function test_a_submitted_enrollment_cannot_be_edited_by_the_wizard(): void
    {
        $context = $this->enrollmentContext();
        $registrar = $this->registrar();

        $created = $this->actingAs($registrar, 'sanctum')
            ->postJson('/api/v1/enrollments/apply', $this->validPayload([], $context))
            ->assertStatus(201)
            ->json('data');

        $id = $created['id'];

        $this->actingAs($registrar, 'sanctum')
            ->postJson("/api/v1/enrollments/{$id}/submit")
            ->assertOk();

        $this->actingAs($registrar, 'sanctum')
            ->putJson("/api/v1/enrollments/{$id}/details", ['tuition_plan' => 'Other Plan'])
            ->assertStatus(422);
    }

    public function test_the_walk_in_apply_validates_the_payload(): void
    {
        $this->actingAs($this->registrar(), 'sanctum')
            ->postJson('/api/v1/enrollments/apply', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => [
                'school_profile_id',
                'campus_id',
                'academic_year_id',
                'department',
                'status',
                'incoming_level',
                'track',
                'email',
                'mobile_number',
            ]]);
    }
}