<?php

namespace Database\Factories;

use App\Models\GradeScale;
use App\Models\GradeScaleEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeScaleEntry>
 */
class GradeScaleEntryFactory extends Factory
{
    protected $model = GradeScaleEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'grade_scale_id' => GradeScale::factory(),
            'label' => $this->faker->randomElement(['Excellent', 'Very Good', 'Good', 'Passed', 'Needs Improvement']),
            'remarks' => null,
            'min_grade' => 75,
            'max_grade' => 100,
            'is_passing' => true,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}