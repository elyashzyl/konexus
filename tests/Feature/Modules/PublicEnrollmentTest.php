<?php

namespace Tests\Feature\Modules;

use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\SchoolProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PublicEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private function activeSchool(): SchoolProfile
    {
        return SchoolProfile::factory()->create(['name' => 'Online Enrollment School', 'is_active' => true]);
    }

    private function validPayload(array $overrides = []): array
    {
        $year = AcademicYear::factory()->create();

        return array_merge([
            'academic_year_id' => $year->id,
            'department' => 'junior-high',
            'strand' => null,
            'status' => 'continuing',
            'incoming_level' => 'Grade 7',
            'track' => 'chinese',
            'email' => 'applicant@example.com',
            'mobile_number' => '+63 900 000 0000',
        ], $overrides);
    }

    public function test_options_endpoint_returns_the_active_schools_options(): void
    {
        $school = $this->activeSchool();

        $year = AcademicYear::factory()->create(['school_profile_id' => $school->id]);
        $level = GradeLevel::factory()->create(['school_profile_id' => $school->id]);
        GradeLevel::factory()->create([
            'school_profile_id' => SchoolProfile::factory()->create(['is_active' => false])->id,
            'name' => 'Grade 12',
            'code' => '12',
            'short_name' => 'G12',
            'education_level' => 'senior-high',
            'sequence' => 12,
        ]);

        $this->getJson('/api/v1/public/enrollment/options')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.school_id', $school->id)
            ->assertJsonPath('data.academic_years.0.id', $year->id)
            ->assertJsonPath('data.grade_levels.0.id', $level->id)
            ->assertJsonCount(1, 'data.grade_levels');
    }

    public function test_store_creates_a_pending_application(): void
    {
        $school = $this->activeSchool();

        $this->postJson('/api/v1/public/enrollments', $this->validPayload())
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('enrollments', [
            'school_profile_id' => $school->id,
            'department' => 'junior-high',
            'strand' => null,
            'track' => 'chinese',
            'incoming_level' => 'Grade 7',
            'email' => 'applicant@example.com',
            'mobile_number' => '+63 900 000 0000',
            'status' => 'pending',
            'enrollment_type' => 'continuing',
            'student_id' => null,
            'campus_id' => null,
            'grade_level_id' => null,
        ]);

        $enrollment = Enrollment::query()->first();
        $this->assertStringStartsWith('ENR-', (string) $enrollment->enrollment_number);
        $this->assertStringStartsWith('KXN-EN-', (string) $enrollment->reference_number);
        $this->assertNotNull($enrollment->application_submitted_at);
        $this->assertNotNull($enrollment->application_expires_at);
        $this->assertTrue($enrollment->application_expires_at->isSameDay(Carbon::now()->addDays(30)));
    }

    public function test_store_does_not_reuse_reference_number_of_a_soft_deleted_application(): void
    {
        $this->activeSchool();

        $first = $this->postJson('/api/v1/public/enrollments', $this->validPayload())
            ->assertStatus(201)
            ->json('data');

        Enrollment::query()->findOrFail($first['id'])->delete();

        $second = $this->postJson('/api/v1/public/enrollments', $this->validPayload())
            ->assertStatus(201)
            ->json('data');

        $this->assertNotSame($first['reference_number'], $second['reference_number']);
        $this->assertNotSame($first['id'], $second['id']);
    }

    public function test_store_validates_the_payload(): void
    {
        $this->postJson('/api/v1/public/enrollments', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['academic_year_id', 'department', 'status', 'incoming_level', 'track', 'email', 'mobile_number']]);
    }

    public function test_store_rejects_an_unknown_track(): void
    {
        $this->postJson('/api/v1/public/enrollments', $this->validPayload(['track' => 'mathematics']))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['track']]);
    }

    public function test_strand_is_required_for_senior_high(): void
    {
        $this->postJson('/api/v1/public/enrollments', $this->validPayload(['department' => 'senior-high', 'strand' => null]))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['strand']]);
    }

    public function test_purge_command_deletes_only_abandoned_applications(): void
    {
        $this->activeSchool();

        $abandoned = Enrollment::factory()->create([
            'status' => EnrollmentStatus::PENDING->value,
            'application_expires_at' => Carbon::now()->subDays(31),
        ]);

        $withinWindow = Enrollment::factory()->create([
            'status' => EnrollmentStatus::PENDING->value,
            'application_expires_at' => Carbon::now()->addDays(15),
        ]);

        $active = Enrollment::factory()->create([
            'status' => EnrollmentStatus::OFFICIALLY_ENROLLED->value,
            'application_expires_at' => Carbon::now()->subDays(90),
        ]);

        $this->artisan('enrollments:purge-abandoned')
            ->expectsOutputToContain('1')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('enrollments', ['id' => $abandoned->id]);
        $this->assertDatabaseHas('enrollments', ['id' => $withinWindow->id]);
        $this->assertDatabaseHas('enrollments', ['id' => $active->id]);
    }
}