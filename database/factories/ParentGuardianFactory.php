<?php

namespace Database\Factories;

use App\Models\ParentGuardian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParentGuardian>
 */
class ParentGuardianFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<ParentGuardian>
     */
    protected $model = ParentGuardian::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'last_name' => fake()->lastName(),
            'occupation' => fake()->optional()->jobTitle(),
            'employer' => fake()->optional()->company(),
            'educational_attainment' => fake()->optional()->randomElement(['High School', "Bachelor's Degree", 'Masteral', 'Doctorate']),
            'mobile_number' => fake()->optional()->numerify('09#########'),
            'telephone_number' => fake()->optional()->numerify('(074)-###-####'),
            'email' => fake()->optional()->safeEmail(),
            'address' => fake()->optional()->address(),
            'relationship' => fake()->randomElement(['father', 'mother', 'guardian']),
            'status' => 'active',
            'is_active' => true,
        ];
    }
}
