<?php

namespace App\Services;

use App\Enums\ScheduleDay;
use App\Exceptions\ApiException;
use App\Models\ClassSchedule;
use App\Repositories\Contracts\ClassScheduleRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Schedules place offerings on a day/time slot and bind a section, teacher
 * and room. A dedicated conflict engine prevents double-booking of the same
 * teacher, room or section and supports explicit override by an authorized
 * user. The timetable resolver renders the weekly grid used by the UI.
 */
class ClassScheduleService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['conflict_reason'];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'day', 'start_time', 'end_time'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = [
        'academicYear',
        'academicTerm',
        'campus',
        'gradeLevel',
        'section',
        'subject',
        'teacher.employee',
        'room',
        'subjectOffering',
    ];

    public function __construct(private readonly ClassScheduleRepositoryInterface $repo) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }

    /**
     * The equality filters extracted from the request.
     *
     * @return array<string, mixed>
     */
    protected function filters(\App\Http\Requests\Api\IndexRequest $request): array
    {
        $filters = parent::filters($request);

        foreach (['academic_year_id', 'academic_term_id', 'campus_id', 'grade_level_id', 'section_id', 'subject_id', 'teacher_id', 'room_id', 'day'] as $column) {
            if ($request->has($column)) {
                $filters[$column] = $request->input($column);
            }
        }

        return $filters;
    }

    /**
     * Create a schedule. When conflicts are detected the record is rejected
     * unless the caller explicitly overrides them.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $conflicts = $this->detectConflicts($data);

        $data['conflict_override'] = (bool) ($data['conflict_override'] ?? false);

        if ($conflicts->isNotEmpty() && ! $data['conflict_override']) {
            throw ApiException::unprocessable('The schedule conflicts with an existing class.', [
                'conflicts' => $conflicts,
            ]);
        }

        if ($conflicts->isNotEmpty()) {
            $data['conflict_reason'] = $data['conflict_reason']
                ?? implode('; ', $conflicts->pluck('reason')->unique()->all());
        }

        return parent::create($data);
    }

    /**
     * Update a schedule while re-running the conflict engine.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        $merged = array_merge($model->only(['academic_year_id', 'academic_term_id', 'campus_id', 'day', 'start_time', 'end_time', 'teacher_id', 'room_id', 'section_id']), $data);

        $conflicts = $this->detectConflicts($merged, $model);

        $data['conflict_override'] = (bool) ($data['conflict_override'] ?? $model->conflict_override);

        if ($conflicts->isNotEmpty() && ! $data['conflict_override']) {
            throw ApiException::unprocessable('The schedule conflicts with an existing class.', [
                'conflicts' => $conflicts,
            ]);
        }

        if ($conflicts->isNotEmpty()) {
            $data['conflict_reason'] = $data['conflict_reason']
                ?? implode('; ', $conflicts->pluck('reason')->unique()->all());
        }

        return parent::update($model, $data);
    }

    /**
     * List every schedule that would collide with the proposed data.
     *
     * The overlap test is exclusive of the boundary at the start time because
     * a back-to-back class (finishing exactly when another begins) is fine, but
     * identical start/end times collide.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, array{type: string, reason: string, schedule: array<string, mixed>}>
     */
    public function detectConflicts(array $data, ?ClassSchedule $except = null): Collection
    {
        if (blank($data['day'] ?? null) || blank($data['start_time'] ?? null) || blank($data['end_time'] ?? null)) {
            return collect();
        }

        $day = strtolower((string) $data['day']);
        $start = $this->normalizeTime($data['start_time']);
        $end = $this->normalizeTime($data['end_time']);

        if ($start >= $end) {
            throw ApiException::unprocessable('The start time must be earlier than the end time.');
        }

        $teacherId = $data['teacher_id'] ?? null;
        $roomId = $data['room_id'] ?? null;
        $sectionId = $data['section_id'] ?? null;
        $offeringId = $data['subject_offering_id'] ?? null;

        $query = ClassSchedule::query()
            ->when(isset($data['academic_year_id']), fn (Builder $q) => $q->where('academic_year_id', $data['academic_year_id']))
            ->when(isset($data['academic_term_id']), fn (Builder $q) => $q->where('academic_term_id', $data['academic_term_id']))
            ->when(isset($data['campus_id']), fn (Builder $q) => $q->where('campus_id', $data['campus_id']))
            ->where('day', $day)
            ->whereTime('start_time', '<', $end->format('H:i'))
            ->whereTime('end_time', '>', $start->format('H:i'))
            ->when($except !== null, fn (Builder $q) => $q->whereKeyNot($except->getKey()));

        $candidates = $query->with(['teacher.employee', 'room', 'section', 'subject'])->get();

        if ($teacherId) {
            $candidates->where('teacher_id', (int) $teacherId);
        }

        $conflicts = collect();

        foreach ($candidates as $schedule) {
            $reasons = [];

            if ($teacherId && (int) $schedule->teacher_id === (int) $teacherId) {
                $reasons[] = 'Teacher is already scheduled at this time.';
            }

            if ($roomId && $schedule->room_id !== null && (int) $schedule->room_id === (int) $roomId) {
                $reasons[] = 'Room is already occupied at this time.';
            }

            if ($sectionId && (int) $schedule->section_id === (int) $sectionId && (int) $schedule->subject_offering_id !== (int) $offeringId) {
                $reasons[] = 'Section already has a class at this time.';
            }

            if ($reasons === []) {
                continue;
            }

            $conflicts->push([
                'type' => $this->conflictType($reasons),
                'reason' => implode(' ', $reasons),
                'schedule' => $this->scheduleSummary($schedule),
            ]);
        }

        return $conflicts;
    }

    /**
     * The weekly timetable grid for a section or set of sections.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function timetable(array $filters = []): array
    {
        $days = isset($filters['days']) && is_array($filters['days']) && $filters['days'] !== []
            ? $filters['days']
            : array_column(ScheduleDay::toOptions(), 'value');

        $schedules = $this->repo->query()
            ->with($this->with)
            ->where('is_active', true)
            ->when(! empty($filters['academic_year_id']), fn (Builder $q) => $q->where('academic_year_id', $filters['academic_year_id']))
            ->when(! empty($filters['academic_term_id']), fn (Builder $q) => $q->where('academic_term_id', $filters['academic_term_id']))
            ->when(! empty($filters['campus_id']), fn (Builder $q) => $q->where('campus_id', $filters['campus_id']))
            ->when(! empty($filters['grade_level_id']), fn (Builder $q) => $q->where('grade_level_id', $filters['grade_level_id']))
            ->when(! empty($filters['section_id']), fn (Builder $q) => $q->where('section_id', $filters['section_id']))
            ->whereIn('day', $days)
            ->orderBy('day')
            ->orderBy('start_time')
            ->get();

        $grid = [];
        foreach ($days as $day) {
            $grid[strtolower($day)] = $schedules
                ->where('day', strtolower($day))
                ->values()
                ->map(fn (ClassSchedule $schedule) => $this->scheduleSummary($schedule))
                ->all();
        }

        return [
            'days' => $days,
            'grid' => $grid,
        ];
    }

    /**
     * The schedules of a teacher resolved against their teaching assignments.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function teacherCalendar(int $teacherId, array $filters = []): Collection
    {
        return $this->repo->query()
            ->with($this->with)
            ->where('teacher_id', $teacherId)
            ->where('is_active', true)
            ->when(! empty($filters['academic_year_id']), fn (Builder $q) => $q->where('academic_year_id', $filters['academic_year_id']))
            ->when(! empty($filters['academic_term_id']), fn (Builder $q) => $q->where('academic_term_id', $filters['academic_term_id']))
            ->orderBy('day')
            ->orderBy('start_time')
            ->get()
            ->map(fn (ClassSchedule $schedule) => $this->scheduleSummary($schedule));
    }

    /**
     * The schedules of a section grouped by day for the section timetable.
     *
     * @return array<string, mixed>
     */
    public function sectionTimetable(int $sectionId, array $filters = []): array
    {
        $filters['section_id'] = $sectionId;

        return $this->timetable($filters);
    }

    /**
     * The normalized `H:i` timestamp for overlap math.
     */
    protected function normalizeTime(mixed $time): Carbon
    {
        if ($time instanceof Carbon) {
            return $time->copy()->setDate(2000, 1, 1);
        }

        return Carbon::parse($time)->setDate(2000, 1, 1);
    }

    /**
     * The dominant conflict type from the list of reasons.
     */
    protected function conflictType(array $reasons): string
    {
        if (str_contains($reasons[0], 'Teacher')) {
            return 'teacher';
        }

        if (str_contains($reasons[0], 'Room')) {
            return 'room';
        }

        return 'section';
    }

    /**
     * A compact, human-friendly summary of a schedule.
     *
     * @return array<string, mixed>
     */
    protected function scheduleSummary(ClassSchedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'day' => $schedule->day,
            'start_time' => $schedule->start_time?->format('H:i'),
            'end_time' => $schedule->end_time?->format('H:i'),
            'section' => $schedule->relationLoaded('section') && $schedule->section ? [
                'id' => $schedule->section->id,
                'name' => $schedule->section->name,
            ] : null,
            'subject' => $schedule->relationLoaded('subject') && $schedule->subject ? [
                'id' => $schedule->subject->id,
                'name' => $schedule->subject->name,
                'code' => $schedule->subject->code,
            ] : null,
            'teacher' => $schedule->relationLoaded('teacher') && $schedule->teacher ? [
                'id' => $schedule->teacher->id,
                'name' => $schedule->teacher->employee?->full_name,
            ] : null,
            'room' => $schedule->relationLoaded('room') && $schedule->room ? [
                'id' => $schedule->room->id,
                'name' => $schedule->room->name,
                'code' => $schedule->room->code,
            ] : null,
        ];
    }
}