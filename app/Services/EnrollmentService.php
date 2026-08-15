<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Enums\EnrollmentType;
use App\Enums\RequirementItemStatus;
use App\Events\EnrollmentStatusChanged;
use App\Exceptions\ApiException;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Campus;
use App\Models\CurriculumProgram;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\MasterData;
use App\Models\Section;
use App\Models\Student;
use App\Repositories\Contracts\EnrollmentCapacityOverrideRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRequirementRepositoryInterface;
use App\Repositories\Contracts\EnrollmentTransferRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
use App\Repositories\Contracts\StudentRepositoryInterface;
use App\Repositories\Contracts\SystemSettingRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Service handling the enrollment lifecycle and its configurable workflow.
 */
class EnrollmentService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['enrollment_number', 'reference_number'];

    /**
     * Relation columns included in free-text search (relation => columns).
     *
     * @var array<string, list<string>>
     */
    protected array $searchableRelations = [
        'student' => ['first_name', 'middle_name', 'last_name', 'student_number', 'lrn', 'email'],
    ];

    /**
     * Columns that are allowed to be sorted on.
     *
     * @var list<string>
     */
    protected array $sortable = [
        'id', 'created_at', 'updated_at',
        'enrollment_number', 'reference_number', 'status', 'enrollment_type',
        'enrollment_date', 'date_enrolled', 'transfer_date',
        'academic_year_id', 'campus_id', 'grade_level_id', 'section_id',
    ];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = [
        'student',
        'academicYear',
        'academicTerm',
        'campus',
        'gradeLevel',
        'section',
        'requirementItems.requirement',
    ];

    protected string $defaultSortBy = 'id';

    protected string $defaultSortDir = 'desc';

    public function __construct(
        private readonly EnrollmentRepositoryInterface $repo,
        private readonly StudentRepositoryInterface $studentRepo,
        private readonly SystemSettingRepositoryInterface $settings,
        private readonly EnrollmentRequirementRepositoryInterface $requirementRepo,
        private readonly EnrollmentTransferRepositoryInterface $transferRepo,
        private readonly EnrollmentCapacityOverrideRepositoryInterface $capacityRepo,
        private readonly AcademicOperationsService $academicOperations,
    ) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }

    /**
     * The configurable enrollment types (from master data).
     *
     * @return list<array{code: string, name: string}>
     */
    public function enrollmentTypes(): array
    {
        return $this->masterDataOptions('enrollment-type', EnrollmentType::cases());
    }

    /**
     * The configurable enrollment statuses (from master data).
     *
     * @return list<array{code: string, name: string}>
     */
    public function enrollmentStatuses(): array
    {
        return $this->masterDataOptions('enrollment-status', EnrollmentStatus::cases());
    }

    /**
     * @param  array<int, \BackedEnum>  $defaults
     * @return list<array{code: string, name: string}>
     */
    protected function masterDataOptions(string $type, array $defaults): array
    {
        $entries = MasterData::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['code', 'name']);

        if ($entries->isEmpty()) {
            return array_map(
                static fn ($case) => ['code' => $case->value, 'name' => $case->label()],
                $defaults
            );
        }

        return $entries->map(static fn ($entry): array => [
            'code' => $entry->code ?: Str::slug($entry->name),
            'name' => $entry->name,
        ])->values()->all();
    }

    /**
     * Create a new enrollment, then snapshot and prepare its requirements.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $data = $this->hydrateAssignment($data);

        $overrideReason = ($data['capacity_override_reason'] ?? $data['capacity_override'] ?? null)
            ? trim((string) ($data['capacity_override_reason'] ?? $data['capacity_override']))
            : null;

        unset($data['capacity_override_reason'], $data['capacity_override']);

        $this->assertAssignmentValid($data);

        $data = $this->assignNumbers($data);

        $data['status'] = EnrollmentStatus::DRAFT->value;
        $data['is_active'] = true;

        return DB::transaction(function () use ($data, $overrideReason): Model {
            $enrollment = $this->repo->create($data);

            $this->syncRequirements($enrollment);
            $this->syncSectionCapacity($enrollment, $overrideReason);

            return $enrollment->load($this->with);
        });
    }

    /**
     * Update an applicable enrollment record.
     *
     * @param  Enrollment  $model
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        if (! in_array($model->status, [EnrollmentStatus::DRAFT->value, EnrollmentStatus::PENDING->value], true)) {
            throw ApiException::unprocessable('Enrollment records can only be edited while in Draft or Pending status.');
        }

        $data = $this->hydrateAssignment($data);

        $overrideReason = ($data['capacity_override_reason'] ?? $data['capacity_override'] ?? null)
            ? trim((string) ($data['capacity_override_reason'] ?? $data['capacity_override']))
            : null;

        unset($data['capacity_override_reason'], $data['capacity_override']);

        $this->assertAssignmentValid($data, $model);

        return DB::transaction(function () use ($model, $data, $overrideReason): Model {
            $updated = $this->repo->update($model, $data);

            $this->syncRequirements($updated);
            $this->syncSectionCapacity($updated, $overrideReason);

            return $updated->load($this->with);
        });
    }

    /**
     * Soft-delete an enrollment.
     *
     * Only Draft and Pending enrollments may be removed; terminal and
     * officially-enrolled records are never physically deleted.
     *
     * @param  Enrollment  $model
     */
    public function delete(Model $model): bool
    {
        if (! in_array($model->status, [EnrollmentStatus::DRAFT->value, EnrollmentStatus::PENDING->value], true)) {
            throw ApiException::unprocessable('Only Draft or Pending enrollments can be deleted.');
        }

        return parent::delete($model);
    }

    /**
     * Force-delete an enrollment.
     *
     * @param  Enrollment  $model
     */
    public function forceDelete(Model $model): bool
    {
        throw ApiException::forbidden('Enrollment records cannot be permanently deleted.');
    }

    /**
     * Search students by name / identifiers for the enrollment intake step.
     *
     * @return Collection<int, Student>
     */
    public function searchStudents(string $term, int $limit = 25): Collection
    {
        if ($term === '') {
            return new Collection;
        }

        return $this->studentRepo->query()
            ->where(function (Builder $q) use ($term): void {
                $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('middle_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('student_number', 'like', "%{$term}%")
                    ->orWhere('lrn', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            })
            ->orderBy('last_name')
            ->limit($limit)
            ->get();
    }

    /**
     * Aggregate enrollment statistics.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function statistics(array $filters = []): array
    {
        $query = $this->repo->query();

        if (! empty($filters['academic_year_id'])) {
            $query->where('academic_year_id', $filters['academic_year_id']);
        }

        if (! empty($filters['campus_id'])) {
            $query->where('campus_id', $filters['campus_id']);
        }

        $rows = (clone $query)->get(['id', 'status', 'grade_level_id', 'section_id', 'enrollment_type', 'academic_year_id', 'campus_id']);

        return [
            'total' => $rows->count(),
            'active' => $rows->whereIn('status', EnrollmentStatus::activeStatuses())->count(),
            'officially_enrolled' => $rows->where('status', EnrollmentStatus::OFFICIALLY_ENROLLED->value)->count(),
            'per_status' => $rows->groupBy('status')->map->count(),
            'per_grade_level' => $rows->groupBy('grade_level_id')->map->count(),
            'per_campus' => $rows->groupBy('campus_id')->map->count(),
            'per_type' => $rows->groupBy('enrollment_type')->map->count(),
        ];
    }

    /**
     * Move an enrollment from Draft to Pending (submit).
     */
    public function submit(Enrollment $enrollment): Enrollment
    {
        if ($enrollment->status !== EnrollmentStatus::DRAFT->value) {
            throw ApiException::unprocessable('Only Draft enrollments can be submitted.');
        }

        return $this->saveTransition($enrollment, [
            'status' => EnrollmentStatus::PENDING->value,
            'enrollment_date' => $enrollment->enrollment_date ?: now()->toDateString(),
        ]);
    }

    /**
     * Verify the submitted requirements; an enrollment moves to Verified when
     * every required item is satisfied, otherwise to Requirements Incomplete.
     */
    public function verify(Enrollment $enrollment): Enrollment
    {
        $this->assertNotTerminal($enrollment, 'become verified');

        $satisfied = $enrollment->allRequirementsSatisfied();

        return $this->saveTransition($enrollment, [
            'status' => $satisfied
                ? EnrollmentStatus::VERIFIED->value
                : EnrollmentStatus::REQUIREMENTS_INCOMPLETE->value,
        ]);
    }

    /**
     * Move a Verified enrollment to Approved.
     */
    public function approve(Enrollment $enrollment): Enrollment
    {
        if (! in_array($enrollment->status, [
            EnrollmentStatus::VERIFIED->value,
            EnrollmentStatus::FOR_APPROVAL->value,
        ], true)) {
            throw ApiException::unprocessable('Only Verified enrollments can be approved.');
        }

        return $this->saveTransition($enrollment, [
            'status' => EnrollmentStatus::APPROVED->value,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject an enrollment with a required reason.
     */
    public function reject(Enrollment $enrollment, ?string $reason = null): Enrollment
    {
        $this->assertNotTerminal($enrollment, 'become');

        if (blank($reason)) {
            throw ApiException::unprocessable('A rejection reason is required.');
        }

        return $this->saveTransition($enrollment, [
            'status' => EnrollmentStatus::REJECTED->value,
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => trim($reason),
        ]);
    }

    /**
     * Mark an Approved enrollment as Officially Enrolled.
     *
     * @param  string|null  $date  The official enrollment date.
     * @param  string|null  $capacityOverrideReason  When provided, records the
     *                                               reason an over-capacity
     *                                               placement is permitted.
     */
    public function enroll(Enrollment $enrollment, ?string $date = null, ?string $capacityOverrideReason = null): Enrollment
    {
        if (! in_array($enrollment->status, [
            EnrollmentStatus::APPROVED->value,
            EnrollmentStatus::FOR_APPROVAL->value,
        ], true)) {
            throw ApiException::unprocessable('Only Approved enrollments can be officially enrolled.');
        }

        if (! $enrollment->allRequirementsSatisfied()) {
            throw ApiException::unprocessable('All required documents must be verified before the student can be officially enrolled.');
        }

        $this->syncSectionCapacity($enrollment, $capacityOverrideReason);

        $completed = $this->saveTransition($enrollment, [
            'status' => EnrollmentStatus::OFFICIALLY_ENROLLED->value,
            'date_enrolled' => $date ?: now()->toDateString(),
            'approved_by' => $enrollment->approved_by ?: auth()->id(),
            'approved_at' => $enrollment->approved_at ?: now(),
        ]);

        $this->academicOperations->materializeEnrollment($completed);

        return $completed;
    }

    /**
     * Withdraw an enrollment. Officially enrolled students must be transferred
     * instead of withdrawn.
     */
    public function withdraw(Enrollment $enrollment, ?string $reason = null): Enrollment
    {
        if ($enrollment->status === EnrollmentStatus::OFFICIALLY_ENROLLED->value) {
            throw ApiException::unprocessable('Officially enrolled students cannot be withdrawn; process a transfer instead.');
        }

        $this->assertNotTerminal($enrollment, 'become');

        return $this->saveTransition($enrollment, [
            'status' => EnrollmentStatus::WITHDRAWN->value,
            'withdrawn_by' => auth()->id(),
            'withdrawn_at' => now(),
            'transfer_reason' => $reason ?: $enrollment->transfer_reason,
            'transfer_remarks' => $enrollment->transfer_remarks,
        ]);
    }

    /**
     * Cancel an early-stage enrollment.
     */
    public function cancel(Enrollment $enrollment, ?string $reason = null): Enrollment
    {
        if (! in_array($enrollment->status, [
            EnrollmentStatus::DRAFT->value,
            EnrollmentStatus::PENDING->value,
            EnrollmentStatus::FOR_VERIFICATION->value,
            EnrollmentStatus::REQUIREMENTS_INCOMPLETE->value,
        ], true)) {
            throw ApiException::unprocessable('This enrollment can no longer be cancelled.');
        }

        return $this->saveTransition($enrollment, [
            'status' => EnrollmentStatus::CANCELLED->value,
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
            'cancellation_reason' => $reason ?: null,
        ]);
    }

    /**
     * Process a transfer for an enrolled (approved or officially enrolled) student.
     *
     * @param  array<string, mixed>  $data
     */
    public function transfer(Enrollment $enrollment, array $data): Enrollment
    {
        if (! in_array($enrollment->status, [
            EnrollmentStatus::APPROVED->value,
            EnrollmentStatus::OFFICIALLY_ENROLLED->value,
        ], true)) {
            throw ApiException::unprocessable('Only Approved or Officially Enrolled students can be transferred.');
        }

        $date = Carbon::parse($data['transfer_date'] ?? now()->toDateString());

        $target = [
            'to_campus_name' => null,
            'to_grade_level_name' => null,
            'to_section_name' => null,
        ];

        if (! empty($data['to_campus_id'])) {
            $target['to_campus_name'] = Campus::query()->find($data['to_campus_id'])?->name;
        }

        if (! empty($data['to_grade_level_id'])) {
            $target['to_grade_level_name'] = GradeLevel::query()->find($data['to_grade_level_id'])?->name;
            if (! empty($data['to_section_id'])) {
                $target['to_section_name'] = Section::query()->find($data['to_section_id'])?->name;
            }
        }

        $this->transferRepo->create([
            'enrollment_id' => $enrollment->id,
            'transfer_type' => $data['transfer_type'] ?? 'within-school',
            'from_campus_name' => $enrollment->campus?->name,
            'from_grade_level_name' => $enrollment->gradeLevel?->name,
            'from_section_name' => $enrollment->section?->name,
            ...$target,
            'destination' => $data['destination'] ?? $data['transfer_destination'] ?? null,
            'transfer_date' => $date->toDateString(),
            'reason' => $data['reason'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'processed_by' => auth()->id(),
        ]);

        $updates = [
            'status' => EnrollmentStatus::TRANSFERRED->value,
            'transfer_date' => $date->toDateString(),
            'transfer_type' => $data['transfer_type'] ?? 'within-school',
            'transfer_destination' => $data['transfer_destination'] ?? $target['to_campus_name'] ?? null,
            'transfer_destination_school' => $data['transfer_destination_school'] ?? null,
            'transfer_reason' => $data['reason'] ?? null,
            'transfer_remarks' => $data['remarks'] ?? null,
        ];

        if (! empty($data['to_campus_id'])) {
            $updates['campus_id'] = $data['to_campus_id'];
        }

        if (! empty($data['to_grade_level_id'])) {
            $updates['grade_level_id'] = $data['to_grade_level_id'];
        }

        if (! empty($data['to_section_id'])) {
            $updates['section_id'] = $data['to_section_id'];
        }

        return $this->saveTransition($enrollment, $updates);
    }

    /**
     * Create a capacity override for an enrollment on a section.
     */
    public function overrideCapacity(Enrollment $enrollment, string $reason): Enrollment
    {
        $section = $enrollment->section;

        if (! $section) {
            throw ApiException::unprocessable('The enrollment has no section assigned.');
        }

        $this->capacityRepo->updateOrCreate(
            ['enrollment_id' => $enrollment->id, 'section_id' => $section->id],
            ['reason' => trim($reason), 'overridden_by' => auth()->id()]
        );

        return $enrollment->load($this->with);
    }

    /**
     * Ensure the enrollment number fields are generated and unique.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function assignNumbers(array $data): array
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                if (blank($data['enrollment_number'] ?? null)) {
                    $data['enrollment_number'] = $this->generateNumber('enrollment_number', $data['academic_year_id']);
                }

                if (blank($data['reference_number'] ?? null)) {
                    $data['reference_number'] = $this->generateNumber('reference_number', $data['academic_year_id']);
                }

                return $data;
            } catch (Throwable) {
                unset($data['enrollment_number'], $data['reference_number']);
            }
        }

        throw ApiException::unprocessable('Unable to generate a unique enrollment number.');
    }

    /**
     * Generate a number from the configured format.
     */
    protected function generateNumber(string $field, int $academicYearId): string
    {
        $key = $field === 'reference_number'
            ? 'enrollment.reference_number_format'
            : 'enrollment.enrollment_number_format';

        $format = $this->settings->value($key, $field === 'reference_number' ? 'KXN-EN-{YEAR}-{SEQ:6}' : 'ENR-{YEAR}-{SEQ:6}');

        $year = (int) (AcademicYear::query()->find($academicYearId)?->code ?? now()->year);

        $seq = ((int) $this->repo->query()->max('id')) + 1;

        $number = preg_replace_callback(
            '/\{SEQ:(\d+)\}/',
            static fn (array $match): string => str_pad((string) $seq, (int) $match[1], '0', STR_PAD_LEFT),
            (string) $format
        );

        return str_replace('{YEAR}', (string) $year, (string) $number);
    }

    /**
     * Fill the display-friendly assignment columns from their ids.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function hydrateAssignment(array $data): array
    {
        $data['academic_term_id'] = $data['academic_term_id'] ?? null;
        $data['section_id'] = $data['section_id'] ?? null;
        $data['enrollment_date'] = $data['enrollment_date'] ?? now()->toDateString();
        $data['enrollment_type'] = $data['enrollment_type'] ?? EnrollmentType::NEW_STUDENT->value;

        return $data;
    }

    /**
     * Validate the assignment context and duplicates.
     *
     * @param  array<string, mixed>  $data
     */
    protected function assertAssignmentValid(array $data, ?Enrollment $excluding = null): void
    {
        $year = AcademicYear::query()->findOrFail($data['academic_year_id']);
        if (! $year->is_active) {
            throw ApiException::unprocessable('The selected Academic Year is inactive.');
        }

        $campus = Campus::query()->findOrFail($data['campus_id']);
        if (! $campus->is_active) {
            throw ApiException::unprocessable('The selected Campus is inactive.');
        }

        $gradeLevel = GradeLevel::query()->findOrFail($data['grade_level_id']);
        if (! $gradeLevel->is_active) {
            throw ApiException::unprocessable('The selected Grade Level is inactive.');
        }

        if (! empty($data['curriculum_program_id'])) {
            $program = CurriculumProgram::query()->findOrFail($data['curriculum_program_id']);
            if ((int) $program->academic_year_id !== (int) $data['academic_year_id'] || ! $program->includesGradeLevel((int) $data['grade_level_id'])) {
                throw ApiException::unprocessable('The selected curriculum program does not apply to this enrollment.');
            }
            if (! empty($program->clusters) && blank($data['program_cluster'] ?? null)) {
                throw ApiException::unprocessable('A curriculum program cluster is required for this enrollment.');
            }
            if (! empty($data['program_cluster']) && ! in_array($data['program_cluster'], $program->clusters ?? [], true)) {
                throw ApiException::unprocessable('The selected program cluster is not available in the curriculum program.');
            }
        }

        if (! empty($data['academic_term_id'])) {
            $term = AcademicTerm::query()->findOrFail($data['academic_term_id']);
            if (! $term->is_active) {
                throw ApiException::unprocessable('The selected Academic Term is inactive.');
            }
            if ((int) $term->academic_year_id !== (int) $data['academic_year_id']) {
                throw ApiException::unprocessable('The selected Academic Term does not belong to the selected Academic Year.');
            }
        }

        if (! empty($data['section_id'])) {
            $section = Section::query()->findOrFail($data['section_id']);
            if (! $section->is_active) {
                throw ApiException::unprocessable('The selected Section is inactive.');
            }
            if ((int) $section->grade_level_id !== (int) $data['grade_level_id']) {
                throw ApiException::unprocessable('The selected Section does not belong to the selected Grade Level.');
            }
        }

        $allowMultiple = filter_var(
            $this->settings->value('enrollment.allow_multiple_per_year_branch', false),
            FILTER_VALIDATE_BOOLEAN
        );

        $query = $this->repo->query()
            ->where('student_id', $data['student_id'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->whereIn('status', EnrollmentStatus::activeStatuses());

        if (! $allowMultiple) {
            $existing = (clone $query)->where('campus_id', $data['campus_id']);
            if ($excluding) {
                $existing->whereKeyNot($excluding->id);
            }
            if ($existing->exists()) {
                throw ApiException::conflict('The student already has an active enrollment for this Academic Year and Campus.');
            }
        }

        $sameGrade = (clone $query)->where('grade_level_id', $data['grade_level_id']);
        if ($excluding) {
            $sameGrade->whereKeyNot($excluding->id);
        }
        if ($sameGrade->exists()) {
            throw ApiException::conflict('The student already has an active enrollment for this Grade Level and Academic Year.');
        }
    }

    /**
     * Check section capacity for an enrollment, allowing a recorded override.
     */
    protected function syncSectionCapacity(Enrollment $enrollment, ?string $overrideReason = null): void
    {
        $section = $enrollment->section;

        if (! $section || ! $section->max_capacity) {
            return;
        }

        $occupied = $this->repo->query()
            ->where('section_id', $section->id)
            ->where('academic_year_id', $enrollment->academic_year_id)
            ->whereIn('status', EnrollmentStatus::occupancyStatuses())
            ->whereKeyNot($enrollment->id ?? 0)
            ->count();

        if ($occupied < $section->max_capacity) {
            return;
        }

        $hasOverride = $this->capacityRepo->query()
            ->where('enrollment_id', $enrollment->id)
            ->where('section_id', $section->id)
            ->exists();

        if ($hasOverride || $overrideReason !== null) {
            if (! $hasOverride) {
                $this->capacityRepo->updateOrCreate(
                    ['enrollment_id' => $enrollment->id, 'section_id' => $section->id],
                    ['reason' => $overrideReason, 'overridden_by' => auth()->id()]
                );
            }

            return;
        }

        throw ApiException::conflict('The selected section has reached its maximum capacity. Override capacity to continue.');
    }

    /**
     * Keep the requirement items in sync with the applicable, active requirements.
     */
    public function syncRequirements(Enrollment $enrollment): void
    {
        /** @var Collection<int, EnrollmentRequirement> $applicable */
        $applicable = $this->requirementRepo->query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $current = $enrollment->requirementItems()->get()->keyBy('enrollment_requirement_id');

        foreach ($applicable as $requirement) {
            if (! $requirement->appliesTo($enrollment) || $current->has($requirement->id)) {
                continue;
            }

            $enrollment->requirementItems()->create([
                'enrollment_requirement_id' => $requirement->id,
                'status' => RequirementItemStatus::NOT_SUBMITTED->value,
            ]);
        }

        foreach ($current as $item) {
            if ($applicable->contains('id', $item->enrollment_requirement_id)) {
                continue;
            }

            $item->documents()->delete();
            $item->delete();
        }
    }

    /**
     * Persist a workflow transition and dispatch the status-change event.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function saveTransition(Enrollment $enrollment, array $attributes): Enrollment
    {
        $oldStatus = $enrollment->status;

        $model = $this->repo->update($enrollment, $attributes);

        if ($model->status !== $oldStatus) {
            event(new EnrollmentStatusChanged($model, $oldStatus, auth()->user()));
        }

        return $model->load($this->with);
    }

    /**
     * Revert an officially enrolled record back to Approved.
     */
    public function revert(Enrollment $enrollment): Enrollment
    {
        if ($enrollment->status !== EnrollmentStatus::OFFICIALLY_ENROLLED->value) {
            throw ApiException::unprocessable('Only officially enrolled records can be reverted.');
        }

        return $this->saveTransition($enrollment, [
            'status' => EnrollmentStatus::APPROVED->value,
            'date_enrolled' => null,
        ]);
    }

    /**
     * @throws ApiException
     */
    protected function assertNotTerminal(Enrollment $enrollment, string $verb = 'continue'): void
    {
        if (in_array($enrollment->status, EnrollmentStatus::terminalStatuses(), true)) {
            throw ApiException::unprocessable("This enrollment is already in a terminal status and cannot {$verb}.");
        }
    }
}
