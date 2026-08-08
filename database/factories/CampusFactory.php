<?php

namespace Database\Factories;

use App\Models\Campus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campus>
 */
class CampusFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Campus>
     */
    protected $model = Campus::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' Campus',
            'code' => fake()->unique()->lexify('??C'),
            'address' => fake()->address(),
            'contact_number' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }
}
