<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\EnrollmentRequirement;
use App\Models\EnrollmentRequirementItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnrollmentRequirementItem>
 */
class EnrollmentRequirementItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<EnrollmentRequirementItem>
     */
    protected $model = EnrollmentRequirementItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'enrollment_requirement_id' => EnrollmentRequirement::factory(),
            'status' => 'not-submitted',
            'remarks' => null,
            'verified_by' => null,
            'verified_at' => null,
            'rejected_by' => null,
            'rejected_at' => null,
        ];
    }
}