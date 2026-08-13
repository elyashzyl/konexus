<?php

namespace App\Services;

use App\Enums\GradeStatus;
use App\Exceptions\ApiException;
use App\Models\GradeRecord;
use App\Models\SubjectOffering;
use App\Repositories\Contracts\GradeRecordRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Grade records hold the raw and final grade of a student for an offering.
 *
 * The grade workflow is draft -> in-progress -> submitted -> for-review ->
 * approved -> published, with `returned` allowing a supervisor to send a
 * submission back for editing. Only editable states can be changed; finalized
 * states require a GradeCorrection instead.
 */
class GradeRecordService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['remarks'];

    protected array $searchableRelations = [
        'student' => ['first_name', 'middle_name', 'last_name', 'student_number'],
        'subject' => ['name', 'code'],
    ];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'final_grade', 'raw_grade', 'status'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = [
        'student',
        'academicYear',
        'academicTerm',
        'gradeLevel',
        'section',
        'subject',
        'subjectOffering',
        'teacher.employee',
        'submittedBy',
        'approvedBy',
        'corrections',
    ];

    public function __construct(
        private readonly GradeRecordRepositoryInterface $repo,
        private readonly GradeScaleService $scaleService,
        private readonly NotificationService $notifications,
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

        foreach (['academic_year_id', 'academic_term_id', 'campus_id', 'grade_level_id', 'section_id', 'subject_id', 'subject_offering_id', 'student_id', 'teacher_id', 'status'] as $column) {
            if ($request->has($column)) {
                $filters[$column] = $request->input($column);
            }
        }

        return $filters;
    }

    /**
     * Create a grade record, automatically computing the final grade under the
     * active grade scale.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $data = $this->hydrateContext($data);
        $data = $this->applyFinalGrade($data);
        $data['status'] = $data['status'] ?? GradeStatus::DRAFT->value;
        $data['submitted_by'] = $data['submitted_by'] ?? auth()->id();
        $data['submitted_at'] = $data['submitted_at'] ?? now();

        return parent::create($data);
    }

    /**
     * Update a grade record; only editable statuses accept changes.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        /** @var GradeRecord $model */
        if (! $model->isEditable()) {
            throw ApiException::unprocessable('Only draft, in-progress or returned grade records can be edited.');
        }

        $data = $this->applyFinalGrade($data);

        return parent::update($model, $data);
    }

    /**
     * Bulk create/update grade records for a subject offering.
     *
     * @param  array{rows: list<array<string, mixed>>}  $payload
     * @return array{created: int, updated: int, skipped: int}
     */
    public function bulkUpsert(SubjectOffering $offering, array $payload): array
    {
        $rows = $payload['rows'] ?? [];

        if ($rows === []) {
            throw ApiException::unprocessable('No grade rows were provided.');
        }

        $studentIds = array_column($rows, 'student_id');

        return DB::transaction(function () use ($offering, $rows): array {
            $created = 0;
            $updated = 0;
            $skipped = 0;

            foreach ($rows as $row) {
                $studentId = (int) $row['student_id'];
                $raw = $row['raw_grade'] ?? null;
                $remarks = $row['remarks'] ?? null;

                $existing = $this->repo->query()
                    ->where('subject_offering_id', $offering->id)
                    ->where('student_id', $studentId)
                    ->first();

                $data = array_merge([
                    'student_id' => $studentId,
                    'academic_year_id' => $offering->academic_year_id,
                    'academic_term_id' => $offering->academic_term_id,
                    'campus_id' => $offering->campus_id,
                    'grade_level_id' => $offering->grade_level_id,
                    'section_id' => $offering->section_id,
                    'subject_id' => $offering->subject_id,
                    'subject_offering_id' => $offering->id,
                    'teacher_id' => $offering->teacher_id,
                ], array_filter([
                    'raw_grade' => $raw,
                    'remarks' => $remarks,
                ], static fn ($value) => $value !== null));

                if ($existing !== null) {
                    if (! $existing->isEditable()) {
                        $skipped++;
                        continue;
                    }

                    $existingData = $this->applyFinalGrade($data);
                    $this->repo->update($existing, $existingData);
                    $updated++;
                    continue;
                }

                $this->create($data);
                $created++;
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
            ];
        });
    }

    /**
     * Move a record into the given workflow state.
     *
     * @param  array<string, mixed>  $data
     */
    public function transition(GradeRecord $record, string $status, array $data = []): GradeRecord
    {
        $from = $record->status;

        if (! $this->transitionAllowed($from, $status)) {
            throw ApiException::conflict("A grade record cannot transition from '{$from}' to '{$status}'.");
        }

        $stamps = match ($status) {
            GradeStatus::SUBMITTED->value => ['submitted_by' => $data['actor_id'] ?? auth()->id(), 'submitted_at' => now()],
            GradeStatus::APPROVED->value => ['approved_by' => $data['actor_id'] ?? auth()->id(), 'approved_at' => now()],
            GradeStatus::PUBLISHED->value => ['approved_by' => $data['actor_id'] ?? auth()->id(), 'approved_at' => now(), 'published_at' => now()],
            GradeStatus::CORRECTED->value => ['status' => GradeStatus::CORRECTED->value],
            default => [],
        };

        $this->repo->update($record, array_merge(['status' => $status], $stamps));

        $record = $record->fresh(['student', 'subject', 'section', 'subjectOffering']);

        if ($status === GradeStatus::PUBLISHED->value) {
            $this->notifications->sendToStudentCircle(
                $record->student,
                'academic',
                [
                    'category' => 'academic',
                    'title' => 'Grade published',
                    'body' => sprintf(
                        'Your %s grade (%s) has been published.',
                        $record->subject?->name ?? 'Subject',
                        $record->final_grade ?? '—',
                    ),
                    'grade_record_id' => $record->id,
                    'subject_id' => $record->subject_id,
                    'final_grade' => $record->final_grade,
                ]
            );
        }

        return $record;
    }

    /**
     * The list of grades a student received across a term/year (report card
     * foundation).
     *
     * @return array<string, mixed>
     */
    public function studentReport(int $studentId, ?int $academicYearId = null, ?int $academicTermId = null): array
    {
        $records = $this->repo->query()
            ->with(['subject', 'subjectOffering', 'academicTerm'])
            ->where('student_id', $studentId)
            ->where('is_active', true)
            ->when($academicYearId, fn (Builder $query) => $query->where('academic_year_id', $academicYearId))
            ->when($academicTermId, fn (Builder $query) => $query->where('academic_term_id', $academicTermId))
            ->get();

        $published = $records
            ->whereIn('status', GradeStatus::finalizedStatuses())
            ->values();

        return [
            'student_id' => $studentId,
            'records' => $records->map(fn (GradeRecord $record) => [
                'id' => $record->id,
                'subject' => $record->subject?->name,
                'subject_code' => $record->subject?->code,
                'units' => (float) ($record->subjectOffering?->units ?? 0),
                'raw_grade' => $record->raw_grade === null ? null : (float) $record->raw_grade,
                'final_grade' => $record->final_grade === null ? null : (float) $record->final_grade,
                'remarks' => $record->remarks,
                'status' => $record->status,
                'term' => $record->academicTerm?->name,
            ]),
            'total_units' => (float) $published->sum(fn ($record) => (float) ($record->subjectOffering?->units ?? 0)),
            'published_records' => $published->count(),
            'general_average' => $this->generalAverage($published),
        ];
    }

    /**
     * The weighted general average of finalized records.
     *
     * @param  Collection<int, GradeRecord>  $records
     */
    protected function generalAverage(Collection $records): ?float
    {
        if ($records->isEmpty()) {
            return null;
        }

        $weightedSum = 0.0;
        $weightTotal = 0.0;

        foreach ($records as $record) {
            if ($record->final_grade === null) {
                continue;
            }
            $units = (float) ($record->subjectOffering?->units ?? 1);
            $weightedSum += (float) $record->final_grade * $units;
            $weightTotal += $units;
        }

        if ($weightTotal <= 0) {
            return null;
        }

        return round($weightedSum / $weightTotal, 2);
    }

    /**
     * Whether a workflow transition is permitted.
     */
    public function transitionAllowed(string $from, string $to): bool
    {
        $allowed = [
            GradeStatus::DRAFT->value => [GradeStatus::IN_PROGRESS->value, GradeStatus::SUBMITTED->value],
            GradeStatus::IN_PROGRESS->value => [GradeStatus::DRAFT->value, GradeStatus::SUBMITTED->value],
            GradeStatus::SUBMITTED->value => [GradeStatus::FOR_REVIEW->value, GradeStatus::RETURNED->value],
            GradeStatus::FOR_REVIEW->value => [GradeStatus::APPROVED->value, GradeStatus::RETURNED->value],
            GradeStatus::RETURNED->value => [GradeStatus::IN_PROGRESS->value, GradeStatus::SUBMITTED->value],
            GradeStatus::APPROVED->value => [GradeStatus::PUBLISHED->value],
        ];

        return in_array($to, $allowed[$from] ?? [], true);
    }

    /**
     * Hydrate the context columns of a record from the offering when present.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function hydrateContext(array $data): array
    {
        if (! empty($data['subject_offering_id']) && empty($data['subject_id'])) {
            $offering = SubjectOffering::query()->find($data['subject_offering_id']);

            if ($offering) {
                return array_merge([
                    'academic_year_id' => $offering->academic_year_id,
                    'academic_term_id' => $offering->academic_term_id,
                    'campus_id' => $offering->campus_id,
                    'grade_level_id' => $offering->grade_level_id,
                    'section_id' => $offering->section_id,
                    'subject_id' => $offering->subject_id,
                    'teacher_id' => $offering->teacher_id,
                ], array_filter($data, static fn ($value) => $value !== null));
            }
        }

        return $data;
    }

    /**
     * Apply the rounding rules of the active grade scale to the raw grade.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function applyFinalGrade(array $data): array
    {
        if (! array_key_exists('raw_grade', $data) || $data['raw_grade'] === null || $data['raw_grade'] === '') {
            $data['final_grade'] = null;

            return $data;
        }

        $resolved = $this->scaleService->finalizeGrade($data['raw_grade']);

        $data['final_grade'] = $resolved['final_grade'];
        $data['remarks'] = $data['remarks'] ?? $resolved['remarks'];

        return $data;
    }
}