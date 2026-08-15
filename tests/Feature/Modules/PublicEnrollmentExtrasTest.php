<?php

namespace Tests\Feature\Modules;

use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\SchoolProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicEnrollmentExtrasTest extends TestCase
{
    use RefreshDatabase;

    private function activeSchool(): SchoolProfile
    {
        return SchoolProfile::factory()->create(['name' => 'Extras School', 'is_active' => true]);
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

    private function detailsPayload(array $overrides = []): array
    {
        return array_merge([
            'siblings' => [
                [
                    'last_name' => 'Dela Cruz',
                    'first_name' => 'Maria',
                    'middle_name' => 'Reyes',
                    'extension_name' => null,
                    'grade_level' => 'Grade 5',
                ],
            ],
            'tuition_plan' => 'School Tuition Plan',
            'medical_history' => [
                'allergies' => 'Peanuts',
                'family_history' => ['asthma', 'diabetes'],
                'family_history_others' => 'None',
                'emergency_hospital' => 'baguio-general',
            ],
            'chinese_details' => [
                'grade_level' => 'Grade 7',
                'english_name' => 'Juan Dela Cruz',
                'chinese_name' => '林明',
                'father_chinese_name' => '林大',
                'mother_chinese_name' => '陈美',
            ],
            'agreement' => [
                'photo_consent' => true,
                'registration_consent' => true,
                'credentialing_consent' => true,
                'rules_consent' => true,
                'date_of_registration' => '2026-08-15',
                'initial_payment' => 10000,
            ],
        ], $overrides);
    }

    public function test_details_endpoint_stores_all_sections(): void
    {
        $enrollment = $this->pendingEnrollment();

        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/details", $this->detailsPayload())
            ->assertOk()
            ->assertJsonPath('success', true);

        $enrollment->refresh();

        $this->assertSame('School Tuition Plan', $enrollment->tuition_plan);
        $this->assertCount(1, $enrollment->siblings);
        $this->assertSame('Peanuts', $enrollment->medical_history['allergies']);
        $this->assertSame(['asthma', 'diabetes'], $enrollment->medical_history['family_history']);
        $this->assertSame('baguio-general', $enrollment->medical_history['emergency_hospital']);
        $this->assertSame('林明', $enrollment->chinese_details['chinese_name']);
        $this->assertTrue($enrollment->photo_consent);
        $this->assertTrue($enrollment->registration_consent);
        $this->assertTrue($enrollment->credentialing_consent);
        $this->assertTrue($enrollment->rules_consent);
        $this->assertSame('2026-08-15', $enrollment->date_of_registration->toDateString());
        $this->assertSame('10000.00', $enrollment->initial_payment);
    }

    public function test_details_endpoint_merges_partial_updates(): void
    {
        $enrollment = $this->pendingEnrollment();

        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/details", ['tuition_plan' => 'School Tuition Plan'])
            ->assertOk();

        $enrollment->refresh();
        $this->assertSame('School Tuition Plan', $enrollment->tuition_plan);
        $this->assertNull($enrollment->siblings);

        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/details", [
            'medical_history' => ['allergies' => 'None'],
        ])->assertOk();

        $enrollment->refresh();
        $this->assertSame('School Tuition Plan', $enrollment->tuition_plan);
        $this->assertSame('None', $enrollment->medical_history['allergies']);
    }

    public function test_details_rejects_unknown_family_history_condition(): void
    {
        $enrollment = $this->pendingEnrollment();

        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/details", [
            'medical_history' => ['family_history' => ['covid-19']],
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['medical_history.family_history.0']]);
    }

    public function test_details_rejects_sibling_without_names(): void
    {
        $enrollment = $this->pendingEnrollment();

        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/details", [
            'siblings' => [['grade_level' => 'Grade 5']],
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['siblings.0.first_name', 'siblings.0.last_name']]);
    }

    public function test_details_requires_consents_when_agreement_submitted(): void
    {
        $enrollment = $this->pendingEnrollment();

        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/details", [
            'agreement' => ['photo_consent' => true],
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['agreement.registration_consent', 'agreement.credentialing_consent', 'agreement.rules_consent']]);
    }

    public function test_signature_endpoint_captures_student_and_parent(): void
    {
        $enrollment = $this->pendingEnrollment();

        $this->postJson("/api/v1/public/enrollments/{$enrollment->id}/signature", [
            'role' => 'student',
            'signer_name' => 'Juan Dela Cruz',
            'signature_data' => 'data:image/png;base64,STUDENT_SIGNATURE',
        ])->assertOk();

        $this->postJson("/api/v1/public/enrollments/{$enrollment->id}/signature", [
            'role' => 'parent',
            'signer_name' => 'Pedro Dela Cruz',
            'signature_data' => 'data:image/png;base64,PARENT_SIGNATURE',
        ])->assertOk();

        $this->assertDatabaseHas('enrollment_signatures', [
            'enrollment_id' => $enrollment->id,
            'role' => 'student',
            'signer_name' => 'Juan Dela Cruz',
        ]);
        $this->assertDatabaseHas('enrollment_signatures', [
            'enrollment_id' => $enrollment->id,
            'role' => 'parent',
            'signer_name' => 'Pedro Dela Cruz',
        ]);
        $this->assertSame(2, $enrollment->signatures()->count());
    }

    public function test_signature_endpoint_overwrites_existing_role(): void
    {
        $enrollment = $this->pendingEnrollment();

        $this->postJson("/api/v1/public/enrollments/{$enrollment->id}/signature", [
            'role' => 'student',
            'signer_name' => 'Juan Dela Cruz',
            'signature_data' => 'data:image/png;base64,FIRST',
        ])->assertOk();

        $this->postJson("/api/v1/public/enrollments/{$enrollment->id}/signature", [
            'role' => 'student',
            'signer_name' => 'Juan Dela Cruz',
            'signature_data' => 'data:image/png;base64,SECOND',
        ])->assertOk();

        $this->assertSame(1, $enrollment->signatures()->where('role', 'student')->count());
        $this->assertSame('data:image/png;base64,SECOND', $enrollment->signatures()->where('role', 'student')->value('signature_data'));
    }

    public function test_signature_rejects_invalid_role(): void
    {
        $enrollment = $this->pendingEnrollment();

        $this->postJson("/api/v1/public/enrollments/{$enrollment->id}/signature", [
            'role' => 'registrar',
            'signer_name' => 'Juan Dela Cruz',
            'signature_data' => 'data:image/png;base64,ABC',
        ])->assertStatus(422);
    }

    public function test_show_returns_details_and_signatures(): void
    {
        $enrollment = $this->pendingEnrollment();

        $this->putJson("/api/v1/public/enrollments/{$enrollment->id}/details", $this->detailsPayload())->assertOk();

        $this->postJson("/api/v1/public/enrollments/{$enrollment->id}/signature", [
            'role' => 'student',
            'signer_name' => 'Juan Dela Cruz',
            'signature_data' => 'data:image/png;base64,SIG',
        ])->assertOk();

        $this->getJson("/api/v1/public/enrollments/{$enrollment->id}")
            ->assertOk()
            ->assertJsonPath('data.tuition_plan', 'School Tuition Plan')
            ->assertJsonPath('data.agreement.photo_consent', true)
            ->assertJsonCount(1, 'data.siblings')
            ->assertJsonCount(1, 'data.signatures')
            ->assertJsonPath('data.signatures.0.role', 'student');
    }
}