<?php

namespace App\Services;

use App\Enums\AcademicCalendarType;
use App\Exceptions\ApiException;
use App\Models\AcademicYear;
use App\Repositories\Contracts\AcademicYearRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class AcademicYearService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['name', 'code'];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'name', 'code'];

    public function __construct(private readonly AcademicYearRepositoryInterface $repo) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }

    /**
     * Create an academic year and keep the active flag singular.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $year = parent::create($data);

        $this->enforceSingularActive($year);

        return $year;
    }

    /**
     * Update an academic year, validating calendar changes against its terms.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        if (isset($data['calendar_type'])) {
            $this->validateCalendarChange($model, $data['calendar_type']);
        }

        $year = parent::update($model, $data);

        $this->enforceSingularActive($year);

        return $year;
    }

    /**
     * Only one academic year can be active at a time.
     */
    private function enforceSingularActive(AcademicYear $year): void
    {
        if (! $year->is_active) {
            return;
        }

        AcademicYear::query()
            ->whereKeyNot($year->getKey())
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }

    /**
     * Prevent switching to a calendar type that cannot hold the existing terms.
     */
    private function validateCalendarChange(AcademicYear $year, string $calendarType): void
    {
        $type = AcademicCalendarType::tryFrom($calendarType);

        if ($type === null || $type->expectedTermCount() === null) {
            return;
        }

        $termCount = $year->terms()->count();
        $expected = $type->expectedTermCount();

        if ($termCount > $expected) {
            throw ApiException::unprocessable(
                "Cannot change the calendar type to {$type->label()}. The academic year already has {$termCount} terms, which exceeds the allowed {$expected}."
            );
        }
    }
}
