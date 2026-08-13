<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Employee;
use App\Models\Enrollment;
use App\Models\GradeRecord;
use App\Models\Section;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * The Reports center.
 *
 * Part 8 – Reports. Generates the standard operational reports of the system
 * as CSV or PDF. Every report resolves its rows through the same services the
 * modules use, so the output always respects the current academic context.
 */
class ReportService
{
    public function __construct(
        private readonly AcademicContextService $context,
        private readonly PortalIdentityService $identities,
    ) {}

    /**
     * The catalog of available reports.
     *
     * @return array<string, mixed>
     */
    public function catalog(): array
    {
        $reports = [
            ['key' => 'students', 'label' => 'Student Roster', 'group' => 'People', 'columns' => ['Student Number', 'LRN', 'Full Name', 'Gender', 'Birth Date', 'Status']],
            ['key' => 'enrollments', 'label' => 'Enrollment Register', 'group' => 'Enrollment', 'columns' => ['Enrollment #', 'Student', 'Academic Year', 'Grade Level', 'Section', 'Campus', 'Status', 'Date Enrolled']],
            ['key' => 'class-roster', 'label' => 'Class Roster', 'group' => 'Enrollment', 'columns' => ['Student Number', 'LRN', 'Full Name', 'Gender', 'Status']],
            ['key' => 'grade-records', 'label' => 'Grade Records Summary', 'group' => 'Academics', 'columns' => ['Student', 'Subject', 'Section', 'Final Grade', 'Remarks', 'Status']],
            ['key' => 'sections', 'label' => 'Sections Directory', 'group' => 'Academics', 'columns' => ['Section', 'Code', 'Grade Level', 'Campus', 'Adviser']],
            ['key' => 'subjects', 'label' => 'Subject Catalog', 'group' => 'Academics', 'columns' => ['Code', 'Name', 'Department']],
            ['key' => 'teachers', 'label' => 'Teaching Staff', 'group' => 'People', 'columns' => ['Employee #', 'Full Name', 'Department', 'Specialization']],
            ['key' => 'employees', 'label' => 'Employee Directory', 'group' => 'People', 'columns' => ['Employee #', 'Full Name', 'Email', 'Status']],
            ['key' => 'announcements', 'label' => 'Announcement Log', 'group' => 'Communications', 'columns' => ['Title', 'Category', 'Priority', 'Status', 'Published At']],
        ];

        return [
            'items' => $reports,
            'context' => [
                'academic_year_id' => $this->context->currentAcademicYear()?->id,
                'academic_year' => $this->context->currentAcademicYear()?->name,
                'academic_term_id' => $this->context->currentAcademicTerm()?->id,
                'academic_term' => $this->context->currentAcademicTerm()?->name,
            ],
        ];
    }

    /**
     * Generate a report and return the CSV/PDF binary content.
     *
     * @param  array<string, mixed>  $filters
     */
    public function generate(string $key, string $format, array $filters): \Symfony\Component\HttpFoundation\Response
    {
        [$title, $headers, $rows] = $this->rows($key, $filters);

        return $format === 'pdf'
            ? $this->asPdf($title, $headers, $rows)
            : $this->asCsv($title, $headers, $rows);
    }

    /**
     * Resolve the title, headers and rows of a report.
     *
     * @param  array<string, mixed>  $filters
     * @return array{string, list<string>, list<array<int, mixed>>}
     */
    protected function rows(string $key, array $filters): array
    {
        $title = Str::headline($key);
        $yearId = $filters['academic_year_id'] ?? $this->context->currentAcademicYear()?->id;
        $termId = $filters['academic_term_id'] ?? $this->context->currentAcademicTerm()?->id;

        return match ($key) {
            'students' => $this->studentRows($title),
            'enrollments' => $this->enrollmentRows($title, $yearId),
            'class-roster' => $this->classRosterRows($title, $filters['section_id'] ?? null),
            'grade-records' => $this->gradeRecordRows($title, $yearId, $termId),
            'sections' => $this->sectionRows($title),
            'subjects' => $this->subjectRows($title),
            'teachers' => $this->teacherRows($title),
            'employees' => $this->employeeRows($title),
            'announcements' => $this->announcementRows($title),
            default => abort(422, "Unknown report [{$key}]."),
        };
    }

    /**
     * @return array{string, list<string>, list<array<int, mixed>>}
     */
    protected function studentRows(string $title): array
    {
        $rows = Student::query()->orderBy('last_name')->get()->map(fn (Student $s) => [
            $s->student_number, $s->lrn, $s->full_name, $s->gender, $s->birth_date?->toDateString(), $s->status,
        ])->values();

        return [$title, ['Student Number', 'LRN', 'Full Name', 'Gender', 'Birth Date', 'Status'], $rows->toArray()];
    }

    /**
     * @return array{string, list<string>, list<array<int, mixed>>}
     */
    protected function enrollmentRows(string $title, ?int $yearId): array
    {
        $query = Enrollment::query()->with(['student', 'academicYear', 'gradeLevel', 'section', 'campus'])
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->orderByDesc('created_at');

        $rows = $query->get()->map(fn (Enrollment $e) => [
            $e->enrollment_number, $e->student?->full_name, $e->academicYear?->name, $e->gradeLevel?->name,
            $e->section?->name, $e->campus?->name, $e->display_status_label, $e->date_enrolled?->toDateString(),
        ])->values();

        return [$title, ['Enrollment #', 'Student', 'Academic Year', 'Grade Level', 'Section', 'Campus', 'Status', 'Date Enrolled'], $rows->toArray()];
    }

    /**
     * @return array{string, list<string>, list<array<int, mixed>>}
     */
    protected function classRosterRows(string $title, ?int $sectionId): array
    {
        $section = $sectionId ? Section::query()->find($sectionId) : null;

        $students = Student::query()
            ->whereHas('activeEnrollment', fn ($q) => $q->when($sectionId, fn ($q) => $q->where('section_id', $sectionId)))
            ->orderBy('last_name')
            ->get();

        $rows = $students->map(fn (Student $s) => [
            $s->student_number, $s->lrn, $s->full_name, $s->gender, $s->status,
        ])->values();

        return [$section ? 'Class Roster - '.$section->name : 'Class Roster', ['Student Number', 'LRN', 'Full Name', 'Gender', 'Status'], $rows->toArray()];
    }

    /**
     * @return array{string, list<string>, list<array<int, mixed>>}
     */
    protected function gradeRecordRows(string $title, ?int $yearId, ?int $termId): array
    {
        $rows = GradeRecord::query()
            ->with(['student', 'subject', 'section'])
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->when($termId, fn ($q) => $q->where('academic_term_id', $termId))
            ->whereNotNull('final_grade')
            ->orderBy('student_id')
            ->get()
            ->map(fn (GradeRecord $g) => [
                $g->student?->full_name, $g->subject?->name, $g->section?->name,
                $g->final_grade, $g->remarks, $g->status,
            ])->values();

        return [$title, ['Student', 'Subject', 'Section', 'Final Grade', 'Remarks', 'Status'], $rows->toArray()];
    }

    /**
     * @return array{string, list<string>, list<array<int, mixed>>}
     */
    protected function sectionRows(string $title): array
    {
        $rows = Section::query()->with(['gradeLevel', 'campus', 'adviser.employee'])->orderBy('name')->get()->map(fn (Section $s) => [
            $s->name, $s->code, $s->gradeLevel?->name, $s->campus?->name, $s->adviser?->employee?->full_name,
        ])->values();

        return [$title, ['Section', 'Code', 'Grade Level', 'Campus', 'Adviser'], $rows->toArray()];
    }

    /**
     * @return array{string, list<string>, list<array<int, mixed>>}
     */
    protected function subjectRows(string $title): array
    {
        $rows = \App\Models\Subject::query()->with('department')->orderBy('code')->get()->map(fn ($s) => [
            $s->code, $s->name, $s->department?->name,
        ])->values();

        return [$title, ['Code', 'Name', 'Department'], $rows->toArray()];
    }

    /**
     * @return array{string, list<string>, list<array<int, mixed>>}
     */
    protected function teacherRows(string $title): array
    {
        $rows = Teacher::query()->with(['employee', 'department'])->orderBy('id')->get()->map(fn (Teacher $t) => [
            $t->employee_number, $t->employee?->full_name, $t->department?->name, $t->specialization,
        ])->values();

        return [$title, ['Employee #', 'Full Name', 'Department', 'Specialization'], $rows->toArray()];
    }

    /**
     * @return array{string, list<string>, list<array<int, mixed>>}
     */
    protected function employeeRows(string $title): array
    {
        $rows = Employee::query()->orderBy('last_name')->get()->map(fn (Employee $e) => [
            $e->employee_number, $e->full_name, $e->email, $e->employment_status ?? $e->status,
        ])->values();

        return [$title, ['Employee #', 'Full Name', 'Email', 'Status'], $rows->toArray()];
    }

    /**
     * @return array{string, list<string>, list<array<int, mixed>>}
     */
    protected function announcementRows(string $title): array
    {
        $rows = \App\Models\Announcement::query()->orderByDesc('created_at')->get()->map(fn ($a) => [
            $a->title, $a->category, $a->priority, $a->status, $a->published_at?->toDateTimeString(),
        ])->values();

        return [$title, ['Title', 'Category', 'Priority', 'Status', 'Published At'], $rows->toArray()];
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<int, mixed>>  $rows
     */
    protected function asCsv(string $title, array $headers, array $rows): \Symfony\Component\HttpFoundation\Response
    {
        $fileName = Str::slug($title).'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel.
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<int, mixed>>  $rows
     */
    protected function asPdf(string $title, array $headers, array $rows): \Symfony\Component\HttpFoundation\Response
    {
        $html = view('pdf.report', compact('title', 'headers', 'rows'))->render();

        return Pdf::loadHTML($html)
            ->setPaper('a4', 'landscape')
            ->download(Str::slug($title).'-'.now()->format('Ymd-His').'.pdf');
    }
}