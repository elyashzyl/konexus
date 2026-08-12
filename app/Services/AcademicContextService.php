<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\ScheduleDay;
use App\Models\AcademicClassStudent;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\Subject;
use App\Models\SubjectOffering;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use App\Repositories\Contracts\AcademicSettingRepositoryInterface;
use App\Repositories\Contracts\ClassScheduleRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Resolves the current academic context (year/term/campus) and aggregates the
 * Academic Dashboard statistics. Academic periods are never hardcoded; they
 * come from the configured Academic Structure (Parts 1–5).
 */
class AcademicContextService
{
    public function __construct(
        private readonly AcademicSettingRepositoryInterface $settings,
        private readonly ClassScheduleRepositoryInterface $scheduleRepo,
    ) {}

    /**
     * The currently active academic year.
     */
    public function currentAcademicYear(): ?AcademicYear
    {
        return AcademicYear::query()
            ->where('is_active', true)
            ->latest('start_date')
            ->first();
    }

    /**
     * The currently active academic term belonging to the active year.
     */
    public function currentAcademicTerm(): ?AcademicTerm
    {
        return AcademicTerm::query()
            ->where('is_active', true)
            ->when($year = $this->currentAcademicYear(), fn (Builder $q) => $q->where('academic_year_id', $year->id))
            ->orderBy('sequence')
            ->first();
    }

    /**
     * The operating days configured for the school (fallback: Monday–Saturday).
     *
     * @return list<string>
     */
    public function operatingDays(): array
    {
        $setting = $this->settings->findBy(['key' => 'operating_days']);

        if ($setting === null || blank($setting->value)) {
            return array_column(ScheduleDay::toOptions(), 'value');
        }

        $decoded = json_decode((string) $setting->value, true);

        if (is_array($decoded) && $decoded !== []) {
            return array_values(array_filter(array_map('strval', $decoded)));
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $setting->value))));
    }

    /**
     * The academic dashboard payload.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function dashboard(array $filters = []): array
    {
        $yearId = ! empty($filters['academic_year_id'])
            ? (int) $filters['academic_year_id']
            : $this->currentAcademicYear()?->id;
        $termId = ! empty($filters['academic_term_id'])
            ? (int) $filters['academic_term_id']
            : $this->currentAcademicTerm()?->id;
        $campusId = ! empty($filters['campus_id']) ? (int) $filters['campus_id'] : null;

        $contextScope = static function (Builder $q) use ($yearId, $termId, $campusId): void {
            $q->where('academic_year_id', $yearId);
            if ($termId) {
                $q->where('academic_term_id', $termId);
            }
            if ($campusId) {
                $q->where('campus_id', $campusId);
            }
        };

        $activeOfferings = $this->offeringCount($yearId, $termId, $campusId);

        $subjectsWithoutTeacher = Subject::query()
            ->whereDoesntHave('offerings', static function (Builder $q) use ($yearId, $termId, $campusId): void {
                $q->where('academic_year_id', $yearId);
                if ($termId) {
                    $q->where('academic_term_id', $termId);
                }
                if ($campusId) {
                    $q->where('campus_id', $campusId);
                }
                $q->whereNotNull('teacher_id');
            })
            ->count();

        $enrollments = $this->enrollments($yearId, $termId, $campusId);
        $enrolledStudentIds = $enrollments->pluck('student_id')->unique();

        $assignedStudentIds = AcademicClassStudent::query()
            ->where('is_active', true)
            ->whereHas('academicClass', static function (Builder $q) use ($yearId, $termId, $campusId): void {
                $q->where('academic_year_id', $yearId);
                if ($termId) {
                    $q->where('academic_term_id', $termId);
                }
                if ($campusId) {
                    $q->where('campus_id', $campusId);
                }
            })
            ->pluck('student_id');

        $withoutClass = $enrolledStudentIds->diff($assignedStudentIds->values())->count();

        $classesToday = $this->scheduleCountForDay($this->dayKey(now()), $yearId, $termId, $campusId);
        $upcomingDay = $this->dayKey(now()->addDay());
        $upcomingClasses = in_array($upcomingDay, $this->operatingDays(), true)
            ? $this->scheduleCountForDay($upcomingDay, $yearId, $termId, $campusId)
            : 0;

        $byGradeLevel = $enrollments->groupBy('grade_level_id')->map->count();
        $bySection = $enrollments->whereNotNull('section_id')->groupBy('section_id')->map->count();

        $byDepartment = $activeOfferings->whereNotNull('department_id')->groupBy('department_id')->map->count();

        $teacherLoad = TeacherAssignment::query()
            ->where('is_active', true)
            ->when($yearId, fn (Builder $q) => $q->where('academic_year_id', $yearId))
            ->when($termId, fn (Builder $q) => $q->where('academic_term_id', $termId))
            ->get(['teacher_id', 'units'])
            ->groupBy('teacher_id')
            ->map(fn ($items) => (float) $items->sum(fn ($item) => (float) ($item['units'] ?? 0)))
            ->sortDesc();

        return [
            'context' => [
                'academic_year' => $yearId ? AcademicYear::query()->find($yearId)?->name : null,
                'academic_term' => $termId ? AcademicTerm::query()->find($termId)?->name : null,
                'campus_id' => $campusId,
                'campus_name' => $campusId ? Campus::query()->find($campusId)?->name : null,
            ],
            'totals' => [
                'active_sections' => $activeOfferings->whereNotNull('section_id')->pluck('section_id')->unique()->count(),
                'subjects' => Subject::query()->where('is_active', true)->count(),
                'subjects_without_teacher' => $subjectsWithoutTeacher,
                'teachers' => Teacher::query()->count(),
                'enrolled_students' => $enrolledStudentIds->count(),
                'students_without_class' => $withoutClass,
                'classes_today' => $classesToday,
                'upcoming_classes' => $upcomingClasses,
            ],
            'by_grade_level' => $byGradeLevel,
            'by_section' => $bySection,
            'by_department' => $byDepartment,
            'teacher_load' => $teacherLoad,
        ];
    }

    /**
     * Active enrollment rows scoped to the academic context.
     *
     * @return \Illuminate\Support\Collection<int, Enrollment>
     */
    protected function enrollments(int|string|null $yearId, int|string|null $termId, int|string|null $campusId): \Illuminate\Support\Collection
    {
        return Enrollment::query()
            ->select(['student_id', 'grade_level_id', 'section_id'])
            ->where('academic_year_id', $yearId)
            ->when($termId, fn (Builder $q) => $q->where('academic_term_id', $termId))
            ->when($campusId, fn (Builder $q) => $q->where('campus_id', $campusId))
            ->whereIn('status', EnrollmentStatus::occupancyStatuses())
            ->get();
    }

    /**
     * Active subject offerings scoped to the academic context.
     *
     * @return \App\Models\SubjectOffering[]|\Illuminate\Database\Eloquent\Collection<int, SubjectOffering>
     */
    protected function offeringCount(int|string|null $yearId, int|string|null $termId, int|string|null $campusId)
    {
        if ($yearId === null) {
            return collect();
        }

        return SubjectOffering::query()
            ->select(['section_id', 'department_id'])
            ->where('academic_year_id', $yearId)
            ->when($termId, fn (Builder $q) => $q->where('academic_term_id', $termId))
            ->when($campusId, fn (Builder $q) => $q->where('campus_id', $campusId))
            ->get();
    }

    /**
     * Count the active schedules on a day within the academic context.
     */
    protected function scheduleCountForDay(string $day, int|string|null $yearId, int|string|null $termId, int|string|null $campusId): int
    {
        return ClassSchedule::query()
            ->where('academic_year_id', $yearId)
            ->when($termId, fn (Builder $q) => $q->where('academic_term_id', $termId))
            ->when($campusId, fn (Builder $q) => $q->where('campus_id', $campusId))
            ->where('day', $day)
            ->where('is_active', true)
            ->count();
    }

    /**
     * The lowercase weekday key of a date.
     */
    protected function dayKey(Carbon $date): string
    {
        return strtolower($date->format('l'));
    }
}