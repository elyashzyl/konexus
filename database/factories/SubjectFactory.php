<?php

namespace Database\Factories;

use App\Models\GradeLevel;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Subject>
     */
    protected $model = Subject::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'code' => fake()->unique()->lexify('SUB-??'),
            'description' => fake()->sentence(),
            'department_id' => null,
            'grade_level_id' => GradeLevel::factory(),
            'is_active' => true,
        ];
    }
}
