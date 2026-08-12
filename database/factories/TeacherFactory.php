<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Teacher>
     */
    protected $model = Teacher::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::query()->where('employment_type', 'teaching')->inRandomOrder()->value('id'),
            'prc_number' => fake()->optional()->numerify('########'),
            'license_expiration' => now()->addYears(fake()->numberBetween(1, 5))->format('Y-m-d'),
            'specialization' => fake()->optional()->words(2, true),
            'major' => fake()->optional()->words(2, true),
            'minor' => fake()->optional()->words(2, true),
            'academic_load' => fake()->numberBetween(12, 30),
        ];
    }
}
