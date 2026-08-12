<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\GradeScale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeScale>
 */
class GradeScaleFactory extends Factory
{
    protected $model = GradeScale::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'code' => strtoupper($this->faker->lexify('????')),
            'min_grade' => 0,
            'max_grade' => 100,
            'minimum_passing_grade' => 75,
            'decimal_precision' => 2,
            'rounding' => 'standard',
            'academic_year_id' => null,
            'is_default' => false,
            'is_active' => true,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (): array => ['is_default' => true]);
    }
}