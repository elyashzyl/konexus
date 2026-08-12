<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Exceptions\ApiException;
use App\Models\AcademicClass;
use App\Models\AcademicClassStudent;
use App\Models\Enrollment;
use App\Repositories\Contracts\AcademicClassRepositoryInterface;
use App\Repositories\Contracts\AcademicClassStudentRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Manages flassrooms and their student rosters.
 *
 * A class groups members of the same section within an academic period.
 * Members can be assigned manually or synchronized from the enrolled
 * students of the same section.
 */
class AcademicClassService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['name'];

    protected array $searchableRelations = [
        'section' => ['name'],
        'gradeLevel' => ['name'],
    ];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'name'];

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
        'adviser.employee',
        'activeMembers.student',
    ];

    public function __construct(
        private readonly AcademicClassRepositoryInterface $repo,
        private readonly AcademicClassStudentRepositoryInterface $memberRepo,
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

        foreach (['academic_year_id', 'academic_term_id', 'campus_id', 'grade_level_id', 'section_id'] as $column) {
            if ($request->has($column)) {
                $filters[$column] = $request->input($column);
            }
        }

        return $filters;
    }

    /**
     * Create a class, guarding against duplicate section/period combos.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $this->assertNoDuplicate(null, $data);

        return parent::create($data);
    }

    /**
     * Update a class while keeping the uniqueness guarantee.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        $merged = array_merge($model->only(['academic_year_id', 'academic_term_id', 'campus_id', 'grade_level_id', 'section_id']), $data);

        $this->assertNoDuplicate($model, $merged);

        return parent::update($model, $data);
    }

    /**
     * Only one class per section + academic period.
     *
     * @param  array<string, mixed>  $data
     */
    private function assertNoDuplicate(?AcademicClass $except, array $data): void
    {
        $duplicate = AcademicClass::query()
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('academic_term_id', $data['academic_term_id'] ?? null)
            ->where('campus_id', $data['campus_id'] ?? null)
            ->where('section_id', $data['section_id'])
            ->when($except !== null, fn (Builder $query) => $query->whereKeyNot($except->getKey()))
            ->exists();

        if ($duplicate) {
            throw ApiException::unprocessable('A class already exists for this section in the given academic period.');
        }
    }

    /**
     * The active membership rows of the supplied class.
     *
     * @return \App\Models\AcademicClassStudent[]|\Illuminate\Database\Eloquent\Collection<int, AcademicClassStudent>
     */
    public function members(AcademicClass $class, bool $includeInactive = false)
    {
        $query = $this->memberRepo->query()
            ->with(['student', 'enrollment'])
            ->where('academic_class_id', $class->id);

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        return $query->orderBy('created_at')->get();
    }

    /**
     * Assign a student to a class.
     *
     * @param  array<string, mixed>  $data
     * @return array{member: AcademicClassStudent, action: string}
     */
    public function assignMember(AcademicClass $class, array $data): array
    {
        $studentId = (int) $data['student_id'];

        $existing = $this->memberRepo->findBy([
            'academic_class_id' => $class->id,
            'student_id' => $studentId,
        ], ['*']);

        if ($existing) {
            if ($existing->is_active) {
                throw ApiException::conflict('The student is already a member of this class.');
            }

            $this->memberRepo->update($existing, [
                'is_active' => true,
                'source' => AcademicClassStudent::SOURCE_MANUAL,
                'remarks' => $data['remarks'] ?? null,
            ]);

            return ['member' => $existing->load(['student', 'enrollment']), 'action' => 'reactivated'];
        }

        $enrollment = $this->findEnrollment($class, $studentId);

        $member = $this->memberRepo->create([
            'academic_class_id' => $class->id,
            'student_id' => $studentId,
            'enrollment_id' => $enrollment?->id,
            'source' => AcademicClassStudent::SOURCE_MANUAL,
            'academic_status' => $enrollment?->status ?? EnrollmentStatus::PENDING->value,
            'remarks' => $data['remarks'] ?? null,
            'is_active' => true,
        ]);

        return ['member' => $member->load(['student', 'enrollment']), 'action' => 'added'];
    }

    /**
     * Remove a student from a class (soft-via is_active).
     */
    public function unassignMember(AcademicClass $class, int $studentId, ?string $remarks = null): ?AcademicClassStudent
    {
        $member = $this->memberRepo->findBy([
            'academic_class_id' => $class->id,
            'student_id' => $studentId,
        ]);

        if ($member === null || ! $member->is_active) {
            return null;
        }

        $this->memberRepo->update($member, [
            'is_active' => false,
            'remarks' => $remarks ?? 'Removed from class roster.',
        ]);

        return $member;
    }

    /**
     * Rebuild the roster of a class from students who are enrolled and
     * assigned to the section of the class in the same academic context.
     *
     * Non-official members are dropped; newly matched enrollments are added.
     *
     * @return array{added: int, removed: int, total: int}
     */
    public function syncFromEnrollments(AcademicClass $class): array
    {
        return DB::transaction(function () use ($class): array {
            $enrolled = Enrollment::query()
                ->where('academic_year_id', $class->academic_year_id)
                ->when($class->academic_term_id, fn (Builder $query) => $query->where('academic_term_id', $class->academic_term_id))
                ->where('campus_id', $class->campus_id ?? null)
                ->where('grade_level_id', $class->grade_level_id)
                ->where('section_id', $class->section_id)
                ->whereIn('status', EnrollmentStatus::occupancyStatuses())
                ->get(['id', 'student_id', 'status']);

        $enrolledStudentIds = $enrolled->pluck('student_id');
        $currentMembers = $this->memberRepo->query()
            ->where('academic_class_id', $class->id)
            ->where('is_active', true)
            ->get(['id', 'student_id', 'enrollment_id']);

        $added = 0;
        $removed = 0;

        foreach ($enrolledStudentIds as $studentId) {
            $member = $currentMembers->firstWhere('student_id', $studentId);

            if ($member !== null) {
                continue;
            }

            $matching = $enrolled->firstWhere('student_id', $studentId);

            $this->memberRepo->create([
                'academic_class_id' => $class->id,
                'student_id' => $studentId,
                'enrollment_id' => $matching?->id,
                'source' => AcademicClassStudent::SOURCE_ENROLLMENT,
                'academic_status' => $matching?->status,
                'is_active' => true,
            ]);

            $added++;
        }

        foreach ($currentMembers as $member) {
            if (! $enrolledStudentIds->contains($member->student_id)) {
                if ($member->source === AcademicClassStudent::SOURCE_MANUAL) {
                    continue;
                }
                $this->memberRepo->update($member, ['is_active' => false]);
                $removed++;
            }
        }

        return [
                'added' => $added,
                'removed' => $removed,
                'total' => $this->memberRepo->query()
                    ->where('academic_class_id', $class->id)
                    ->where('is_active', true)
                    ->count(),
            ];
        });
    }

    /**
     * The matching official enrollment of a student in the class' context.
     */
    protected function findEnrollment(AcademicClass $class, int $studentId): ?Enrollment
    {
        return Enrollment::query()
            ->where('academic_year_id', $class->academic_year_id)
            ->when($class->academic_term_id, fn (Builder $query) => $query->where('academic_term_id', $class->academic_term_id))
            ->where('campus_id', $class->campus_id ?? null)
            ->where('grade_level_id', $class->grade_level_id)
            ->where('section_id', $class->section_id)
            ->where('student_id', $studentId)
            ->whereIn('status', EnrollmentStatus::activeStatuses())
            ->latest('id')
            ->first();
    }
}