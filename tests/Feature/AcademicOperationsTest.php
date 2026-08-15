<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Enums\RoleEnum;
use App\Models\AcademicClass;
use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\CurriculumEntry;
use App\Models\CurriculumProgram;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\GradeRecord;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentSubjectEnrollment;
use App\Models\Subject;
use App\Models\SubjectOffering;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_official_enrollment_materializes_subjects_and_supports_attendance_grading_and_promotion(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SUPER_ADMINISTRATOR->roleName());
        $this->actingAs($admin, 'sanctum');

        $year = AcademicYear::factory()->create(['is_active' => true]);
        $grade = GradeLevel::factory()->create(['name' => 'Grade 7', 'code' => '7', 'sequence' => 7, 'is_active' => true]);
        $section = Section::factory()->create(['grade_level_id' => $grade->id, 'is_active' => true]);
        $campus = Campus::factory()->create(['is_active' => true]);
        $class = AcademicClass::query()->create(['academic_year_id' => $year->id, 'campus_id' => $campus->id, 'grade_level_id' => $grade->id, 'section_id' => $section->id, 'status' => 'active', 'is_active' => true]);
        $student = Student::factory()->create();
        $subject = Subject::factory()->create(['grade_level_id' => $grade->id]);
        $program = CurriculumProgram::query()->create(['academic_year_id' => $year->id, 'name' => 'MATATAG Grade 7', 'code' => 'MATATAG-G7', 'framework' => 'matatag', 'calendar_type' => 'quarterly', 'grade_level_ids' => [$grade->id], 'compliance_status' => 'deped-aligned', 'status' => 'active', 'is_active' => true]);
        $period = AcademicPeriod::query()->create(['curriculum_program_id' => $program->id, 'name' => '1st Quarter', 'code' => 'Q1', 'sequence' => 1, 'start_date' => '2026-06-01', 'end_date' => '2026-08-31', 'status' => 'open', 'is_active' => true]);
        $entry = CurriculumEntry::query()->create(['academic_year_id' => $year->id, 'curriculum_program_id' => $program->id, 'grade_level_id' => $grade->id, 'subject_id' => $subject->id, 'subject_type' => 'core', 'units' => 1, 'weekly_minutes' => 240, 'assessment_policy' => ['written-work' => 1], 'is_required' => true, 'status' => 'active', 'is_active' => true]);
        $offering = SubjectOffering::query()->create(['academic_year_id' => $year->id, 'campus_id' => $campus->id, 'grade_level_id' => $grade->id, 'section_id' => $section->id, 'subject_id' => $subject->id, 'curriculum_program_id' => $program->id, 'curriculum_entry_id' => $entry->id, 'units' => 1, 'status' => 'active', 'is_active' => true]);
        $enrollment = Enrollment::factory()->create(['student_id' => $student->id, 'academic_year_id' => $year->id, 'campus_id' => $campus->id, 'grade_level_id' => $grade->id, 'section_id' => $section->id, 'curriculum_program_id' => $program->id, 'status' => EnrollmentStatus::OFFICIALLY_ENROLLED->value]);

        $this->postJson("/api/v1/academic-operations/enrollments/{$enrollment->id}/materialize")
            ->assertOk()
            ->assertJsonPath('data.subject_enrollments_created', 1);

        $sessionId = $this->postJson('/api/v1/academic-operations/attendance-sessions', ['academic_class_id' => $class->id, 'attendance_date' => '2026-06-02'])
            ->assertCreated()
            ->json('data.id');
        $this->putJson("/api/v1/academic-operations/attendance-sessions/{$sessionId}/records", ['records' => [['student_id' => $student->id, 'status' => 'present']]])->assertOk();
        $this->postJson("/api/v1/academic-operations/attendance-sessions/{$sessionId}/submit")->assertOk();

        $assessmentId = $this->postJson('/api/v1/academic-operations/assessments', ['subject_offering_id' => $offering->id, 'academic_period_id' => $period->id, 'component' => 'written-work', 'title' => 'Quiz 1', 'max_score' => 100])
            ->assertCreated()
            ->json('data.id');
        $subjectEnrollmentId = StudentSubjectEnrollment::query()->where('student_id', $student->id)->value('id');
        $this->putJson("/api/v1/academic-operations/assessments/{$assessmentId}/scores", ['scores' => [['student_subject_enrollment_id' => $subjectEnrollmentId, 'score' => 80]]])->assertOk();

        GradeRecord::query()->update(['status' => 'published']);
        $this->postJson("/api/v1/academic-operations/enrollments/{$enrollment->id}/promotion")
            ->assertOk()
            ->assertJsonPath('data.decision', 'promoted');
        $this->getJson("/api/v1/academic-operations/enrollments/{$enrollment->id}/report-card")
            ->assertOk()
            ->assertJsonPath('data.label', 'System-generated internal report card — not an official DepEd LIS form.');
    }
}
