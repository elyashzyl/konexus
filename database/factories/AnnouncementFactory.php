<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Announcement>
     */
    protected $model = Announcement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(2, true),
            'category' => fake()->randomElement(['general', 'academic', 'event', 'emergency']),
            'priority' => fake()->randomElement(['low', 'normal', 'high']),
            'target_audience' => fake()->randomElement(['all', 'students', 'teachers', 'parents', 'staff']),
            'author_id' => User::factory(),
            'published' => true,
            'published_at' => now(),
            'starts_at' => null,
            'ends_at' => null,
            'is_active' => true,
        ];
    }
}
