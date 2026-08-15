<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\PromotionDecision;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PromotionDecision> */
class PromotionDecisionFactory extends Factory
{
    protected $model = PromotionDecision::class;

    public function definition(): array
    {
        return ['student_id' => Student::factory(), 'enrollment_id' => Enrollment::factory(), 'academic_year_id' => AcademicYear::factory(), 'grade_level_id' => GradeLevel::factory(), 'decision' => 'promoted', 'general_average' => 85, 'basis_snapshot' => []];
    }
}
