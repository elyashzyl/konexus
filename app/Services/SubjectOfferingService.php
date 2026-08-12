<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\CurriculumEntry;
use App\Models\SubjectOffering;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Contracts\SubjectOfferingRepositoryInterface;
use App\Repositories\Contracts\TeacherAssignmentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Subject offerings materialize a curriculum entry for a specific section
 * within an academic period and optionally bind a teacher, room and units.
 * The service keeps the offering, the teacher assignment and the curriculum
 * in a consistent state.
 */
class SubjectOfferingService extends CrudService
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
        'department',
        'room',
    ];

public function __construct(
        private readonly SubjectOfferingRepositoryInterface $repo,
        private readonly TeacherAssignmentRepositoryInterface $assignmentRepo,
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

        foreach (['academic_year_id', 'academic_term_id', 'campus_id', 'grade_level_id', 'section_id', 'subject_id', 'teacher_id', 'department_id'] as $column) {
            if ($request->has($column)) {
                $filters[$column] = $request->input($column);
            }
        }

        return $filters;
    }

    /**
     * Create an offering; duplicate section/subject pairs inside the academic
     * context are rejected.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $this->assertNoDuplicate(null, $data);

        $offering = parent::create($data);

        $this->syncTeacherAssignment($offering);

        return $offering;
    }

    /**
     * Update an offering while keeping the uniqueness + assignment invariants.
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

        /** @var SubjectOffering $offering */
        $offering = parent::update($model, $data);

        $this->syncTeacherAssignment($offering);

        return $offering;
    }

    /**
     * The same subject cannot be offered to the same section twice in the
     * academic context.
     *
     * @param  array<string, mixed>  $data
     */
    private function assertNoDuplicate(?SubjectOffering $except, array $data): void
    {
        $duplicate = SubjectOffering::query()
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('academic_term_id', $data['academic_term_id'] ?? null)
            ->where('campus_id', $data['campus_id'] ?? null)
            ->where('section_id', $data['section_id'])
            ->where('subject_id', $data['subject_id'])
            ->when($except !== null, fn (Builder $query) => $query->whereKeyNot($except->getKey()))
            ->exists();

        if ($duplicate) {
            throw ApiException::unprocessable('The subject is already offered to this section for the given academic period.');
        }
    }

    /**
     * Keep the teacher assignment mirror in sync when a teacher is bound to
     * or removed from an offering.
     */
    private function syncTeacherAssignment(SubjectOffering $offering): void
    {
        $yearId = $offering->academic_year_id;
        $termId = $offering->academic_term_id;
        $campusId = $offering->campus_id;
        $gradeLevelId = $offering->grade_level_id;
        $sectionId = $offering->section_id;
        $subjectId = $offering->subject_id;

        $existing = $this->assignmentRepo->query()
            ->where('academic_year_id', $yearId)
            ->when($termId, fn (Builder $query) => $query->where('academic_term_id', $termId))
            ->where('campus_id', $campusId ?? null)
            ->where('grade_level_id', $gradeLevelId)
            ->where('section_id', $sectionId)
            ->where('subject_id', $subjectId)
            ->first();

        if ($existing) {
            $existing->forceDelete();
        }

        if (! $offering->teacher_id) {
            return;
        }

        $this->assignmentRepo->create([
            'academic_year_id' => $yearId,
            'academic_term_id' => $termId,
            'campus_id' => $campusId,
            'grade_level_id' => $gradeLevelId,
            'section_id' => $sectionId,
            'subject_id' => $subjectId,
            'teacher_id' => $offering->teacher_id,
            'units' => $offering->units ?? $this->unitsFromCurriculum($offering),
            'status' => 'active',
            'is_active' => true,
        ]);
    }

    /**
     * Fall back to the curriculum's units when the offering has none.
     */
    private function unitsFromCurriculum(SubjectOffering $offering): float
    {
        $entry = CurriculumEntry::query()
            ->where('academic_year_id', $offering->academic_year_id)
            ->when($offering->academic_term_id, fn (Builder $query) => $query->where('academic_term_id', $offering->academic_term_id))
            ->where('campus_id', $offering->campus_id ?? null)
            ->where('grade_level_id', $offering->grade_level_id)
            ->where('subject_id', $offering->subject_id)
            ->first();

        return (float) ($entry?->units ?? 1);
    }

    /**
     * Delete an offering and its mirrored teacher assignment.
     *
     * @param  SubjectOffering  $model
     */
    public function delete(Model $model): bool
    {
        $deleted = parent::delete($model);

        if ($deleted) {
            $this->assignmentRepo->query()
                ->where('academic_year_id', $model->academic_year_id)
                ->when($model->academic_term_id, fn (Builder $query) => $query->where('academic_term_id', $model->academic_term_id))
                ->where('campus_id', $model->campus_id ?? null)
                ->where('grade_level_id', $model->grade_level_id)
                ->where('section_id', $model->section_id)
                ->where('subject_id', $model->subject_id)
                ->where('teacher_id', $model->teacher_id)
                ->get()
                ->each->delete();
        }

        return $deleted;
    }
}