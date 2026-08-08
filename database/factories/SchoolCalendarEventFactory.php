<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\SchoolCalendarEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolCalendarEvent>
 */
class SchoolCalendarEventFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<SchoolCalendarEvent>
     */
    protected $model = SchoolCalendarEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'category' => fake()->randomElement(['holiday', 'enrollment', 'examination', 'school-event', 'announcement', 'suspension']),
            'description' => fake()->paragraph(),
            'academic_year_id' => AcademicYear::factory(),
            'start_date' => fake()->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'end_date' => null,
            'all_day' => true,
            'start_time' => null,
            'end_time' => null,
            'location' => null,
            'is_active' => true,
        ];
    }
}
