<?php

namespace App\Services;

use App\Models\AcademicClassStudent;
use App\Models\Announcement;
use App\Models\AttendanceRecord;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * Shared data assembly for the Parent and Student portals.
 *
 * Part 8 – Portals. Every method only reads records tied to the given student
 * (or the student's own section), so callers can safely expose the result to
 * parents and students. Modules that are not part of the current installation
 * (attendance, finance, library, clinic) are reported as unavailable.
 */
class PortalDataService
{
    public function __construct(
        private readonly AcademicContextService $context,
        private readonly GradeRecordService $grades,
        private readonly PortalIdentityService $identities,
    ) {}

    /**
     * The safe, summarized profile of a child/student.
     *
     * @return array<string, mixed>
     */
    public function childSummary(Student $student): array
    {
        $enrollment = $this->identities->activeEnrollment($student);

        return [
            'id' => $student->id,
            'name' => $student->full_name,
            'first_name' => $student->first_name,
            'middle_name' => $student->middle_name,
            'last_name' => $student->last_name,
            'extension_name' => $student->extension_name,
            'student_number' => $student->student_number,
            'lrn' => $student->lrn,
            'gender' => $student->gender,
            'birth_date' => $student->birth_date?->toDateString(),
            'age' => $student->age,
            'profile_picture_url' => $student->profile_picture_path ? url('storage/'.$student->profile_picture_path) : null,
            'status' => $student->status,
            'enrollment_status' => $enrollment?->status,
            'enrollment_status_label' => $enrollment?->display_status_label,
            'academic_year' => $enrollment?->academicYear?->name,
            'academic_term' => $enrollment?->academicTerm?->name,
            'grade_level_id' => $enrollment?->grade_level_id,
            'grade_level' => $enrollment?->gradeLevel?->name,
            'section_id' => $enrollment?->section_id,
            'section' => $enrollment?->section?->name,
            'campus_id' => $enrollment?->campus_id,
            'campus' => $enrollment?->campus?->name,
            'adviser' => $this->adviserName($student),
            'academic_summary' => $this->academicSummary($student),
            'attendance_summary' => $this->attendanceSummary($student),
            'modules' => $this->moduleAvailability(),
        ];
    }

    /**
     * The attendance / finance / library availability flags.
     *
     * @return array<string, bool>
     */
    public function moduleAvailability(): array
    {
        return [
            'attendance' => true,
            'finance' => false,
            'library' => false,
            'clinic' => false,
        ];
    }

    /**
     * The adviser of the student's advisory class in the current context.
     */
    protected function adviserName(Student $student): ?string
    {
        $yearId = $this->context->currentAcademicYear()?->id;

        $membership = AcademicClassStudent::query()
            ->with('academicClass.adviser.employee')
            ->where('student_id', $student->id)
            ->where('is_active', true)
            ->when($yearId, fn ($q) => $q->whereHas('academicClass', fn ($q) => $q->where('academic_year_id', $yearId)))
            ->first();

        $adviser = $membership?->academicClass?->adviser;

        return $adviser?->employee?->full_name;
    }

    /**
     * The published academic summary of a student (report card foundation).
     *
     * @return array<string, mixed>
     */
    public function academicSummary(Student $student): array
    {
        $yearId = $this->context->currentAcademicYear()?->id;
        $report = $this->grades->studentReport($student->id, $yearId, $this->context->currentAcademicTerm()?->id);

        return [
            'records' => $report['records'],
            'total_units' => $report['total_units'],
            'published_records' => $report['published_records'],
            'general_average' => $report['general_average'],
        ];
    }

    /** @return array<string, int> */
    public function attendanceSummary(Student $student): array
    {
        return AttendanceRecord::query()
            ->where('student_id', $student->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn (int $total): int => $total)
            ->all();
    }

    /**
     * The class schedule of the student's current section.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function schedule(Student $student): Collection
    {
        $enrollment = $this->identities->activeEnrollment($student);

        if ($enrollment?->section_id === null) {
            return collect();
        }

        $yearId = $this->context->currentAcademicYear()?->id;
        $termId = $this->context->currentAcademicTerm()?->id;

        return ClassSchedule::query()
            ->with(['subject', 'teacher.employee', 'room'])
            ->where('section_id', $enrollment->section_id)
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
                'teacher' => $schedule->teacher?->employee?->full_name,
                'room' => $schedule->room?->name,
                'day' => $schedule->day,
                'start_time' => $schedule->start_time?->format('H:i'),
                'end_time' => $schedule->end_time?->format('H:i'),
            ]);
    }

    /**
     * The enrollment history of a student.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function enrollmentHistory(Student $student): Collection
    {
        return Enrollment::query()
            ->with(['academicYear', 'academicTerm', 'gradeLevel', 'section', 'campus'])
            ->where('student_id', $student->id)
            ->orderByDesc('academic_year_id')
            ->get()
            ->map(fn (Enrollment $enrollment) => [
                'id' => $enrollment->id,
                'enrollment_number' => $enrollment->enrollment_number,
                'reference_number' => $enrollment->reference_number,
                'status' => $enrollment->status,
                'status_label' => $enrollment->display_status_label,
                'academic_year' => $enrollment->academicYear?->name,
                'academic_term' => $enrollment->academicTerm?->name,
                'grade_level' => $enrollment->gradeLevel?->name,
                'section' => $enrollment->section?->name,
                'campus' => $enrollment->campus?->name,
                'enrollment_date' => $enrollment->enrollment_date?->toDateString(),
                'date_enrolled' => $enrollment->date_enrolled?->toDateString(),
            ]);
    }

    /**
     * The documents of a student (profile documents + enrollment documents).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function documents(Student $student): Collection
    {
        $profileDocs = $student->documents()->get();

        $enrollmentDocs = Enrollment::query()
            ->where('student_id', $student->id)
            ->with('documents')
            ->get()
            ->pluck('documents')
            ->flatten();

        return collect()
            ->concat($profileDocs)
            ->concat($enrollmentDocs)
            ->map(fn ($document) => [
                'id' => $document->id,
                'name' => $document->name ?? $document->file_name ?? 'Document',
                'document_type' => $document->document_type ?? 'enrollment',
                'status' => $document->status,
                'created_at' => $document->created_at?->toISOString(),
                'url' => $document->file_path ? url('storage/'.$document->file_path) : null,
            ])
            ->values();
    }

    /**
     * The announcements targeted at the audience signature of a user.
     *
     * @return Collection<int, Announcement>
     */
    public function targetedAnnouncements(array $signature): Collection
    {
        return Announcement::query()
            ->with('author')
            ->where('published', true)
            ->orderByDesc('published_at')
            ->limit(10)
            ->get()
            ->filter(fn (Announcement $announcement) => $announcement->isVisible() && $announcement->matchesAudience($signature))
            ->values();
    }
}
