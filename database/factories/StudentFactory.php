<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Student>
     */
    protected $model = Student::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement(['male', 'female']);
        $firstName = fake()->firstName($gender);
        $lastName = fake()->lastName();
        $birthDate = fake()->dateTimeBetween('-18 years', '-11 years');

        return [
            'student_number' => 'KXN-'.fake()->unique()->numerify('#######'),
            'lrn' => fake()->unique()->numerify('##############'),
            'first_name' => $firstName,
            'middle_name' => fake()->optional()->firstName($gender),
            'last_name' => $lastName,
            'gender' => $gender,
            'birth_date' => $birthDate->format('Y-m-d'),
            'place_of_birth' => fake()->city(),
            'civil_status' => 'single',
            'nationality' => 'Filipino',
            'citizenship' => 'Filipino',
            'status' => 'active',
            'is_active' => true,
        ];
    }
}
