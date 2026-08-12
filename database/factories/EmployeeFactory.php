<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Employee>
     */
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(['male', 'female']);
        $employmentType = fake()->randomElement(['teaching', 'non-teaching']);

        return [
            'employee_number' => 'EMP-'.fake()->unique()->numerify('#####'),
            'first_name' => fake()->firstName($gender),
            'middle_name' => fake()->optional()->firstName($gender),
            'last_name' => fake()->lastName(),
            'extension_name' => fake()->optional()->randomElement(['Jr.', 'Sr.', 'III']),
            'gender' => $gender,
            'birth_date' => fake()->dateTimeBetween('-60 years', '-20 years')->format('Y-m-d'),
            'mobile_number' => fake()->optional()->numerify('09#########'),
            'telephone_number' => fake()->optional()->numerify('(074)-###-####'),
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->optional()->address(),
            'employment_type' => $employmentType,
            'department_id' => Department::query()->inRandomOrder()->value('id'),
            'position' => fake()->jobTitle(),
            'hiring_type' => fake()->randomElement(['regular', 'contractual', 'part-time']),
            'date_hired' => fake()->dateTimeBetween('-10 years', 'now')->format('Y-m-d'),
            'status' => 'active',
            'is_active' => true,
        ];
    }
}
