<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\TeacherAssignment;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Contracts\TeacherAssignmentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Teacher enrollments mirror which teacher handles which subject/section in
 * an academic period. They are auto-maintained from SubjectOfferings but may
 * be created directly by an administrator.
 */
class TeacherAssignmentService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = [];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'units'];

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
    ];

    public function __construct(private readonly TeacherAssignmentRepositoryInterface $repo) {}

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

        foreach (['academic_year_id', 'academic_term_id', 'campus_id', 'grade_level_id', 'section_id', 'subject_id', 'teacher_id'] as $column) {
            if ($request->has($column)) {
                $filters[$column] = $request->input($column);
            }
        }

        return $filters;
    }

    /**
     * Create a teacher assignment, guarding against duplicate teacher/subject
     * pairs in the same section/period.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $this->assertNoDuplicate(null, $data);

        return parent::create($data);
    }

    /**
     * Update a teacher assignment while keeping the uniqueness guarantee.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        $merged = array_merge($model->only([
            'academic_year_id', 'academic_term_id', 'campus_id',
            'grade_level_id', 'section_id', 'subject_id',
        ]), $data);

        $this->assertNoDuplicate($model, $merged);

        return parent::update($model, $data);
    }

    /**
     * A teacher can only teach one subject for a given section/period.
     *
     * @param  array<string, mixed>  $data
     */
    private function assertNoDuplicate(?TeacherAssignment $except, array $data): void
    {
        $duplicate = TeacherAssignment::query()
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('academic_term_id', $data['academic_term_id'] ?? null)
            ->where('campus_id', $data['campus_id'] ?? null)
            ->where('grade_level_id', $data['grade_level_id'])
            ->where('section_id', $data['section_id'])
            ->where('subject_id', $data['subject_id'])
            ->where('teacher_id', $data['teacher_id'])
            ->when($except !== null, fn (Builder $query) => $query->whereKeyNot($except->getKey()))
            ->exists();

        if ($duplicate) {
            throw ApiException::unprocessable('This teacher is already assigned to this subject for the given section and academic period.');
        }
    }

    /**
     * A summary of each teacher's total assigned load (in units) for the
     * given academic context.
     *
     * @param  array<string, mixed>  $filters
     * @return list<array{teacher_id: int, teacher_name: string|null, units: float, assignments: int}>
     */
    public function loadSummary(array $filters = []): array
    {
        $assignments = TeacherAssignment::query()
            ->with('teacher.employee')
            ->where('academic_year_id', $filters['academic_year_id'] ?? null)
            ->when(! empty($filters['academic_term_id']), fn (Builder $query) => $query->where('academic_term_id', $filters['academic_term_id']))
            ->when(! empty($filters['campus_id']), fn (Builder $query) => $query->where('campus_id', $filters['campus_id']))
            ->where('is_active', true)
            ->get();

        return $assignments
            ->groupBy('teacher_id')
            ->map(fn ($rows) => [
                'teacher_id' => $rows->first()->teacher_id,
                'teacher_name' => $rows->first()->teacher?->employee?->full_name,
                'units' => (float) $rows->sum(fn ($a) => (float) $a->units),
                'assignments' => $rows->count(),
            ])
            ->sortByDesc('units')
            ->values()
            ->all();
    }
}