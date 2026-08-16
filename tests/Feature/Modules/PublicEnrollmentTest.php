<?php

namespace Tests\Feature\Modules;

use App\Enums\EnrollmentStatus;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\SchoolProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PublicEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{school: SchoolProfile, campus: Campus, year: AcademicYear, level: GradeLevel}
     */
    private function enrollmentContext(array $schoolOverrides = [], array $campusOverrides = [], array $levelOverrides = []): array
    {
        $school = SchoolProfile::factory()->create(array_merge([
            'name' => 'Online Enrollment School',
            'is_active' => true,
        ], $schoolOverrides));

        $campus = Campus::factory()->create(array_merge([
            'school_profile_id' => $school->id,
            'name' => 'Main Campus',
            'is_active' => true,
        ], $campusOverrides));

        $year = AcademicYear::factory()->create(['school_profile_id' => $school->id]);

        $level = GradeLevel::factory()->create(array_merge([
            'school_profile_id' => $school->id,
            'campus_id' => $campus->id,
            'education_level' => 'junior-high',
            'is_active' => true,
        ], $levelOverrides));

        return compact('school', 'campus', 'year', 'level');
    }

    private function activeSchool(): SchoolProfile
    {
        return $this->enrollmentContext()['school'];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @param  array{school: SchoolProfile, campus: Campus, year: AcademicYear, level: GradeLevel}|null  $context
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
            'status' => 'continuing',
            'incoming_level' => $context['level']->name,
            'track' => 'chinese',
            'email' => 'applicant@example.com',
            'mobile_number' => '+63 900 000 0000',
        ], $overrides);
    }

    public function test_options_endpoint_returns_the_active_schools_options(): void
    {
        $context = $this->enrollmentContext();

        GradeLevel::factory()->create([
            'school_profile_id' => SchoolProfile::factory()->create(['is_active' => false])->id,
            'name' => 'Grade 12',
            'code' => '12',
            'short_name' => 'G12',
            'education_level' => 'senior-high',
            'sequence' => 12,
        ]);

        $this->getJson('/api/v1/public/enrollment/options?school_profile_id='.$context['school']->id.'&campus_id='.$context['campus']->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.school_id', $context['school']->id)
            ->assertJsonPath('data.campus_id', $context['campus']->id)
            ->assertJsonPath('data.academic_years.0.id', $context['year']->id)
            ->assertJsonPath('data.grade_levels.0.id', $context['level']->id)
            ->assertJsonCount(1, 'data.schools')
            ->assertJsonCount(1, 'data.campuses')
            ->assertJsonCount(1, 'data.grade_levels');
    }

    public function test_options_endpoint_filters_grade_levels_by_campus(): void
    {
        $context = $this->enrollmentContext();

        $otherCampus = Campus::factory()->create([
            'school_profile_id' => $context['school']->id,
            'name' => 'Annex Campus',
            'is_active' => true,
        ]);

        GradeLevel::factory()->create([
            'school_profile_id' => $context['school']->id,
            'campus_id' => $otherCampus->id,
            'name' => 'Grade 8',
            'code' => '8',
            'short_name' => 'G8',
            'education_level' => 'junior-high',
            'sequence' => 8,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/public/enrollment/options?school_profile_id='.$context['school']->id.'&campus_id='.$context['campus']->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.grade_levels')
            ->assertJsonPath('data.grade_levels.0.name', $context['level']->name);
    }

    public function test_options_endpoint_lists_multiple_active_schools(): void
    {
        $first = $this->enrollmentContext(['name' => 'Alpha School']);
        $second = $this->enrollmentContext(['name' => 'Beta School']);

        $this->getJson('/api/v1/public/enrollment/options')
            ->assertOk()
            ->assertJsonCount(2, 'data.schools')
            ->assertJsonPath('data.school_id', null)
            ->assertJsonPath('data.campus_id', null)
            ->assertJsonMissingPath('data.academic_years.0.id')
            ->assertJsonMissingPath('data.grade_levels.0.id');

        $this->getJson('/api/v1/public/enrollment/options?school_profile_id='.$second['school']->id.'&campus_id='.$second['campus']->id)
            ->assertOk()
            ->assertJsonPath('data.school_id', $second['school']->id)
            ->assertJsonPath('data.campus_id', $second['campus']->id)
            ->assertJsonPath('data.academic_years.0.id', $second['year']->id);
    }

    public function test_store_creates_a_pending_application(): void
    {
        $context = $this->enrollmentContext();

        $this->postJson('/api/v1/public/enrollments', $this->validPayload([], $context))
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('enrollments', [
            'school_profile_id' => $context['school']->id,
            'campus_id' => $context['campus']->id,
            'grade_level_id' => $context['level']->id,
            'department' => 'junior-high',
            'strand' => null,
            'track' => 'chinese',
            'incoming_level' => $context['level']->name,
            'email' => 'applicant@example.com',
            'mobile_number' => '+63 900 000 0000',
            'status' => 'pending',
            'enrollment_type' => 'continuing',
            'student_id' => null,
        ]);

        $enrollment = Enrollment::query()->first();
        $this->assertStringStartsWith('ENR-', (string) $enrollment->enrollment_number);
        $this->assertStringStartsWith('KXN-EN-', (string) $enrollment->reference_number);
        $this->assertNotNull($enrollment->application_submitted_at);
        $this->assertNotNull($enrollment->application_expires_at);
        $this->assertTrue($enrollment->application_expires_at->isSameDay(Carbon::now()->addDays(30)));
    }

    public function test_store_rejects_a_campus_from_another_school(): void
    {
        $context = $this->enrollmentContext();
        $other = $this->enrollmentContext(['name' => 'Other School']);

        $this->postJson('/api/v1/public/enrollments', $this->validPayload([
            'campus_id' => $other['campus']->id,
        ], $context))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['campus_id']]);
    }

    public function test_store_does_not_reuse_reference_number_of_a_soft_deleted_application(): void
    {
        $context = $this->enrollmentContext();

        $first = $this->postJson('/api/v1/public/enrollments', $this->validPayload([], $context))
            ->assertStatus(201)
            ->json('data');

        Enrollment::query()->findOrFail($first['id'])->delete();

        $second = $this->postJson('/api/v1/public/enrollments', $this->validPayload([], $context))
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

    public function test_store_rejects_an_unknown_track(): void
    {
        $context = $this->enrollmentContext();

        $this->postJson('/api/v1/public/enrollments', $this->validPayload(['track' => 'mathematics'], $context))
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['track']]);
    }

    public function test_strand_is_required_for_senior_high(): void
    {
        $context = $this->enrollmentContext();

        $this->postJson('/api/v1/public/enrollments', $this->validPayload(['department' => 'senior-high', 'strand' => null], $context))
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
