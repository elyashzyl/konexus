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
     * Counter used to keep generated grade level names and codes unique, since
     * both columns are globally unique across all schools.
     *
     * @var int
     */
    protected static int $counter = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $grade = fake()->numberBetween(7, 12);
        $n = ++static::$counter;

        return [
            'name' => $n === 1 ? 'Grade '.$grade : 'Grade '.$grade.' ('.$n.')',
            'code' => $n === 1 ? (string) $grade : (string) $grade.'-'.$n,
            'short_name' => 'G'.$grade,
            'education_level' => $grade <= 10 ? 'junior-high' : 'senior-high',
            'sequence' => $grade,
            'is_active' => true,
        ];
    }
}
