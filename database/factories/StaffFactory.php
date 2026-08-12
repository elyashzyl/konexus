<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    /**
     * The name of the model's corresponding model.
     *
     * @var class-string<Staff>
     */
    protected $model = Staff::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::query()->where('employment_type', 'non-teaching')->inRandomOrder()->value('id'),
            'support_area' => fake()->randomElement([
                'Administrative',
                'Finance',
                'Registrar',
                'Guidance',
                'Facilities',
                'IT Support',
                'Library',
                'Cafeteria',
            ]),
        ];
    }
}
