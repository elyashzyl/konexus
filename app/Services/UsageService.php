<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\Employee;
use App\Models\StudentDocument;
use App\Models\SubscriptionUsage;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Captures and reads the monthly usage snapshots used to enforce plan limits
 * and to surface capacity warnings to school administrators.
 */
class UsageService
{
    public function __construct(private readonly FeatureAccessService $featureAccess) {}

    /**
     * Capture the current usage snapshot of a tenant for the given period.
     */
    public function snapshot(Tenant $tenant, ?int $year = null, ?int $month = null): SubscriptionUsage
    {
        $year ??= (int) Carbon::now()->year;
        $month ??= (int) Carbon::now()->month;

        $subscription = $tenant->currentSubscription();

        $usage = SubscriptionUsage::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'period_year' => $year, 'period_month' => $month],
            [
                'subscription_id' => $subscription?->id,
                'students_count' => $this->countStudents($tenant),
                'users_count' => $this->countUsers($tenant),
                'staff_count' => $this->countStaff($tenant),
                'branches_count' => $this->countCampuses($tenant),
                'storage_mb' => $this->storageMb($tenant),
                'documents_count' => $this->countDocuments($tenant),
                'database_size_mb' => $this->databaseSizeMb(),
                'captured_at' => now(),
            ]
        );

        return $usage;
    }

    /**
     * The latest snapshot of a tenant (or the default zeroed snapshot).
     */
    public function current(Tenant $tenant): SubscriptionUsage
    {
        return SubscriptionUsage::query()
            ->where('tenant_id', $tenant->id)
            ->latest('period_year')
            ->latest('period_month')
            ->first() ?? new SubscriptionUsage([
                'tenant_id' => $tenant->id,
                'period_year' => Carbon::now()->year,
                'period_month' => Carbon::now()->month,
            ]);
    }

    /**
     * The usage trend of the last N snapshots.
     *
     * @return \Illuminate\Support\Collection<int, SubscriptionUsage>
     */
    public function trend(Tenant $tenant, int $months = 6)
    {
        return SubscriptionUsage::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->limit($months)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Compare current usage against the plan limits.
     *
     * @return array{usage: array<string, int>, limits: array<string, int|null>, warnings: array<int, array{key: string, label: string, used: int, limit: int, percent: int}>}
     */
    public function limitStatus(Tenant $tenant): array
    {
        $usage = $this->current($tenant);
        $limits = $this->featureAccess->planLimits($tenant);

        $usageMap = [
            'students_count' => (int) $usage->students_count,
            'staff_count' => (int) $usage->staff_count,
            'branches_count' => (int) $usage->branches_count,
            'users_count' => (int) $usage->users_count,
            'storage_mb' => (int) $usage->storage_mb,
        ];

        $labels = [
            'students_count' => 'Students',
            'staff_count' => 'Staff',
            'branches_count' => 'Campuses',
            'users_count' => 'Users',
            'storage_mb' => 'Storage (MB)',
        ];

        $warnings = [];

        foreach ($limits as $key => $limit) {
            if ($limit === null || $limit <= 0 || ($usageMap[$key] ?? 0) === 0) {
                continue;
            }

            $used = $usageMap[$key] ?? 0;
            $percent = (int) round(($used / $limit) * 100);

            $thresholds = $this->featureAccess->settings()->get('usage_warning_thresholds', [80, 90, 100]);

            foreach ((array) $thresholds as $threshold) {
                if ($percent >= (int) $threshold) {
                    $warnings[] = [
                        'key' => $key,
                        'label' => $labels[$key] ?? $key,
                        'used' => $used,
                        'limit' => $limit,
                        'percent' => $percent,
                    ];
                    break;
                }
            }
        }

        return [
            'usage' => $usageMap,
            'limits' => $limits,
            'warnings' => $warnings,
        ];
    }

    /**
     * The students enrolled at the tenant's campuses.
     */
    protected function countStudents(Tenant $tenant): int
    {
        $campusIds = $this->campusIds($tenant);

        if ($campusIds === []) {
            return 0;
        }

        return DB::table('enrollments')
            ->whereIn('campus_id', $campusIds)
            ->distinct()
            ->count('student_id');
    }

    /**
     * The active user accounts linked to the tenant's students.
     */
    protected function countUsers(Tenant $tenant): int
    {
        $campusIds = $this->campusIds($tenant);

        if ($campusIds === []) {
            return 0;
        }

        $studentIds = DB::table('enrollments')
            ->whereIn('campus_id', $campusIds)
            ->distinct()
            ->pluck('student_id');

        $userIds = DB::table('students')
            ->whereIn('id', $studentIds)
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->unique();

        return User::query()->whereIn('id', $userIds)->where('is_active', true)->count();
    }

    /**
     * The employees of the school. Employees carry no campus link, so this is
     * a platform-wide approximation used only for reporting.
     */
    protected function countStaff(Tenant $tenant): int
    {
        return Employee::query()->count();
    }

    /**
     * The campuses linked to the tenant's school profile.
     */
    protected function countCampuses(Tenant $tenant): int
    {
        return count($this->campusIds($tenant));
    }

    /**
     * Approximate storage footprint of the tenant's student documents.
     */
    protected function storageMb(Tenant $tenant): int
    {
        $studentIds = $this->studentIdsForSchool($tenant);

        if ($studentIds === []) {
            return 0;
        }

        $bytes = StudentDocument::query()
            ->whereIn('student_id', $studentIds)
            ->sum(DB::raw('COALESCE(file_size, 0)'));

        return (int) round($bytes / (1024 * 1024));
    }

    /**
     * The document count of the tenant's students.
     */
    protected function countDocuments(Tenant $tenant): int
    {
        $studentIds = $this->studentIdsForSchool($tenant);

        if ($studentIds === []) {
            return 0;
        }

        return StudentDocument::query()->whereIn('student_id', $studentIds)->count();
    }

    /**
     * The distinct student ids enrolled at the tenant's campuses.
     *
     * @return list<int>
     */
    protected function studentIdsForSchool(Tenant $tenant): array
    {
        $campusIds = $this->campusIds($tenant);

        if ($campusIds === []) {
            return [];
        }

        return DB::table('enrollments')
            ->whereIn('campus_id', $campusIds)
            ->distinct()
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * The approximate database footprint of the tenant (per-tenant estimate).
     */
    protected function databaseSizeMb(): int
    {
        $db = config('database.default');
        $name = config("database.connections.{$db}.database");

        if (empty($name)) {
            return 0;
        }

        $path = database_path($name);

        return file_exists($path) ? (int) round(filesize($path) / (1024 * 1024)) : 0;
    }

    /**
     * The campus ids belonging to the tenant's school profile.
     *
     * @return list<int>
     */
    protected function campusIds(Tenant $tenant): array
    {
        if (! $tenant->school_profile_id) {
            return [];
        }

        return Campus::query()
            ->where('school_profile_id', $tenant->school_profile_id)
            ->pluck('id')
            ->all();
    }
}