<?php

namespace App\Services;

use App\Enums\RoundingRule;
use App\Exceptions\ApiException;
use App\Models\GradeScale;
use App\Models\GradeScaleEntry;
use App\Repositories\Contracts\GradeScaleEntryRepositoryInterface;
use App\Repositories\Contracts\GradeScaleRepositoryInterface;
use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Grade scales define the numeric range and rounding rules used to turn a raw
 * grade into a published final grade, together with the letter/remark bands
 * that label a numeric grade. Only one active scale can be marked as default
 * for the current academic year.
 */
class GradeScaleService extends CrudService
{
    /**
     * Columns included in free-text search.
     *
     * @var list<string>
     */
    protected array $searchable = ['name', 'code'];

    protected array $sortable = ['id', 'created_at', 'updated_at', 'name', 'code'];

    /**
     * Relationships eager loaded with every record.
     *
     * @var list<string>
     */
    protected array $with = ['academicYear', 'entries'];

    public function __construct(
        private readonly GradeScaleRepositoryInterface $repo,
        private readonly GradeScaleEntryRepositoryInterface $entryRepo,
    ) {}

    /**
     * The underlying repository for this service.
     */
    protected function repository(): RepositoryInterface
    {
        return $this->repo;
    }

    /**
     * Create a grade scale and enforce the single-default rule.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        $scale = parent::create($data);

        $this->enforceSingleDefault($scale);

        return $scale;
    }

    /**
     * Update a grade scale, enforcing the single-default rule.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        /** @var GradeScale $model */
        $scale = parent::update($model, $data);

        $this->enforceSingleDefault($scale);

        return $scale;
    }

    /**
     * Resolve the grade scale currently in effect for a context.
     */
    public function activeScale(?int $academicYearId = null): ?GradeScale
    {
        return $this->repo->query()
            ->when($academicYearId, fn ($query) => $query->where('academic_year_id', $academicYearId))
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    /**
     * Round a raw grade to the scale's decimal precision using its rounding
     * rule.
     */
    public function roundGrade(GradeScale $scale, mixed $raw): float
    {
        $precision = $scale->decimal_precision ?? 2;
        $value = (float) $raw;

        $rounded = match (RoundingRule::tryFrom((string) $scale->rounding)) {
            RoundingRule::HALF_UP => round($value, $precision, PHP_ROUND_HALF_UP),
            RoundingRule::CEIL => $this->ceil($value, $precision),
            RoundingRule::FLOOR => $this->floor($value, $precision),
            default => round($value, $precision, PHP_ROUND_HALF_EVEN),
        };

        return min($scale->max_grade, max($scale->min_grade, $rounded));
    }

    /**
     * The final grade for a raw grade under the active scale.
     */
    public function finalizeGrade(mixed $raw, ?GradeScale $scale = null): array
    {
        $scale ??= $this->activeScale();

        if ($raw === null || $raw === '') {
            return ['final_grade' => null, 'label' => null, 'remarks' => null, 'is_passing' => null];
        }

        if ($scale === null) {
            return ['final_grade' => round((float) $raw, 2), 'label' => null, 'remarks' => null, 'is_passing' => null];
        }

        $finalGrade = $this->roundGrade($scale, $raw);
        $entry = $scale->entryFor($finalGrade);

        return [
            'final_grade' => $finalGrade,
            'label' => $entry?->label,
            'remarks' => $entry?->remarks,
            'is_passing' => $entry?->is_passing,
        ];
    }

    /**
     * List the bands of a grade scale.
     *
     * @return \App\Models\GradeScaleEntry[]|\Illuminate\Database\Eloquent\Collection<int, GradeScaleEntry>
     */
    public function entries(int $scaleId)
    {
        return $this->entryRepo->query()
            ->where('grade_scale_id', $scaleId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Add a band to a grade scale, validating it sits within the numeric range
     * of the scale and does not overlap an existing band.
     *
     * @param  array<string, mixed>  $data
     */
    public function addEntry(GradeScale $scale, array $data): GradeScaleEntry
    {
        $this->assertEntryRange($scale, $data);

        return $this->entryRepo->create(array_merge($data, [
            'grade_scale_id' => $scale->id,
            'is_active' => $data['is_active'] ?? true,
        ]));
    }

    /**
     * Update a band of a grade scale.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateEntry(GradeScaleEntry $entry, array $data): GradeScaleEntry
    {
        $merged = array_merge($entry->only(['grade_scale_id', 'min_grade', 'max_grade']), $data);

        $this->assertEntryRange($entry->gradeScale, $merged, $entry);

        return $this->entryRepo->update($entry, $data);
    }

    /**
     * Soft-delete a band.
     */
    public function deleteEntry(GradeScaleEntry $entry): bool
    {
        return $this->entryRepo->delete($entry);
    }

    /**
     * A band must not exceed the scale bounds or overlap a sibling band.
     *
     * @param  array<string, mixed>  $data
     */
    private function assertEntryRange(GradeScale $scale, array $data, ?GradeScaleEntry $except = null): void
    {
        $min = (float) ($data['min_grade'] ?? 0);
        $max = (float) ($data['max_grade'] ?? 0);

        if ($min > $max) {
            throw ApiException::unprocessable('The minimum grade cannot exceed the maximum grade of a band.');
        }

        if ($min < (float) $scale->min_grade || $max > (float) $scale->max_grade) {
            throw ApiException::unprocessable("The band must fall within the scale range ({$scale->min_grade} – {$scale->max_grade}).");
        }

        $overlap = $this->entryRepo->query()
            ->where('grade_scale_id', $scale->id)
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->where('min_grade', '<=', $max)
            ->where('max_grade', '>=', $min)
            ->exists();

        if ($overlap) {
            throw ApiException::unprocessable('The band overlaps with an existing band of this grade scale.');
        }
    }

    /**
     * Only one active scale can be default at a time.
     */
    private function enforceSingleDefault(GradeScale $scale): void
    {
        if (! $scale->is_default || ! $scale->is_active) {
            return;
        }

        GradeScale::query()
            ->whereKeyNot($scale->getKey())
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    /**
     * Round a value up to the given decimal precision.
     */
    private function ceil(float $value, int $precision): float
    {
        $factor = 10 ** $precision;

        return ceil($value * $factor) / $factor;
    }

    /**
     * Round a value down to the given decimal precision.
     */
    private function floor(float $value, int $precision): float
    {
        $factor = 10 ** $precision;

        return floor($value * $factor) / $factor;
    }
}