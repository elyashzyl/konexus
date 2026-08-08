<?php

namespace Database\Factories;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicTerm>
 */
class AcademicTermFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AcademicTerm>
     */
    protected $model = AcademicTerm::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => '1st Quarter',
            'code' => 'Q1',
            'sequence' => 1,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->startOfYear()->addMonths(2)->endOfMonth(),
            'is_active' => false,
        ];
    }
}
