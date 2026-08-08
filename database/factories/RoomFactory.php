<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Room>
     */
    protected $model = Room::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Room '.fake()->numberBetween(101, 399),
            'code' => fake()->unique()->lexify('RM-??'),
            'building_id' => Building::factory(),
            'room_type' => 'classroom',
            'capacity' => fake()->numberBetween(30, 50),
            'is_active' => true,
        ];
    }
}
