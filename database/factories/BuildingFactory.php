<?php

namespace Database\Factories;

use App\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Building>
     */
    protected $model = Building::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true).' Building',
            'code' => fake()->unique()->lexify('BLD-??'),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
