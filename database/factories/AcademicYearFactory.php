<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AcademicYear>
     */
    protected $model = AcademicYear::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = fake()->numberBetween(2020, 2035);

        return [
            'name' => $startYear.'-'.($startYear + 1),
            'code' => (string) $startYear,
            'start_date' => "{$startYear}-06-01",
            'end_date' => ($startYear + 1).'-03-31',
            'calendar_type' => 'quarterly',
            'is_active' => false,
            'description' => null,
        ];
    }
}
