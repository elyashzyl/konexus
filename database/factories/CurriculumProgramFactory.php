<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\CurriculumProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CurriculumProgram> */
class CurriculumProgramFactory extends Factory
{
    protected $model = CurriculumProgram::class;

    public function definition(): array
    {
        return ['academic_year_id' => AcademicYear::factory(), 'name' => 'MATATAG '.fake()->unique()->word(), 'code' => fake()->unique()->lexify('CUR-????'), 'framework' => 'matatag', 'calendar_type' => 'quarterly', 'grade_level_ids' => [], 'clusters' => null, 'compliance_status' => 'deped-aligned', 'status' => 'active', 'is_active' => true];
    }
}
