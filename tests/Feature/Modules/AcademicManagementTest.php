<?php

namespace Tests\Feature\Modules;

use App\Enums\EnrollmentStatus;
use App\Enums\RoleEnum;
use App\Models\AcademicClass;
use App\Models\AcademicClassStudent;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\ClassSchedule;
use App\Models\CurriculumEntry;
use App\Models\Employee;
use App\Models\Enrollment;
use App\Models\GradeScale;
use App\Models\GradeScaleEntry;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectOffering;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicManagementTest extends TestCase
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

    private function context(): array
    {
        $year = AcademicYear::factory()->create(['calendar_type' => 'custom']);
        $grade = GradeLevel::factory()->create();
        $section = Section::factory()->create(['grade_level_id' => $grade->id]);
        $campus = Campus::factory()->create();
        $subject = Subject::factory()->create(['grade_level_id' => $grade->id]);
        $employee = Employee::factory()->create(['employment_type' => 'teaching']);
        $teacher = Teacher::factory()->create(['employee_id' => $employee->id]);

        return compact('year', 'grade', 'section', 'subject', 'teacher', 'campus');
    }

    public function test_academic_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/curriculum')->assertStatus(401);
        $this->getJson('/api/v1/subject-offerings')->assertStatus(401);
        $this->getJson('/api/v1/academic-classes')->assertStatus(401);
        $this->getJson('/api/v1/schedules')->assertStatus(401);
        $this->getJson('/api/v1/grade-records')->assertStatus(401);
        $this->getJson('/api/v1/academic/dashboard')->assertStatus(401);
    }

    public function test_curriculum_entry_crud_and_duplicate_prevention(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $context = $this->context();

        $payload = [
            'academic_year_id' => $context['year']->id,
            'grade_level_id' => $context['grade']->id,
            'subject_id' => $context['subject']->id,
            'subject_type' => 'core',
            'units' => 3,
            'is_required' => true,
            'display_order' => 1,
        ];

        $store = $this->postJson('/api/v1/curriculum', $payload)
            ->assertCreated()
            ->assertJsonPath('success', true);

        $id = $store->json('data.id');
        $this->assertDatabaseHas('curriculum_entries', ['id' => $id, 'units' => 3]);

        $this->getJson("/api/v1/curriculum/{$id}")->assertOk();

        $this->putJson("/api/v1/curriculum/{$id}", array_merge($payload, ['units' => 4]))
            ->assertOk()
            ->assertJsonPath('data.units', 4);

        $this->deleteJson("/api/v1/curriculum/{$id}")->assertOk();
        $this->assertSoftDeleted('curriculum_entries', ['id' => $id]);
        $this->postJson("/api/v1/curriculum/{$id}/restore")->assertOk();

        $this->postJson('/api/v1/curriculum', $payload)
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_subject_offering_creates_a_teacher_assignment_mirror(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $context = $this->context();

        $this->postJson('/api/v1/subject-offerings', [
            'academic_year_id' => $context['year']->id,
            'grade_level_id' => $context['grade']->id,
            'section_id' => $context['section']->id,
            'subject_id' => $context['subject']->id,
            'teacher_id' => $context['teacher']->id,
            'campus_id' => $context['campus']->id,
            'units' => 3,
        ])->assertCreated();

        $this->assertDatabaseHas('teacher_assignments', [
            'subject_id' => $context['subject']->id,
            'section_id' => $context['section']->id,
            'teacher_id' => $context['teacher']->id,
            'units' => 3,
        ]);

        $this->getJson('/api/v1/teacher-assignments?filter[teacher_id]='.$context['teacher']->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.items');

        $this->getJson('/api/v1/teacher-assignments/load?filter[academic_year_id]='.$context['year']->id)
            ->assertOk()
            ->assertJsonStructure(['data' => [['teacher_id', 'units', 'assignments']]]);
    }

    public function test_academic_class_members_can_be_managed_and_synced(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $context = $this->context();

        $class = AcademicClass::factory()->create([
            'academic_year_id' => $context['year']->id,
            'campus_id' => $context['campus']->id,
            'grade_level_id' => $context['grade']->id,
            'section_id' => $context['section']->id,
            'adviser_teacher_id' => $context['teacher']->id,
        ]);

        $student = Student::factory()->create();

        $this->postJson("/api/v1/academic-classes/{$class->id}/members", [
            'student_id' => $student->id,
        ])->assertCreated()
            ->assertJsonPath('data.action', 'added');

        $this->getJson("/api/v1/academic-classes/{$class->id}/members")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // Duplicate assignment is rejected while the student is active.
        $this->postJson("/api/v1/academic-classes/{$class->id}/members", [
            'student_id' => $student->id,
        ])->assertStatus(409);

        $this->deleteJson("/api/v1/academic-classes/{$class->id}/members/{$student->id}")
            ->assertOk();

        $this->assertDatabaseHas('academic_class_students', [
            'academic_class_id' => $class->id,
            'student_id' => $student->id,
            'is_active' => false,
        ]);
    }

    public function test_class_roster_syncs_from_official_enrollments(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $context = $this->context();

        $class = AcademicClass::factory()->create([
            'academic_year_id' => $context['year']->id,
            'campus_id' => $context['campus']->id,
            'grade_level_id' => $context['grade']->id,
            'section_id' => $context['section']->id,
            'adviser_teacher_id' => $context['teacher']->id,
        ]);

        $student = Student::factory()->create();
        Enrollment::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $context['year']->id,
            'campus_id' => $context['campus']->id,
            'grade_level_id' => $context['grade']->id,
            'section_id' => $context['section']->id,
            'status' => EnrollmentStatus::OFFICIALLY_ENROLLED->value,
        ]);

        $this->postJson("/api/v1/academic-classes/{$class->id}/members/sync")
            ->assertOk()
            ->assertJsonPath('data.added', 1);

        $this->assertDatabaseHas('academic_class_students', [
            'academic_class_id' => $class->id,
            'student_id' => $student->id,
            'source' => AcademicClassStudent::SOURCE_ENROLLMENT,
            'is_active' => true,
        ]);
    }

    public function test_schedule_conflicts_are_detected_and_can_be_overridden(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $context = $this->context();

        $base = [
            'academic_year_id' => $context['year']->id,
            'grade_level_id' => $context['grade']->id,
            'section_id' => $context['section']->id,
            'subject_id' => $context['subject']->id,
            'teacher_id' => $context['teacher']->id,
            'day' => 'monday',
            'start_time' => '08:00',
            'end_time' => '09:00',
        ];

        $this->postJson('/api/v1/schedules', $base)->assertCreated();

        $secondSubject = Subject::factory()->create(['grade_level_id' => $context['grade']->id]);

        $conflicting = $this->postJson('/api/v1/schedules', array_merge($base, ['subject_id' => $secondSubject->id]))
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertNotNull($conflicting->json('errors.conflicts'));

        $this->postJson('/api/v1/schedules', array_merge($base, [
            'subject_id' => Subject::factory()->create(['grade_level_id' => $context['grade']->id])->id,
            'conflict_override' => true,
            'conflict_reason' => 'Approved by registrar',
        ]))->assertCreated();

        $this->assertDatabaseCount('class_schedules', 2);
    }

    public function test_grade_scale_and_resolution(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $scale = GradeScale::factory()->create([
            'min_grade' => 60,
            'max_grade' => 100,
            'minimum_passing_grade' => 75,
            'decimal_precision' => 2,
            'is_default' => true,
        ]);

        GradeScaleEntry::factory()->create([
            'grade_scale_id' => $scale->id,
            'label' => 'Passed',
            'min_grade' => 75,
            'max_grade' => 100,
            'is_passing' => true,
        ]);

        $this->getJson('/api/v1/grade-scales')
            ->assertOk()
            ->assertJsonCount(1, 'data.items');

        $this->getJson("/api/v1/grade-scales/{$scale->id}/entries")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->postJson("/api/v1/grade-scales/{$scale->id}/entries", [
            'label' => 'Failed',
            'min_grade' => 60,
            'max_grade' => 74,
            'is_passing' => false,
        ])->assertCreated();

        $this->getJson('/api/v1/grade-scales/resolve?raw_grade=88.4')
            ->assertOk()
            ->assertJsonPath('data.is_passing', true)
            ->assertJsonPath('data.label', 'Passed');
    }

    public function test_grade_workflow_and_correction(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        GradeScale::factory()->create([
            'min_grade' => 60,
            'max_grade' => 100,
            'minimum_passing_grade' => 75,
            'is_default' => true,
        ]);

        $context = $this->context();
        $offering = SubjectOffering::factory()->create([
            'academic_year_id' => $context['year']->id,
            'grade_level_id' => $context['grade']->id,
            'section_id' => $context['section']->id,
            'subject_id' => $context['subject']->id,
            'teacher_id' => $context['teacher']->id,
            'units' => 3,
        ]);
        $student = Student::factory()->create();

        $store = $this->postJson('/api/v1/grade-records', [
            'student_id' => $student->id,
            'subject_offering_id' => $offering->id,
            'raw_grade' => 88.456,
        ])->assertCreated();

        $id = $store->json('data.id');
        $this->assertNotNull($store->json('data.final_grade'));

        // Walk the approval workflow.
        $this->postJson("/api/v1/grade-records/{$id}/transition", ['status' => 'submitted'])->assertOk();
        $this->postJson("/api/v1/grade-records/{$id}/transition", ['status' => 'for-review'])->assertOk();
        $this->postJson("/api/v1/grade-records/{$id}/transition", ['status' => 'approved'])->assertOk();
        $this->postJson("/api/v1/grade-records/{$id}/transition", ['status' => 'published'])->assertOk();

        $this->getJson("/api/v1/grade-records/{$id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'published');

        // Locked record cannot be edited directly.
        $this->putJson("/api/v1/grade-records/{$id}", ['raw_grade' => 90])
            ->assertStatus(422);

        // Request a correction and apply it.
        $correction = $this->postJson('/api/v1/grade-corrections', [
            'grade_record_id' => $id,
            'proposed_grade' => 92,
            'reason' => 'Clerical error in final grade.',
        ])->assertCreated();

        $correctionId = $correction->json('data.id');

        $this->postJson("/api/v1/grade-corrections/{$correctionId}/approve", [
            'approval_remarks' => 'Verified against grade sheet.',
        ])->assertOk();

        $this->assertDatabaseHas('grade_records', [
            'id' => $id,
            'final_grade' => 92,
            'status' => 'corrected',
        ]);
    }

    public function test_academic_dashboard_returns_aggregates(): void
    {
        $this->actingAs($this->superAdmin(), 'sanctum');

        $context = $this->context();
        SubjectOffering::factory()->create([
            'academic_year_id' => $context['year']->id,
            'grade_level_id' => $context['grade']->id,
            'section_id' => $context['section']->id,
            'subject_id' => $context['subject']->id,
            'teacher_id' => $context['teacher']->id,
        ]);

        $this->getJson('/api/v1/academic/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'totals' => [
                        'active_sections',
                        'subjects',
                        'subjects_without_teacher',
                        'teachers',
                        'enrolled_students',
                        'students_without_class',
                        'classes_today',
                        'upcoming_classes',
                    ],
                    'by_grade_level',
                    'by_section',
                    'by_department',
                ],
            ]);
    }

    public function test_plain_user_cannot_access_academic_modules(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/curriculum')
            ->assertStatus(403);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/grade-records')
            ->assertStatus(403);
    }
}