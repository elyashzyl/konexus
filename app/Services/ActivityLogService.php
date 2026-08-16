<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

/**
 * The Audit Center.
 *
 * Part 8 – Audit & Activity Center. Provides the global activity timeline with
 * filtering, statistics and detail drill-downs over the spatie/activitylog
 * table. Log names covering guidance, medical, clinic, finance, library and
 * payroll records are restricted: only Super Administrators (or users holding
 * an explicit audit permission) may browse them.
 */
class ActivityLogService
{
    /**
     * Restricted log name prefixes. Their content must never surface to
     * unauthorized roles (guidance / medical / financial confidentiality).
     *
     * @var list<string>
     */
    public const RESTRICTED_PREFIXES = [
        'guidance',
        'medical',
        'clinic',
        'finance',
        'payroll',
        'library',
        'backups',
        'user_sessions',
    ];

    /**
     * The auditable log names (grouped for the UI).
     *
     * @return array<string, mixed>
     */
    public function catalog(?User $user = null): array
    {
        $names = Activity::query()
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name')
            ->filter(fn (?string $name) => $name !== null)
            ->map(fn (string $name) => $name)
            ->values();

        $restricted = $this->restrictedFor($user)->toArray();

        $groups = $names->groupBy(fn (string $name) => str($name)->before('_')->toString())
            ->map(fn (Collection $items, string $group) => $items->values())
            ->sortKeys();

        return [
            'groups' => $groups,
            'restricted' => $restricted,
        ];
    }

    /**
     * The paginated activity timeline honoring the user's visibility.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Activity>
     */
    public function index(?User $user, array $filters): LengthAwarePaginator
    {
        $query = Activity::query()->with(['causer'])
            ->when(filled($filters['log_name'] ?? null), fn (Builder $q) => $q->where('log_name', $filters['log_name']))
            ->when(filled($filters['subject_type'] ?? null), fn (Builder $q) => $q->where('subject_type', 'like', '%'.$filters['subject_type']))
            ->when(filled($filters['event'] ?? null), fn (Builder $q) => $q->where('event', $filters['event']))
            ->when(filled($filters['causer_id'] ?? null), fn (Builder $q) => $q->where('causer_id', $filters['causer_id']))
            ->when(filled($filters['search'] ?? null), fn (Builder $q) => $q->where('description', 'like', '%'.$filters['search'].'%'))
            ->when(filled($filters['date_from'] ?? null), fn (Builder $q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn (Builder $q) => $q->whereDate('created_at', '<=', $filters['date_to']));

        if (! empty($filters['batch'])) {
            $query->whereNotNull('batch');
        }

        $restricted = $this->restrictedFor($user);
        if ($restricted->isNotEmpty()) {
            $query->whereNotIn('log_name', $restricted);
        }

        return $query->orderByDesc('created_at')->paginate((int) ($filters['per_page'] ?? 25));
    }

    /**
     * Aggregated activity statistics for the current day/week.
     *
     * @return array<string, mixed>
     */
    public function stats(?User $user): array
    {
        $restricted = $this->restrictedFor($user);

        $base = Activity::query();
        if ($restricted->isNotEmpty()) {
            $base->whereNotIn('log_name', $restricted);
        }

        $total = (clone $base)->count();
        $today = (clone $base)->whereDate('created_at', today())->count();

        $uniqueCausers = (clone $base)->whereNotNull('causer_id')->distinct()->count('causer_id');

        $logNames = (clone $base)
            ->select('log_name', DB::raw('count(*) as total'))
            ->groupBy('log_name')
            ->orderBy('log_name')
            ->get()
            ->map(fn ($row) => ['log_name' => $row->log_name, 'total' => (int) $row->total]);

        $topModules = (clone $base)
            ->select('log_name', DB::raw('count(*) as total'))
            ->groupBy('log_name')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($row) => ['module' => $row->log_name, 'total' => (int) $row->total]);

        $events = (clone $base)
            ->whereNotNull('event')
            ->select('event', DB::raw('count(*) as total'))
            ->groupBy('event')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => ['event' => $row->event, 'total' => (int) $row->total]);

        return [
            'total' => $total,
            'today' => $today,
            'unique_causers' => $uniqueCausers,
            'log_names' => $logNames,
            'top_modules' => $topModules,
            'events' => $events,
        ];
    }

    /**
     * The full record of a single activity entry.
     */
    public function find(?User $user, int $id): Activity
    {
        $activity = Activity::query()->with(['causer'])->findOrFail($id);

        if ($this->restrictedFor($user)->contains($activity->log_name)) {
            abort(403, 'You are not allowed to view this audit record.');
        }

        return $activity;
    }

    /**
     * The users who caused activity, used for the causer filter.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function causers(?User $user): Collection
    {
        $ids = Activity::query()->whereNotNull('causer_id')->distinct()->pluck('causer_id');

        return User::query()->whereIn('id', $ids)->get()->map(fn (User $causer) => [
            'id' => $causer->id,
            'name' => $causer->name,
            'email' => $causer->email,
        ]);
    }

    /**
     * The restricted log names a user may not browse.
     *
     * @return Collection<int, string>
     */
    protected function restrictedFor(?User $user): Collection
    {
        if ($user !== null && $user->hasRole(RoleEnum::SUPER_ADMINISTRATOR->roleName())) {
            return collect();
        }

        return collect(self::RESTRICTED_PREFIXES)
            ->flatMap(fn (string $prefix) => Activity::query()
                ->where('log_name', 'like', $prefix.'%')
                ->distinct()
                ->pluck('log_name'));
    }
}