<?php

namespace Database\Factories;

use App\Models\EnrollmentRequirement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnrollmentRequirement>
 */
class EnrollmentRequirementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<EnrollmentRequirement>
     */
    protected $model = EnrollmentRequirement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'PSA Birth Certificate', 'Report Card', 'Certificate of Good Moral Character',
                'Proof of Payment', '2x2 ID Picture', 'Recommendation Letter',
            ]),
            'code' => fake()->unique()->lexify('REQ-????'),
            'description' => fake()->sentence(),
            'is_required' => true,
            'type' => null,
            'applicable_grade_levels' => null,
            'applicable_enrollment_types' => null,
            'applicable_academic_year_id' => null,
            'applicable_campus_ids' => null,
            'sort_order' => fake()->numberBetween(1, 20),
            'is_active' => true,
        ];
    }
}