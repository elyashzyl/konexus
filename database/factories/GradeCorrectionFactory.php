<?php

namespace Database\Factories;

use App\Models\AcademicTerm;
use App\Models\GradeCorrection;
use App\Models\GradeRecord;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeCorrection>
 */
class GradeCorrectionFactory extends Factory
{
    protected $model = GradeCorrection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'grade_record_id' => GradeRecord::factory(),
            'student_id' => Student::factory(),
            'subject_id' => Subject::factory(),
            'academic_term_id' => AcademicTerm::factory(),
            'current_grade' => 80,
            'proposed_grade' => 85,
            'reason' => $this->faker->sentence(),
            'status' => GradeCorrection::STATUS_PENDING,
            'requested_by' => null,
            'approved_by' => null,
            'approved_at' => null,
            'approval_remarks' => null,
            'is_active' => true,
        ];
    }
}