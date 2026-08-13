<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\RoleEnum;
use App\Models\AcademicClass;
use App\Models\Announcement;
use App\Models\Employee;
use App\Models\Enrollment;
use App\Models\GradeRecord;
use App\Models\ParentGuardian;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

/**
 * The Admin Dashboard analytics.
 *
 * Part 8 – Admin Dashboard. Aggregates permission-scoped counters, enrollment
 * funnel statistics, activity snapshots and enrollment trends. Analytics are
 * always aggregated; sensitive detail is only reachable through the scoped
 * module endpoints.
 */
class AdminDashboardService
{
    public function __construct(
        private readonly AcademicContextService $context,
        private readonly ActivityLogService $activityLogs,
    ) {}

    /**
     * The dashboard payload for a system operator.
     *
     * @return array<string, mixed>
     */
    public function snapshot(?User $user): array
    {
        $yearId = $this->context->currentAcademicYear()?->id;
        $termId = $this->context->currentAcademicTerm()?->id;

        $activeEnrollments = Enrollment::query()
            ->whereIn('status', EnrollmentStatus::activeStatuses())
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId));

        return [
            'context' => [
                'academic_year' => $this->context->currentAcademicYear()?->only('id', 'name'),
                'academic_term' => $this->context->currentAcademicTerm()?->only('id', 'name'),
            ],
            'counters' => [
                'students' => Student::query()->count(),
                'parents' => ParentGuardian::query()->count(),
                'employees' => Employee::query()->count(),
                'teachers' => Teacher::query()->count(),
                'staff' => Staff::query()->count(),
                'users' => User::query()->count(),
                'active_enrollments' => (clone $activeEnrollments)->count(),
                'sections' => Section::query()->count(),
                'subjects' => Subject::query()->count(),
                'academic_classes' => AcademicClass::query()->count(),
                'grade_records' => GradeRecord::query()->count(),
                'announcements' => Announcement::query()->count(),
            ],
            'enrollment_status' => $this->enrollmentStatusBreakdown($yearId),
            'grade_status' => $this->gradeStatusBreakdown($yearId, $termId),
            'enrollment_trend' => $this->enrollmentTrend(),
            'activity' => $this->activitySnapshot($user),
        ];
    }

    /**
     * The enrollment counts per status.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function enrollmentStatusBreakdown(?int $yearId): Collection
    {
        return Enrollment::query()
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'label' => EnrollmentStatus::tryFrom($row->status)?->label() ?? $row->status,
                'total' => (int) $row->total,
            ]);
    }

    /**
     * The grade record counts per workflow status.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function gradeStatusBreakdown(?int $yearId, ?int $termId): Collection
    {
        return GradeRecord::query()
            ->when($yearId, fn ($q) => $q->where('academic_year_id', $yearId))
            ->when($termId, fn ($q) => $q->where('academic_term_id', $termId))
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status,
                'total' => (int) $row->total,
            ]);
    }

    /**
     * The last six months of new enrollments and new users.
     */
    protected function enrollmentTrend(): Collection
    {
        $months = collect(range(5, 0))
            ->map(fn (int $i) => now()->startOfMonth()->subMonths($i));

        $enrollments = Enrollment::query()
            ->where('created_at', '>=', $months->first())
            ->select(DB::raw("strftime('%Y-%m', created_at) as month"), DB::raw('count(*) as total'))
            ->groupBy('month')
            ->pluck('total', 'month');

        $users = User::query()
            ->where('created_at', '>=', $months->first())
            ->select(DB::raw("strftime('%Y-%m', created_at) as month"), DB::raw('count(*) as total'))
            ->groupBy('month')
            ->pluck('total', 'month');

        return $months->map(fn ($month) => [
            'month' => $month->format('Y-m'),
            'label' => $month->format('M'),
            'enrollments' => (int) ($enrollments[$month->format('Y-m')] ?? 0),
            'users' => (int) ($users[$month->format('Y-m')] ?? 0),
        ])->values();
    }

    /**
     * The most recent non-restricted activity entries.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function activitySnapshot(?User $user): Collection
    {
        $restricted = collect();
        if ($user !== null && ! $user->hasRole(RoleEnum::SUPER_ADMINISTRATOR->roleName())) {
            $restricted = collect(ActivityLogService::RESTRICTED_PREFIXES)->flatMap(fn (string $prefix) => Activity::query()
                ->where('log_name', 'like', $prefix.'%')
                ->distinct()
                ->pluck('log_name'));
        }

        return Activity::query()
            ->with('causer')
            ->when($restricted->isNotEmpty(), fn ($q) => $q->whereNotIn('log_name', $restricted))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (Activity $activity) => [
                'id' => $activity->id,
                'log_name' => $activity->log_name,
                'description' => $activity->description,
                'event' => $activity->event,
                'causer_name' => $activity->causer?->name,
                'created_at' => $activity->created_at?->toISOString(),
            ]);
    }
}