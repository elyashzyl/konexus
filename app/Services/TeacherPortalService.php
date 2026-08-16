<?php

namespace App\Services;

use App\Enums\GradeStatus;
use App\Models\AcademicClassStudent;
use App\Models\ClassSchedule;
use App\Models\GradeRecord;
use App\Models\SubjectOffering;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Repositories\Contracts\GradeRecordRepositoryInterface;
use App\Repositories\Contracts\SubjectOfferingRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Data assembly for the Teacher Portal.
 *
 * Part 8 – Portals. Every method is scoped to the teacher resolved from the
 * authenticated user, so a teacher only ever sees the sections, schedules,
 * students and grade records assigned to them.
 */
class TeacherPortalService
{
    public function __construct(
        private readonly AcademicContextService $context,
        private readonly SubjectOfferingRepositoryInterface $offerings,
        private readonly GradeRecordRepositoryInterface $gradeRecords,
    ) {}

    /**
     * The dashboard payload of a teacher.
     *
     * @return array<string, mixed>
     */
    public function dashboard(Teacher $teacher): array
    {
        $yearId = $this->context->currentAcademicYear()?->id;
        $termId = $this->context->currentAcademicTerm()?->id;

        $assignments = $this->assignments($teacher);
        $schedule = $this->schedule($teacher);
        $studentIds = $assignments->pluck('section_id')->unique()->flatMap(
            fn (int $sectionId) => $this->sectionStudentIds($sectionId, $yearId)
        )->unique()->values();

        return [
            'teacher' => [
                'id' => $teacher->id,
                'name' => $teacher->employee?->full_name,
                'employee_number' => $teacher->employee_number,
                'specialization' => $teacher->specialization,
                'department' => $teacher->department?->name,
                'advisory_section' => $teacher->advisoryClass?->name,
                'school' => $teacher->employee?->schoolProfile?->name,
                'campus' => $teacher->employee?->campuses->first()?->name,
            ],
            'academic_year' => $this->context->currentAcademicYear()?->name,
            'academic_term' => $this->context->currentAcademicTerm()?->name,
            'stats' => [
                'assignments' => $assignments->count(),
                'sections' => $assignments->pluck('section_id')->unique()->count(),
                'students' => $studentIds->count(),
                'schedules' => $schedule->count(),
            ],
            'modules' => ['attendance' => false, 'finance' => false, 'library' => false, 'clinic' => false],
        ];
    }

    /**
     * The teaching assignments of a teacher.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function assignments(Teacher $teacher): Collection
    {
        $yearId = $this->context->currentAcademicYear()?->id;
        $termId = $this->context->currentAcademicTerm()?->id;

        return TeacherAssignment::query()
            ->with(['section', 'subject', 'campus', 'gradeLevel', 'academicTerm'])
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->when($termId, fn ($q) => $q->where('academic_term_id', $termId))
            ->orderBy('section_id')
            ->get()
            ->map(fn (TeacherAssignment $assignment) => [
                'id' => $assignment->id,
                'section_id' => $assignment->section_id,
                'section' => $assignment->section?->name,
                'grade_level' => $assignment->gradeLevel?->name,
                'subject_id' => $assignment->subject_id,
                'subject' => $assignment->subject?->name,
                'subject_code' => $assignment->subject?->code,
                'campus' => $assignment->campus?->name,
                'term' => $assignment->academicTerm?->name,
                'units' => (float) $assignment->units,
            ]);
    }

    /**
     * The weekly schedule of a teacher.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function schedule(Teacher $teacher): Collection
    {
        $yearId = $this->context->currentAcademicYear()?->id;
        $termId = $this->context->currentAcademicTerm()?->id;

        return ClassSchedule::query()
            ->with(['subject', 'section', 'room'])
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->when($termId, fn ($q) => $q->where('academic_term_id', $termId))
            ->orderBy('day')
            ->orderBy('start_time')
            ->get()
            ->map(fn (ClassSchedule $schedule) => [
                'id' => $schedule->id,
                'subject' => $schedule->subject?->name,
                'subject_code' => $schedule->subject?->code,
                'section' => $schedule->section?->name,
                'room' => $schedule->room?->name,
                'day' => $schedule->day,
                'start_time' => $schedule->start_time?->format('H:i'),
                'end_time' => $schedule->end_time?->format('H:i'),
            ]);
    }

    /**
     * The advisory class of a teacher with its members.
     *
     * @return array<string, mixed>|null
     */
    public function advisoryClass(Teacher $teacher): ?array
    {
        $yearId = $this->context->currentAcademicYear()?->id;

        $class = AcademicClassStudent::query()
            ->with(['academicClass.section', 'student'])
            ->whereHas('academicClass', function ($q) use ($teacher, $yearId): void {
                $q->where('adviser_teacher_id', $teacher->id);
                if ($yearId !== null) {
                    $q->where('academic_year_id', $yearId);
                }
            })
            ->where('is_active', true)
            ->get();

        $group = $class->groupBy('academic_class_id')->first();

        return $group === null ? null : [
            'id' => $group->first()->academicClass->id,
            'name' => $group->first()->academicClass->display_name,
            'section' => $group->first()->academicClass->section?->name,
            'students' => $group->map(fn (AcademicClassStudent $member) => [
                'id' => $member->student_id,
                'name' => $member->student?->full_name,
                'student_number' => $member->student?->student_number,
                'lrn' => $member->student?->lrn,
                'gender' => $member->student?->gender,
            ])->values(),
        ];
    }

    /**
     * The roster of a section taught by the teacher, with any grade records
     * already captured for the teacher's offering of that section.
     *
     * @return array<string, mixed>
     */
    public function classRoster(Teacher $teacher, int $sectionId, ?int $subjectId = null): array
    {
        $yearId = $this->context->currentAcademicYear()?->id;
        $termId = $this->context->currentAcademicTerm()?->id;

        $students = \App\Models\Enrollment::query()
            ->with('student')
            ->where('section_id', $sectionId)
            ->whereIn('status', \App\Enums\EnrollmentStatus::activeStatuses())
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->when($termId, fn ($q) => $q->where('academic_term_id', $termId))
            ->get()
            ->map(fn ($enrollment) => $enrollment->student)
            ->filter()
            ->unique('id')
            ->values();

        $offering = $this->resolveOffering($teacher->id, $sectionId, $subjectId);

        $records = $offering !== null
            ? $this->gradeRecords->query()->where('subject_offering_id', $offering->id)->get()->keyBy('student_id')
            : collect();

        return [
            'section_id' => $sectionId,
            'subject_id' => $subjectId,
            'subject_offering_id' => $offering?->id,
            'subject' => $offering?->subject?->name ?? $subjectId,
            'offering_units' => $offering !== null ? (float) $offering->units : null,
            'items' => $students->map(fn ($student) => [
                'student_id' => $student->id,
                'name' => $student->full_name,
                'student_number' => $student->student_number,
                'lrn' => $student->lrn,
                'gender' => $student->gender,
                'grade_record_id' => $records->get($student->id)?->id,
                'final_grade' => $records->get($student->id)?->final_grade !== null
                    ? (float) $records->get($student->id)->final_grade
                    : null,
                'status' => $records->get($student->id)?->status ?? GradeStatus::DRAFT->value,
            ])->values(),
        ];
    }

    /**
     * The students taught by the teacher across all assignments.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function students(Teacher $teacher): Collection
    {
        $yearId = $this->context->currentAcademicYear()?->id;
        $termId = $this->context->currentAcademicTerm()?->id;

        $sectionIds = TeacherAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->when($termId, fn ($q) => $q->where('academic_term_id', $termId))
            ->pluck('section_id');

        return \App\Models\Enrollment::query()
            ->with('student')
            ->whereIn('section_id', $sectionIds)
            ->whereIn('status', \App\Enums\EnrollmentStatus::activeStatuses())
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->when($termId, fn ($q) => $q->where('academic_term_id', $termId))
            ->get()
            ->pluck('student')
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn ($student) => [
                'id' => $student->id,
                'name' => $student->full_name,
                'student_number' => $student->student_number,
                'lrn' => $student->lrn,
                'gender' => $student->gender,
                'section' => $student->activeEnrollment?->section?->name,
            ]);
    }

    /**
     * Resolve the offering the teacher teaches for a section.
     */
    protected function resolveOffering(int $teacherId, int $sectionId, ?int $subjectId): ?SubjectOffering
    {
        $query = $this->offerings->query()
            ->where('teacher_id', $teacherId)
            ->where('section_id', $sectionId);

        if ($subjectId !== null) {
            $query->where('subject_id', $subjectId);
        }

        return $query->first();
    }

    /**
     * The ids of the active students in a section.
     *
     * @return Collection<int, int>
     */
    protected function sectionStudentIds(int $sectionId, ?int $yearId): Collection
    {
        return \App\Models\Enrollment::query()
            ->where('section_id', $sectionId)
            ->whereIn('status', \App\Enums\EnrollmentStatus::activeStatuses())
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->pluck('student_id');
    }
}