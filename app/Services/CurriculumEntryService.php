<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\CurriculumEntry;
use App\Repositories\Contracts\CurriculumEntryRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Curriculum entries define which subjects are taught to a grade level within
 * an academic year/term. Creating a curriculum also provisions the matching
 * Subject Offerings so scheduling and grading never run against a dangling
 * curriculum.
 */
class CurriculumEntryService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = [];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'display_order', 'units'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = ['academicYear', 'academicTerm', 'campus', 'gradeLevel', 'subject'];

    protected string $defaultSortBy = 'display_order';

    protected string $defaultSortDir = 'asc';

    public function __construct(
        private readonly CurriculumEntryRepositoryInterface $repo,
    ) {}

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

        if ($request->has('academic_year_id')) {
            $filters['academic_year_id'] = $request->input('academic_year_id');
        }

        if ($request->has('grade_level_id')) {
            $filters['grade_level_id'] = $request->input('grade_level_id');
        }

        return $filters;
    }

    /**
     * Create a curriculum entry, guarding against duplicate subject/grade
     * pairs within the same academic context.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $this->assertNoDuplicate(null, $data);

        return parent::create($data);
    }

    /**
     * Update a curriculum entry while keeping the uniqueness guarantee.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        $merged = array_merge($model->only(['academic_year_id', 'academic_term_id', 'campus_id', 'grade_level_id', 'subject_id']), $data);

        $this->assertNoDuplicate($model, $merged);

        return parent::update($model, $data);
    }

    /**
     * A subject can only appear once (per campus + term, grade, counterpart).
     *
     * @param  array<string, mixed>  $data
     */
    private function assertNoDuplicate(?CurriculumEntry $except, array $data): void
    {
        $duplicate = CurriculumEntry::query()
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('academic_term_id', $data['academic_term_id'] ?? null)
            ->where('campus_id', $data['campus_id'] ?? null)
            ->where('grade_level_id', $data['grade_level_id'])
            ->where('subject_id', $data['subject_id'])
            ->when($except !== null, fn (Builder $query) => $query->whereKeyNot($except->getKey()))
            ->exists();

        if ($duplicate) {
            throw ApiException::unprocessable('The subject is already part of the curriculum for this grade in the given academic period.');
        }
    }
}