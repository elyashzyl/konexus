<?php

namespace App\Services;

use App\Enums\GradeStatus;
use App\Exceptions\ApiException;
use App\Models\GradeCorrection;
use App\Models\GradeRecord;
use App\Repositories\Contracts\GradeCorrectionRepositoryInterface;
use App\Repositories\Contracts\GradeRecordRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Grade corrections provide the audited path for changing a finalized grade.
 * A change is requested with a reason; a reviewer approves or rejects it. Only
 * an approved correction mutates the underlying grade record.
 */
class GradeCorrectionService extends CrudService
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['reason', 'approval_remarks'];

    protected array $searchableRelations = [
        'student' => ['first_name', 'middle_name', 'last_name', 'student_number'],
        'subject' => ['name', 'code'],
    ];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'status'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = [
        'gradeRecord',
        'student',
        'subject',
        'academicTerm',
        'requestedBy',
        'approvedBy',
    ];

    public function __construct(
        private readonly GradeCorrectionRepositoryInterface $repo,
        private readonly GradeRecordRepositoryInterface $gradeRecordRepo,
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

        foreach (['student_id', 'subject_id', 'academic_term_id', 'status', 'grade_record_id'] as $column) {
            if ($request->has($column)) {
                $filters[$column] = $request->input($column);
            }
        }

        return $filters;
    }

    /**
     * Open a correction request against a finalized grade record.
     *
     * @param  array<string, mixed>  $data
     */
    public function request(GradeRecord $record, array $data): GradeCorrection
    {
        if (in_array($record->status, [GradeStatus::DRAFT->value, GradeStatus::IN_PROGRESS->value, GradeStatus::RETURNED->value], true)) {
            throw ApiException::unprocessable('Only finalized grade records can be corrected. Edit the record directly.');
        }

        $open = $this->repo->query()
            ->where('grade_record_id', $record->id)
            ->where('status', self::STATUS_PENDING)
            ->exists();

        if ($open) {
            throw ApiException::conflict('A pending correction request already exists for this grade record.');
        }

        /** @var GradeCorrection $correction */
        $correction = $this->repo->create([
            'grade_record_id' => $record->id,
            'student_id' => $data['student_id'] ?? $record->student_id,
            'subject_id' => $data['subject_id'] ?? $record->subject_id,
            'academic_term_id' => $data['academic_term_id'] ?? $record->academic_term_id,
            'current_grade' => $data['current_grade'] ?? $record->final_grade,
            'proposed_grade' => $data['proposed_grade'],
            'reason' => $data['reason'],
            'status' => self::STATUS_PENDING,
            'requested_by' => $data['requested_by'] ?? auth()->id(),
            'is_active' => true,
        ]);

        return $correction->load(['gradeRecord', 'student', 'subject']);
    }

    /**
     * Approve a pending correction, applying the change to the grade record
     * and stamping the review trail.
     */
    public function approve(GradeCorrection $correction, array $data = []): GradeCorrection
    {
        if ($correction->status !== self::STATUS_PENDING) {
            throw ApiException::conflict('Only pending correction requests can be approved.');
        }

        return DB::transaction(function () use ($correction, $data): GradeCorrection {
            $record = $correction->gradeRecord;

            if ($record === null) {
                $record = $this->gradeRecordRepo->findOrFail($correction->grade_record_id);
            }

            $this->gradeRecordRepo->update($record, [
                'raw_grade' => $correction->proposed_grade,
                'final_grade' => $correction->proposed_grade,
                'status' => GradeStatus::CORRECTED->value,
            ]);

            $this->repo->update($correction, [
                'status' => self::STATUS_APPROVED,
                'approved_by' => $data['approved_by'] ?? auth()->id(),
                'approved_at' => now(),
                'approval_remarks' => $data['approval_remarks'] ?? null,
            ]);

            return $correction->fresh(['gradeRecord', 'student', 'subject', 'approvedBy']);
        });
    }

    /**
     * Reject a pending correction without touching the grade record.
     *
     * @param  array<string, mixed>  $data
     */
    public function reject(GradeCorrection $correction, array $data = []): GradeCorrection
    {
        if ($correction->status !== self::STATUS_PENDING) {
            throw ApiException::conflict('Only pending correction requests can be rejected.');
        }

        $this->repo->update($correction, [
            'status' => self::STATUS_REJECTED,
            'approved_by' => $data['approved_by'] ?? auth()->id(),
            'approved_at' => now(),
            'approval_remarks' => $data['approval_remarks'] ?? 'Rejected.',
        ]);

        return $correction->fresh(['gradeRecord', 'approvedBy']);
    }

    /**
     * The correction history of a grade record.
     *
     * @return Model[]|GradeCorrection[]
     */
    public function historyForGradeRecord(int $gradeRecordId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repo->query()
            ->with($this->with)
            ->where('grade_record_id', $gradeRecordId)
            ->orderByDesc('created_at')
            ->get();
    }
}