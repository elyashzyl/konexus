<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\CurriculumProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AcademicPeriod> */
class AcademicPeriodFactory extends Factory
{
    protected $model = AcademicPeriod::class;

    public function definition(): array
    {
        return ['curriculum_program_id' => CurriculumProgram::factory(), 'name' => '1st Quarter', 'code' => fake()->unique()->lexify('Q?'), 'sequence' => 1, 'start_date' => now()->startOfMonth(), 'end_date' => now()->addMonths(2)->endOfMonth(), 'status' => 'planned', 'is_active' => true];
    }
}
