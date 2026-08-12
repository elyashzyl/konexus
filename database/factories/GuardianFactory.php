<?php

namespace Database\Factories;

use App\Models\Guardian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guardian>
 */
class GuardianFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Guardian>
     */
    protected $model = Guardian::class;

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
            'relationship' => fake()->randomElement(['aunt', 'uncle', 'grandparent', 'sibling', 'cousin', 'family friend']),
            'occupation' => fake()->optional()->jobTitle(),
            'employer' => fake()->optional()->company(),
            'mobile_number' => fake()->optional()->numerify('09#########'),
            'telephone_number' => fake()->optional()->numerify('(074)-###-####'),
            'email' => fake()->optional()->safeEmail(),
            'address' => fake()->optional()->address(),
            'status' => 'active',
            'is_active' => true,
        ];
    }
}
