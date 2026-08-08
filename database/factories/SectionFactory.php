<?php

namespace Database\Factories;

use App\Models\GradeLevel;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Section>
     */
    protected $model = Section::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'grade_level_id' => GradeLevel::factory(),
            'name' => fake()->unique()->randomElement(['Diamond', 'Emerald', 'Sapphire', 'Ruby', 'Gold', 'Silver']),
            'code' => fake()->unique()->lexify('SEC-??'),
            'adviser_id' => null,
            'room_id' => null,
            'max_capacity' => fake()->numberBetween(35, 50),
            'is_active' => true,
        ];
    }
}
