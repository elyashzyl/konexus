<?php

namespace Database\Factories;

use App\Models\GradeLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeLevel>
 */
class GradeLevelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<GradeLevel>
     */
    protected $model = GradeLevel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grade = fake()->numberBetween(7, 12);

        return [
            'name' => 'Grade '.$grade,
            'code' => (string) $grade,
            'short_name' => 'G'.$grade,
            'education_level' => $grade <= 10 ? 'junior-high' : 'senior-high',
            'sequence' => $grade,
            'is_active' => true,
        ];
    }
}
