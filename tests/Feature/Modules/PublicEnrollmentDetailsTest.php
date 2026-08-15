<?php

namespace Tests\Feature\Modules;

use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\SchoolProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PublicEnrollmentDetailsTest extends TestCase
{
    use RefreshDatabase;

    private function activeSchool(): SchoolProfile
    {
        return SchoolProfile::factory()->create(['name' => 'Details School', 'is_active' => true]);
    }

    private function pendingEnrollment(?SchoolProfile $school = null): Enrollment
    {
        $school ??= $this->activeSchool();
        $year = AcademicYear::factory()->create(['school_profile_id' => $school->id]);

        return Enrollment::factory()->create([
            'school_profile_id' => $school->id,
            'academic_year_id' => $year->id,
            'status' => EnrollmentStatus::PENDING->value,
            'department' => 'junior-high',
            'track' => 'chinese',
            'incoming_level' => 'Grade 7',
            'email' => 'applicant@example.com',
            'mobile_number' => '+63 900 000 0000',
            'student_id' => null,
        ]);
    }

    private function studentPayload(array $overrides = []): array
    {
        return array_merge([
            'school_student_id' => 'BPHS-2026-001',
            'lrn' => '123456789012',
            'first_name' => 'Juan',
            'middle_name' => 'Dela',
            'last_name' => 'Cruz',
            'extension_name' => null,
            'nickname' => 'Jun',
            'birth_date' => '2013-05-10',
            'gender' => 'male',
            'citizenship' => 'Filipino',
            'religion' => 'Roman Catholic',
            'mobile_number' => '09171234567',
            'email' => 'juan@example.com',
            'place_of_birth' => 'Baguio City',
            'ethnicity' => 'Igorot',
            'is_indigenous' => true,
            'mother_tongue' => 'Ilocano',
            'telephone_number' => '074-123-4567',
            'current_address' => '123 Session Road',
            'current_province' => 'Benguet',
            'current_city' => 'Baguio',
            'current_barangay' => 'Session',
            'interests' => ['academics', 'sports'],
        ], $overrides);
    }

    private function familyPayload(array $overrides = []): array
    {
        return array_merge([
            'family_monthly_income' => 'PHP 20,000 - 30,000',
            'father' => [
                'last_name' => 'Cruz',
                'first_name' => 'Pedro',
                'middle_name' => 'Santos',
                'mobile_number' => '09171111111',
                'email' => 'pedro@example.com',
                'occupation' => 'Engineer',
                'address' => '456 Taft Ave',
            ],
            'mother' => [
                'last_name' => 'Cruz',
                'first_name' => 'Maria',
                'middle_name' => 'Reyes',
                'maiden_name' => 'Maria Reyes Santos',
                'mobile_number' => '09172222222',
                'email' => 'maria@example.com',
                'occupation' => 'Teacher',
                'address' => '456 Taft Ave',
            ],
            'guardian' => [
                'last_name' => 'Dizon',
                'first_name' => 'Ana',
                'middle_name' => 'Perez',
                'relationship' => 'Grandmother',
                'mobile_number' => '09173333333',
                'address' => '789 Baguio',
                'occupation' => 'Retired',
            ],
        ], $overrides);
    }

    public function test_student_info_creates_and_links_a_student(): void
    {
        $enrollment = $this->pendingEnrollment();

        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/student", $this->studentPayload())
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.student.first_name', 'Juan')
            ->assertJsonPath('data.student.age', 13)
            ->assertJsonPath('data.student.interests', ['academics', 'sports']);

        $this->assertDatabaseHas('students', [
            'first_name' => 'Juan',
            'last_name' => 'Cruz',
            'lrn' => '123456789012',
            'is_indigenous' => 1,
            'school_profile_id' => $enrollment->school_profile_id,
        ]);

        $studentId = $enrollment->fresh()->student_id;
        $this->assertNotNull($studentId);
        $this->assertStringStartsWith('KXN-', (string) \App\Models\Student::find($studentId)->student_number);
    }

    public function test_student_info_updates_the_existing_student(): void
    {
        $enrollment = $this->pendingEnrollment();

        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/student", $this->studentPayload())->assertOk();
        $studentId = $enrollment->fresh()->student_id;

        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/student", $this->studentPayload([
            'first_name' => 'Pedro',
            'nickname' => 'PJ',
            'interests' => ['arts'],
        ]))->assertOk()->assertJsonPath('data.student.first_name', 'Pedro');

        $this->assertDatabaseCount('students', 1);
        $this->assertSame(1, $enrollment->fresh()->student_id);
        $this->assertDatabaseHas('students', ['id' => $studentId, 'first_name' => 'Pedro', 'nickname' => 'PJ']);
    }

    public function test_student_info_validates_the_payload(): void
    {
        $enrollment = $this->pendingEnrollment();

        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/student", [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['first_name', 'last_name', 'birth_date', 'gender']]);
    }

    public function test_student_info_rejects_invalid_interest_and_lrn(): void
    {
        $enrollment = $this->pendingEnrollment();

        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/student", $this->studentPayload([
            'lrn' => '123',
            'interests' => ['gaming'],
        ]))->assertStatus(422)
            ->assertJsonStructure(['errors' => ['lrn', 'interests.0']]);
    }

    public function test_student_photo_is_uploaded_and_validated(): void
    {
        $enrollment = $this->pendingEnrollment();
        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/student", $this->studentPayload())->assertOk();
        $studentId = $enrollment->fresh()->student_id;

        $this->post("/api/v1/public/enrollments/{$enrollment->id}/student/photo", [
            'photo' => UploadedFile::fake()->image('photo.jpg', 600, 600),
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertNotNull(\App\Models\Student::find($studentId)->profile_picture_path);

        $this->post("/api/v1/public/enrollments/{$enrollment->id}/student/photo", [
            'photo' => UploadedFile::fake()->image('wide.jpg', 800, 400),
        ])->assertStatus(422);
    }

    public function test_photo_requires_a_linked_student(): void
    {
        $enrollment = $this->pendingEnrollment();

        $this->post("/api/v1/public/enrollments/{$enrollment->id}/student/photo", [
            'photo' => UploadedFile::fake()->image('photo.jpg', 600, 600),
        ])->assertStatus(422);
    }

    public function test_family_background_creates_parents_guardian_and_emergency_contact(): void
    {
        $enrollment = $this->pendingEnrollment();
        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/student", $this->studentPayload())->assertOk();
        $studentId = $enrollment->fresh()->student_id;

        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/family", $this->familyPayload())
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.family.father.last_name', 'Cruz')
            ->assertJsonPath('data.family.mother.maiden_name', 'Maria Reyes Santos')
            ->assertJsonPath('data.family.guardian.relationship', 'Grandmother');

        $this->assertDatabaseHas('parents', ['relationship' => 'father', 'first_name' => 'Pedro']);
        $this->assertDatabaseHas('parents', ['relationship' => 'mother', 'first_name' => 'Maria', 'maiden_name' => 'Maria Reyes Santos']);
        $this->assertDatabaseHas('guardians', ['first_name' => 'Ana', 'relationship' => 'Grandmother']);

        $this->assertDatabaseHas('students', [
            'id' => $studentId,
            'family_monthly_income' => 'PHP 20,000 - 30,000',
            'emergency_contact_name' => 'Ana Perez Dizon',
            'emergency_contact_relationship' => 'Grandmother',
            'emergency_contact_mobile' => '09173333333',
        ]);

        $this->assertDatabaseHas('parent_student', ['student_id' => $studentId]);
        $this->assertDatabaseHas('guardian_student', ['student_id' => $studentId]);
    }

    public function test_family_background_marks_not_applicable_parents(): void
    {
        $enrollment = $this->pendingEnrollment();
        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/student", $this->studentPayload())->assertOk();

        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/family", $this->familyPayload([
            'father' => ['not_applicable' => true, 'last_name' => null, 'first_name' => null],
        ]))->assertOk();

        $this->assertDatabaseHas('parents', ['relationship' => 'father', 'not_applicable' => 1]);
    }

    public function test_family_requires_a_linked_student(): void
    {
        $enrollment = $this->pendingEnrollment();

        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/family", $this->familyPayload())
            ->assertStatus(422);
    }

    public function test_show_returns_the_application_with_student_and_family(): void
    {
        $enrollment = $this->pendingEnrollment();
        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/student", $this->studentPayload())->assertOk();
        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/family", $this->familyPayload())->assertOk();

        $this->getJson("/api/v1/public/enrollments/{$enrollment->id}")
            ->assertOk()
            ->assertJsonPath('data.application.reference_number', $enrollment->reference_number)
            ->assertJsonPath('data.student.first_name', 'Juan')
            ->assertJsonPath('data.family.mother.first_name', 'Maria')
            ->assertJsonPath('data.family.family_monthly_income', 'PHP 20,000 - 30,000');
    }

    public function test_missing_application_returns_404(): void
    {
        $this->getJson('/api/v1/public/enrollments/999999')->assertNotFound();
    }
}