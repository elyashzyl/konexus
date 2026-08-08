<?php

namespace Database\Factories;

use App\Enums\MasterDataType;
use App\Models\MasterData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterData>
 */
class MasterDataFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<MasterData>
     */
    protected $model = MasterData::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(array_column(MasterDataType::toSeedData(), 'value')),
            'name' => fake()->unique()->words(2, true),
            'code' => null,
            'description' => null,
            'is_system' => false,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
