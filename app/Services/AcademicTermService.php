<?php

namespace App\Services;

use App\Enums\AcademicCalendarType;
use App\Exceptions\ApiException;
use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Repositories\Contracts\AcademicTermRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class AcademicTermService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['name', 'code'];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'name', 'code'];

    public function __construct(private readonly AcademicTermRepositoryInterface $repo) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }

    /**
     * Create an academic term after validating overlap, term count and the
     * single-active rule.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $this->validateOverlap(null, $data);
        $this->validateTermCount($data['academic_year_id'], null);

        $term = parent::create($data);

        $this->enforceSingularActive($term);

        return $term;
    }

    /**
     * Update an academic term after validating overlap, term count and the
     * single-active rule.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        $yearId = $data['academic_year_id'] ?? $model->academic_year_id;

        $this->validateOverlap($model, $data);
        $this->validateTermCount($yearId, $model);

        $term = parent::update($model, $data);

        $this->enforceSingularActive($term);

        return $term;
    }

    /**
     * Only one academic term can be active at a time.
     */
    private function enforceSingularActive(AcademicTerm $term): void
    {
        if (! $term->is_active) {
            return;
        }

        AcademicTerm::query()
            ->whereKeyNot($term->getKey())
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    /**
     * Academic terms within the same academic year cannot overlap.
     *
     * @param  array<string, mixed>  $data
     */
    private function validateOverlap(?AcademicTerm $except, array $data): void
    {
        $overlapExists = AcademicTerm::query()
            ->where('academic_year_id', $data['academic_year_id'])
            ->when($except !== null, fn (Builder $query) => $query->whereKeyNot($except->getKey()))
            ->where('start_date', '<=', $data['end_date'])
            ->where('end_date', '>=', $data['start_date'])
            ->exists();

        if ($overlapExists) {
            throw ApiException::unprocessable('The term dates overlap with an existing term in the same academic year.');
        }
    }

    /**
     * The number of terms must match the academic year's calendar type unless
     * the type is Custom.
     */
    private function validateTermCount(int $academicYearId, ?AcademicTerm $except): void
    {
        $year = AcademicYear::query()->findOrFail($academicYearId);
        $type = AcademicCalendarType::tryFrom($year->calendar_type);

        if ($type === null || $type->expectedTermCount() === null) {
            return;
        }

        $existing = AcademicTerm::query()
            ->where('academic_year_id', $academicYearId)
            ->when($except !== null, fn (Builder $query) => $query->whereKeyNot($except->getKey()))
            ->count();

        $expected = $type->expectedTermCount();

        if ($existing + 1 > $expected) {
            throw ApiException::unprocessable(
                "A {$type->label()} academic year can only have {$expected} terms."
            );
        }
    }
}
